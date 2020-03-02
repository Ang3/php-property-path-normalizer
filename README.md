# PHP property path normalizer

[![Build Status](https://travis-ci.org/Ang3/php-property-path-normalizer.svg?branch=master)](https://travis-ci.org/Ang3/php-property-path-normalizer) [![Latest Stable Version](https://poser.pugx.org/ang3/php-property-path-normalizer/v/stable)](https://packagist.org/packages/ang3/php-property-path-normalizer) [![Latest Unstable Version](https://poser.pugx.org/ang3/php-property-path-normalizer/v/unstable)](https://packagist.org/packages/ang3/php-property-path-normalizer) [![Total Downloads](https://poser.pugx.org/ang3/php-property-path-normalizer/downloads)](https://packagist.org/packages/ang3/php-property-path-normalizer)

This normalizer is a basic property mapper. Its helps you to normalize/denormalize specific data by passing mapping in context. It was developed to work with the component "Serializer" of Symfony. Please read the [documentation](https://symfony.com/doc/current/components/serializer.html) of the Symfony component "Serializer".

## Installation

```shell
composer require ang3/php-property-path-normalizer
```

If you install this component outside of a Symfony application, you must require the vendor/autoload.php file in your code to enable the class autoloading mechanism provided by Composer. Read [this article](https://symfony.com/doc/current/components/using_components.html) for more details.

## Usage

**Basic usage**

```php
require_once 'vendor/autoload.php';

use Ang3\Component\Serializer\Normalizer\PropertyPathNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

// Create the serializer with the normalizer
$serializer = new Serializer([
  new PropertyPathNormalizer(), // Put it before all object normalizers so as to get higher priority!
  new ObjectNormalizer(), // Important if data contains some no array/scalar values.
]);

$data = [
  'foo' => 'bar',
  'bar' => 'baz'
];

$normalized = $serializer->normalize($data, null, $context = [
  'attributes' => [
    'foo' => 'qux'
  ]
]);

dump($normalized);

// Output :
// array:1 [
//  "qux" => 'bar'
// ]

```

All the logic resides in the context.

**Normalization context**

The composer tries to normalize from the serializer, then it maps attributes from normalized value to the target array.

- ```property_mapping``` [default: ```[]```] list of mapped property paths
- ```value_as_normalized_path``` [default: ```true```] Set to ```false``` to reverse normalized/denormalized path of context
- ```normalization``` [default: ```[]```] specific context for normalization process
- ```denormalization``` [default: ```[]```] specific context for denormalization process