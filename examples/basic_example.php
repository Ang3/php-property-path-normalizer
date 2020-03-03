<?php

require_once __DIR__.'/../vendor/autoload.php';

use Ang3\Component\Serializer\Normalizer\PropertyPathNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeZoneNormalizer;
use Symfony\Component\Serializer\Serializer;

// Create the serializer
$serializer = new Serializer([
    new DateTimeNormalizer(),
    new DateTimeZoneNormalizer(),
    new PropertyPathNormalizer(),
]);

/**
 * Example record.
 */
$myRecord = new \stdClass();
$myRecord->foo = 'bar';
$myRecord->bar = 123;
$myRecord->baz = new DateTime();

echo "\n".'Initial record: '."\n";
dump($myRecord);

/*
 * Data normalization
 */

echo "\n".'Normalization context: '."\n";
$normalizationContext = [
    PropertyPathNormalizer::PROPERTY_VALUE_NORMALIZATION => false,
    PropertyPathNormalizer::PROPERTY_MAPPING_KEY => [
        'foo' => 'data[0].foo',
        'bar' => 'data[0].bar',
        'baz' => 'data[0].baz',
        'qux' => 'data[0].qux',
    ],
];
dump($normalizationContext);

echo "\n".'Normalized data: '."\n";
$data = $serializer->normalize($myRecord, null, $normalizationContext);
dump($data);

/*
 * Output:
 * array:1 [
 *   "data" => array:1 [
 *    0 => array:3 [
 *       "foo" => "bar"
 *       "bar" => 123
 *       "baz" => "2020-03-02T11:18:06+01:00",
 *       "qux" => null
 *     ]
 *   ]
 * ]
 */
