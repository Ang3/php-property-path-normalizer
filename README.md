# PHP property path normalizer

[![Build Status](https://travis-ci.org/Ang3/php-property-path-normalizer.svg?branch=master)](https://travis-ci.org/Ang3/php-property-path-normalizer) [![Latest Stable Version](https://poser.pugx.org/ang3/php-property-path-normalizer/v/stable)](https://packagist.org/packages/ang3/php-property-path-normalizer) [![Latest Unstable Version](https://poser.pugx.org/ang3/php-property-path-normalizer/v/unstable)](https://packagist.org/packages/ang3/php-property-path-normalizer) [![Total Downloads](https://poser.pugx.org/ang3/php-property-path-normalizer/downloads)](https://packagist.org/packages/ang3/php-property-path-normalizer)

This normalizer was developed to work with the component "Serializer" of Symfony. Please read the [documentation](https://symfony.com/doc/current/components/serializer.html) of the component to know more information about serializer usage. Also, this normalize uses the component "PropertyAccess" to read data and write normalized arrays. Please read the [documentation](https://symfony.com/doc/current/components/property_access.html) of the component to know more about *property paths*.

This normalizer helps you to map specific data by property paths into structured array: **it does not normalize values** but forward this job to the optional injected serializer.

## Installation

```shell
composer require ang3/php-property-path-normalizer
```

If you install this component outside of a Symfony application, you must require the vendor/autoload.php file in your code to enable the class autoloading mechanism provided by Composer. Read [this article](https://symfony.com/doc/current/components/using_components.html) for more details.

## Usage

All the logic resides in the context. it contains the mapping of properties.

In this first example below, the instance of ```\DateTime``` will be not normalized because no serializer has been injected to support its normalization.

```php
require_once 'vendor/autoload.php';

use Ang3\Component\Serializer\Normalizer\PropertyPathNormalizer;

// Create the normalizer
$normalizer = new PropertyPathNormalizer($defaultContext = []);

// Fake data record
$myRecord = new \stdClass;
$myRecord->foo = 'bar';
$myRecord->bar = 123;
$myRecord->baz = new DateTime;

// Define the context and your mapping
$normalizationContext = [
  PropertyPathNormalizer::PROPERTY_MAPPING_KEY => [
    'foo' => 'data[0].foo', // By default, the key is the "source" and the value the "target"
    'bar' => 'data[0].bar',
    'baz' => 'data[0].baz'
  ]
];

$data = $serializer->normalize($myRecord, null, $normalizationContext);
dump($data);

/*
 * Output:
 * array:1 [
 *   "data" => array:1 [
 *    0 => array:3 [
 *       "foo" => "bar"
 *       "bar" => 123
 *       "baz" => object: DateTime
 *     ]
 *   ]
 * ]
 */
```

- If a proeprty cannot be read from data, its value is normalized as ```null```
- If a property cannot be write in normalized array, a ```Symfony\Component\Serializer\Exception\LogicException``` is thrown

**Good to know:** all target property paths are *automatically* converted for array support (i.e. ```foo.bar[0]``` is automatically normalized to ```[foo][bar][0]```).

To normalize objects like a date, you must inject a serializer instance with the method ```setSerializer(SerializerInterface $serializer)``` or construct your serializer by including this normalizer at the top of object/array normalizers.

```php
//...
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeZoneNormalizer;

$normalizer = new PropertyPathNormalizer($defaultContext);
$normalizer->setSerializer(new Serializer([
	new DateTimeNormalizer,
	new DateTimeZoneNormalizer,
]));

// ...

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
```

Of course, you can directly create your serializer with this normalizer and others normalizers but you must take care about the order of normalizers.

Last but not least, if **no property are mapped** (be careful about optional default context) this normalizer will try to forward the normalization process to the serializer while preventing possible circular support checkings. In this case, if the serializer does not support the normalization of whole data, am empty array is returned.

**Context parameters**

- ```property_path_mapping``` [default: ```[]```] list of mapped property paths
- ```value_as_normalized_path``` [default: ```true```] set to ```false``` to reverse normalized/denormalized path of context
- ```fallback_normalization``` [default: ```true```] set to ```false``` to disable the fallback normalization in case of no mapped property
- ```property_value_normalization``` [default: ```true```] set to ```false``` to disable the normalization of property values