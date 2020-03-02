<?php

namespace Ang3\Component\Serializer\Normalizer;

use ArrayObject;
use stdClass;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;
use Throwable;

/**
 * @author Joanis ROUANET
 */
class PropertyPathNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait;

    /**
     * List of property mapping.
     */
    const PROPERTY_MAPPING_KEY = 'property_mapping';

    /**
     * Flag to control whether the mapped property value is the normalized path.
     */
    const VALUE_AS_NORMALIZED_PATH_KEY = 'value_as_normalized_path';

    /**
     * Sub denormalization context key.
     */
    const DENORMALIZATION_KEY = 'denormalization';

    /**
     * Sub normalization context key.
     */
    const NORMALIZATION_KEY = 'normalization';

    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    /**
     * @var array
     */
    private $defaultContext = [
        self::PROPERTY_MAPPING_KEY => [],
        self::VALUE_AS_NORMALIZED_PATH_KEY => true,
        self::DENORMALIZATION_KEY => [],
        self::NORMALIZATION_KEY => [],
    ];

    /**
     * This parameter is used to prevent
     * circular denormalizations in denormalization process.
     *
     * @internal
     *
     * @var bool
     */
    private $isDenormalizing = false;

    /**
     * This parameter is used to prevent
     * circular normalizations in normalization process.
     *
     * @internal
     *
     * @var bool
     */
    private $isNormalizing = false;

    public function __construct(array $defaultContext = [], PropertyAccessorInterface $propertyAccessor = null)
    {
        // Hydratation
        $this->propertyAccessor = $propertyAccessor ?: new PropertyAccessor(true);
        $this->defaultContext = array_merge($this->defaultContext, $defaultContext);
    }

    /**
     * {@inheritdoc}.
     */
    public function denormalize($data, $type, $format = null, array $context = [])
    {
        // Fusion du contexte par défaut et celui reçu en paramètre
        $context = array_merge($this->defaultContext, $context);

        // Normalisation des données
        $data = $this->normalize($data, $format, $context);

        // Si le sérialiseur n'est pas un dénormaliseur
        if (!$this->serializer instanceof DenormalizerInterface) {
            throw new LogicException('Cannot denormalize data because the injected serializer is not a denormalizer');
        }

        // On signale que nous sommes en train de dénormaliser
        // pour éviter une boucle sur ce même dénormaliseur
        $this->isDenormalizing = true;

        // Si le sérialiseur ne supporte pas les données pour une dénormalisation
        if (!$this->serializer->supportsDenormalization($data, $type, $format)) {
            throw new InvalidArgumentException(sprintf('Cannot denormalize data because the serializer has no other denormalizer for data of type "%s" and type "%s" - You must inject an object normalizer in the serializer', gettype($data), $type));
        }

        // Dénormalisation via le sérialiseur
        $denormalized = $this->serializer->denormalize($data, $type, $format, $context[self::DENORMALIZATION_KEY] ?? []);

        // On signale avoir finit de dénormaliser
        $this->isDenormalizing = false;

        // Retour des données dénormalisées
        return $denormalized;
    }

    /**
     * {@inheritdoc}.
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $this->supportsNormalization($data, $format) && false === $this->isDenormalizing;
    }

    /**
     * {@inheritdoc}.
     */
    public function normalize($data, $format = null, array $context = [])
    {
        // Fusion du contexte par défaut et celui reçu en paramètre
        $context = array_merge($this->defaultContext, $context);

        // Si pas de normaliseur
        if (!$this->serializer instanceof NormalizerInterface) {
            throw new LogicException('Cannot normalize data because the injected serializer is not a normalizer');
        }

        // S'il s'agit d'un objet standard
        if ($data instanceof stdClass) {
            // Convertion de l'objet en tableau
            $data = get_object_vars($data);
        }

        // On signale que nous sommes en train de normaliser
        // pour éviter une boucle sur ce même normaliseur
        $this->isNormalizing = true;

        // Normalisation des données via le sérialiseur
        $data = $this->serializer->normalize($data, $format, $context[self::NORMALIZATION_KEY] ?? []);

        // On signale avoir finit de normaliser
        $this->isNormalizing = false;

        // Si les données sont dans un objet Tableau
        if ($data instanceof ArrayObject) {
            // Récupération d'une copie du tableau
            $data = $data->getArrayCopy();
        }

        // Si les données ne sont pas sous forme de tableau
        if (!is_array($data)) {
            // Pas de données normalisables
            return [];
        }

        // Récupération des propriétés mappées
        $properties = (array) (array_merge($this->defaultContext, $context)[self::PROPERTY_MAPPING_KEY] ?? []);

        // SI pas de propriété mappée
        if (!$properties) {
            // Pas de données normalisables
            return [];
        }

        // Récupération du booléan indiquant si les valeurs correspondent au chemin cible
        $isValueAsNormalizedPath = true === (array_merge($this->defaultContext, $context)[self::VALUE_AS_NORMALIZED_PATH_KEY] ?? $this->defaultContext[self::VALUE_AS_NORMALIZED_PATH_KEY]);

        // Initialisation des valeurs normalisées
        $normalized = [];

        // Pour chaque attribut mappé
        foreach ($properties as $pathA => $pathB) {
            // Définition des chemins source et cible
            $target = $isValueAsNormalizedPath ? $pathB : $pathA;
            $source = $pathA === $target ? $pathB : $pathA;

            // Définition des chemins source et cible
            $targetPath = $this->normalizeArrayPath($target);
            $sourcePath = $this->normalizeArrayPath($source);

            // Si la propriété est illisible dans les données initiales
            if (!$this->propertyAccessor->isReadable($data, $sourcePath)) {
                // Propriété suivante
                continue;
            }

            try {
                // Récupération de la valeur
                $value = $this->propertyAccessor->getValue($data, $sourcePath);
            } catch (Throwable $e) {
                throw new RuntimeException(sprintf('Failed to read data value "%s"', $source), 0, $e);
            }

            // Si la propriété ne peut pas être
            if (!$this->propertyAccessor->isWritable($normalized, $targetPath)) {
                // Propriété suivante
                continue;
            }

            try {
                // Enregistrement de la valeur sur le chemin cible
                $this->propertyAccessor->setValue($normalized, $targetPath, $value);
            } catch (Throwable $e) {
                throw new RuntimeException(sprintf('Failed to write value of source property "%s" in target property "%s"', $source, $target), 0, $e);
            }
        }

        // Retour du tableau normalisé
        return $normalized;
    }

    /**
     * {@inheritdoc}.
     */
    public function supportsNormalization($data, $format = null)
    {
        return (is_object($data) || is_array($data)) && false === $this->isNormalizing;
    }

    public function normalizeArrayPath(string $path): string
    {
        // Si la première propriété à traverser est un attribut d'objet
        if (preg_match('#^(\w+)(.*)#', $path, $matches)) {
            // On transforme la propriété en entrée de tableau
            $path = sprintf('[%s]%s', $matches[1], $matches[2]);
        }

        // On remplace tous les attributs d'objet par une entrée de tableau
        return preg_replace('#\.(\w+)#', '[$1]', $path);
    }
}
