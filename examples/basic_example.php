<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Ang3\Component\Serializer\Normalizer\PropertyPathNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeZoneNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

// Create the serializer
$serializer = new Serializer([
	new DateTimeNormalizer,
	new DateTimeZoneNormalizer,
	new PropertyPathNormalizer,
	new ObjectNormalizer(null, null, null, new ReflectionExtractor()),
]);

/**
 * Example record
 */

$myRecord = new \stdClass;
$myRecord->foo = 'bar';
$myRecord->bar = 123;
$myRecord->baz = new DateTime;

echo "\n". 'Initial record: ' . "\n";
dump($myRecord);

/**
 * Data normalization
 */

echo "\n". 'Normalization context: ' . "\n";
$normalizationContext = [
	PropertyPathNormalizer::PROPERTY_MAPPING_KEY => [
		'foo' => 'data[0].foo',
		'bar' => 'data[0].bar',
		'baz' => 'data[0].baz'
	]
];
dump($normalizationContext);

echo "\n". 'Normalized data: ' . "\n";
$data = $serializer->normalize($myRecord, null, $normalizationContext);
dump($data);

/*
 * Output:
 * array:1 [
 *   "data" => array:1 [
 *    0 => array:3 [
 *       "foo" => "bar"
 *       "bar" => 123
 *       "baz" => "2020-03-02T11:18:06+01:00"
 *     ]
 *   ]
 * ]
 */

/**
 * Data denormalization
 */

class FooClass
{
	public $foo;
	public $bar;
	/**
	 * @var DateTimeInterface
	 */
	private $baz;

	public function setBaz(DateTimeInterface $baz)
	{
		$this->baz = $baz;
	}
}

echo "\n". 'Denormalization context: ' . "\n";
$denormalizationContext = [
	PropertyPathNormalizer::VALUE_AS_NORMALIZED_PATH_KEY => false,
	PropertyPathNormalizer::PROPERTY_MAPPING_KEY => [
		'foo' => 'data[0].foo',
		'bar' => 'data[0].bar',
		'baz' => 'data[0].baz'
	]
];
dump($denormalizationContext);

echo "\n". 'Denormalized data: ' . "\n";
$record = $serializer->denormalize($data, FooClass::class, null, $denormalizationContext);
dump($record);

/*
 * Output:
 * FooClass^ {#24
 *  +foo: "bar"
 *  +bar: 123
 *  -baz: DateTimeImmutable @1583144286 {#38
 *    date: 2020-03-02 11:18:06.0 +01:00
 *  }
 *}
 */