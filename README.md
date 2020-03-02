# PHP property path normalizer

[![Build Status](https://travis-ci.org/Ang3/php-property-path-normalizer.svg?branch=master)](https://travis-ci.org/Ang3/php-property-path-normalizer) [![Latest Stable Version](https://poser.pugx.org/ang3/php-property-path-normalizer/v/stable)](https://packagist.org/packages/ang3/php-property-path-normalizer) [![Latest Unstable Version](https://poser.pugx.org/ang3/php-property-path-normalizer/v/unstable)](https://packagist.org/packages/ang3/php-property-path-normalizer) [![Total Downloads](https://poser.pugx.org/ang3/php-property-path-normalizer/downloads)](https://packagist.org/packages/ang3/php-property-path-normalizer)

This normalizer is a basic property mapper. Its helps you to normalize/denormalize specific data by passing mapping in context. It was developed to work with the component "Serializer" of Symfony. Please read the [documentation](https://symfony.com/doc/current/components/serializer.html) of the component to know more information about serializer usage.



## Installation

```shell
composer require ang3/php-property-path-normalizer
```

If you install this component outside of a Symfony application, you must require the vendor/autoload.php file in your code to enable the class autoloading mechanism provided by Composer. Read [this article](https://symfony.com/doc/current/components/using_components.html) for more details.

## Usage

**Basic usage**

Here is the content of file ```examples/basic_example.php```:

```php
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

```

All the logic resides in the context.

**Context parameters**

The composer tries to normalize from the serializer, then it maps attributes from normalized value to the target array.

- ```property_mapping``` [default: ```[]```] list of mapped property paths
- ```value_as_normalized_path``` [default: ```true```] Set to ```false``` to reverse normalized/denormalized path of context
- ```normalization``` [default: ```[]```] specific context for data normalization process
- ```denormalization``` [default: ```[]```] specific context for data denormalization process