<?php

namespace Ang3\Component\Serializer\Normalizer;

use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;
use Throwable;

/**
 * @author Joanis ROUANET
 */
class PropertyPathNormalizer implements NormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait;

    /**
     * List of property mapping.
     */
    const PROPERTY_MAPPING_KEY = 'property_path_mapping';

    /**
     * Flag to control whether the mapped property value is the normalized path.
     */
    const VALUE_AS_NORMALIZED_PATH_KEY = 'value_as_normalized_path';

    /**
     * Flag to control whether data must be normalized by the serializer if no property mapped.
     */
    const FALLBACK_NORMALIZATION = 'fallback_normalization';

    /**
     * Flag to control whether each property value must be normalized.
     */
    const PROPERTY_VALUE_NORMALIZATION = 'property_value_normalization';

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
        self::FALLBACK_NORMALIZATION => true,
        self::PROPERTY_VALUE_NORMALIZATION => true,
    ];

    /**
     * This parameter is used to prevent
     * circular normalizations in global normalization process.
     *
     * @internal
     *
     * @var bool
     */
    private $isNormalizing = false;

    /**
     * This parameter is used to prevent
     * circular denormalizations in global denormalization process.
     *
     * @internal
     *
     * @var bool
     */
    private $isDenormalizing = false;

    public function __construct(array $defaultContext = [], PropertyAccessorInterface $propertyAccessor = null)
    {
        // Hydratation
        $this->propertyAccessor = $propertyAccessor ?: new PropertyAccessor(true, true);
        $this->defaultContext = array_merge($this->defaultContext, $defaultContext);
    }

    /**
     * {@inheritdoc}.
     */
    public function normalize($data, $format = null, array $context = [])
    {
        // Si pas de données
        if (!$data) {
            // ... pas de données
            return [];
        }

        // Fusion du contexte par défaut et celui reçu en paramètre
        $context = array_merge($this->defaultContext, $context);

        // Récupération des options depuis le contexte
        $options = [
            self::PROPERTY_MAPPING_KEY => array_filter((array) $context[self::PROPERTY_MAPPING_KEY]),
            self::VALUE_AS_NORMALIZED_PATH_KEY => (bool) $context[self::VALUE_AS_NORMALIZED_PATH_KEY],
            self::FALLBACK_NORMALIZATION => (bool) $context[self::FALLBACK_NORMALIZATION],
            self::PROPERTY_VALUE_NORMALIZATION => (bool) $context[self::PROPERTY_VALUE_NORMALIZATION],
        ];

        // Suppression de paramètres de contexte de ce normaliseur
        unset($context[self::PROPERTY_MAPPING_KEY]);
        unset($context[self::VALUE_AS_NORMALIZED_PATH_KEY]);
        unset($context[self::FALLBACK_NORMALIZATION]);
        unset($context[self::PROPERTY_VALUE_NORMALIZATION]);

        // Récupération des propriétés mappées
        $properties = $options[self::PROPERTY_MAPPING_KEY];

        // Si pas de propriété mappée
        if (!$properties) {
            // Si on souhaite la normalisation de secours
            if (true === $options[self::FALLBACK_NORMALIZATION]) {
                // Si on un sérialiseur qui peut normaliser
                if ($this->serializer instanceof NormalizerInterface) {
                    // On signale qu'on est entrain de normaliser
                    $this->isNormalizing = true;

                    // Normalisation de la normalisation des données
                    $normalized = $this->serializer->normalize($data, null, $context);

                    // On signale qu'on a finit de normaliser
                    $this->isNormalizing = false;

                    // Retour des données normalisées
                    return $normalized;
                }
            }

            // Pas de données normalisables
            return [];
        }

        // Initialisation des valeurs normalisées
        $normalized = [];

        // Pour chaque attribut mappé
        foreach ($properties as $pathA => $pathB) {
            // Définition des chemins source et cible
            $sourcePath = $options[self::VALUE_AS_NORMALIZED_PATH_KEY] ? $pathA : $pathB;
            $targetPath = $this->normalizeArrayPath($options[self::VALUE_AS_NORMALIZED_PATH_KEY] ? $pathB : $pathA);

            // Si la propriété est illisible dans les données initiales
            if ($this->propertyAccessor->isReadable($data, $sourcePath)) {
                try {
                    // Récupération de la valeur
                    $value = $this->propertyAccessor->getValue($data, $sourcePath);
                } catch (Throwable $e) {
                    throw new RuntimeException(sprintf('Failed to read data value "%s"', $sourcePath), 0, $e);
                }

                // Si la propriété ne peut pas être
                if (!$this->propertyAccessor->isWritable($normalized, $targetPath)) {
                    // Propriété suivante
                    continue;
                }

                // Si on souhaite normaliser les valeurs des propriétés
                if (true === $options[self::PROPERTY_VALUE_NORMALIZATION]) {
                    // Si on un sérialiseur qui peut normaliser
                    if ($this->serializer instanceof NormalizerInterface) {
                        // On signale qu'on est entrain de normaliser
                        $this->isNormalizing = true;

                        // Si le sérialiseur supporte la normalisation de la valeur
                        if ($this->serializer->supportsNormalization($value)) {
                            try {
                                // Normalisation de la valeur
                                $value = $this->serializer->normalize($value, null, $context);
                            } catch (Throwable $e) {
                                throw new RuntimeException(sprintf('Failed to normalize the value of property "%s"', $sourcePath), 0, $e);
                            }
                        }

                        // On signale qu'on a finit de normaliser
                        $this->isNormalizing = false;
                    }
                }
            } else {
                // Pas de valeur
                $value = null;
            }

            try {
                // Enregistrement de la valeur sur le chemin cible
                $this->propertyAccessor->setValue($normalized, $targetPath, $value);
            } catch (Throwable $e) {
                throw new RuntimeException(sprintf('Failed to write value of source property "%s" in target property "%s"', $sourcePath, $targetPath), 0, $e);
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
