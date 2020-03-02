<?php

namespace Ang3\Component\Serializer\Normalizer\Tests;

use Ang3\Component\Serializer\Normalizer\PayloadNormalizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeZoneNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * @author Joanis ROUANET
 */
class PayloadNormalizerTest extends TestCase
{
    /**
     * @var PayloadNormalizer
     */
    private $normalizer;

    /**
     * @var MockObject
     */
    private $propertyAccessor;

    /**
     * @var MockObject
     */
    private $serializer;

    public function setUp(): void
    {
        $this->propertyAccessor = $this->createMock(PropertyAccessor::class);
        $this->normalizer = new PayloadNormalizer([], $this->propertyAccessor);
        $this->serializer = $this->createMock(Serializer::class);
        $this->normalizer->setSerializer($this->serializer);
    }

    /**
     * @return array<array<string>>
     */
    public function provideTestNormalizeArrayPath(): array
    {
        return [
            ['0', '[0]'],
            ['[0]', '[0]'],
            ['0.foo', '[0][foo]'],
            ['[0][foo]', '[0][foo]'],
            ['[foo][0]', '[foo][0]'],
            ['foo[0]', '[foo][0]'],
            ['foo[0].bar', '[foo][0][bar]'],
        ];
    }

    /**
     * @covers ::normalizeArrayPath
     * @dataProvider provideTestNormalizeArrayPath
     */
    public function testNormalizeArrayPath(string $path, string $result): void
    {
        $this->assertEquals($result, $this->normalizer->normalizeArrayPath($path));
    }

    /**
     * @covers ::supportsNormalization
     */
    public function testSupportsNormalization(): void
    {
        $this->assertFalse($this->normalizer->supportsNormalization(true, null));
        $this->assertFalse($this->normalizer->supportsNormalization(1, null));
        $this->assertFalse($this->normalizer->supportsNormalization(1.00, null));
        $this->assertFalse($this->normalizer->supportsNormalization('foo', null));
        $this->assertTrue($this->normalizer->supportsNormalization([], null));
        $this->assertTrue($this->normalizer->supportsNormalization(new stdClass(), null));
        $this->assertTrue($this->normalizer->supportsNormalization($this, null));
    }

    /**
     * @covers ::normalize
     * 
     * @param mixed        $data
     * @param mixed        $value
     * @param array<mixed> $normalized
     */
    public function testNormalizeArray(): void
    {
    	$data = [
    		'foo' => 'bar',
    		'bar' => 'baz'
    	];

    	$context = [
    		'attributes' => $data
    	];

    	$this->serializer->expects($this->once())->method('normalize')->with($data, null, [])->willReturn($data);

    	$attributeValue = 'qux';

    	$this->propertyAccessor->expects($this->exactly(2))->method('isReadable')->willReturn(true);
    	$this->propertyAccessor->expects($this->exactly(2))->method('getValue')->willReturn($attributeValue);
    	$this->propertyAccessor->expects($this->exactly(2))->method('isWritable')->willReturn(true);
    	$this->propertyAccessor->expects($this->exactly(2))->method('setValue')->willReturn($attributeValue);

    	// /*
    	//  * 'foo' mapping
    	//  */

     //    $this->propertyAccessor->expects($this->once())->method('isReadable')->with($data, '[foo]')->willReturn(true);
     //    $this->propertyAccessor->expects($this->once())->method('getValue')->with($data, '[foo]')->willReturn($attributeValue);
     //    $this->propertyAccessor->expects($this->once())->method('isWritable')->with([], '[bar]')->willReturn(true);
     //    $this->propertyAccessor->expects($this->once())->method('setValue')->with([], '[bar]', $attributeValue);

    	// /*
    	//  * 'bar' mapping
    	//  */
    	
     //    $this->propertyAccessor->expects($this->once())->method('isReadable')->with($data, '[bar]')->willReturn(true);
     //    $this->propertyAccessor->expects($this->once())->method('getValue')->with($data, '[bar]')->willReturn($attributeValue);
     //    $this->propertyAccessor->expects($this->once())->method('isWritable')->with([], '[baz]')->willReturn(true);
     //    $this->propertyAccessor->expects($this->once())->method('setValue')->with([], '[baz]', $attributeValue);

        // Normalization

        $this->assertIsArray($this->normalizer->normalize($data, null, $context));
    }
}
