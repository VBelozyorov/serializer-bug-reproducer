<?php

declare(strict_types=1);

namespace Vb\SerializerBugReproducer\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorBuilder;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class NotNormalizableValueExceptionCurrentTypeTest extends TestCase
{
    private static function createSerializerHeavy()
    {
        $reflectionExtractor = new ReflectionExtractor();

        $propertyTypeExtractor = new PropertyInfoExtractor(
            [$reflectionExtractor],
            [$reflectionExtractor],
            [],
            [$reflectionExtractor],
            [$reflectionExtractor],
        );

        $classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());
        $propertyAccessor = (new PropertyAccessorBuilder())->getPropertyAccessor();

        $normalizer = new ObjectNormalizer(
            $classMetadataFactory,
            null,
            $propertyAccessor,
            $propertyTypeExtractor,
        );

        return new Serializer(
            [$normalizer, new ArrayDenormalizer()],
        );
    }

    private static function createSerializerLight()
    {
        return new Serializer([new ObjectNormalizer(propertyAccessor: PropertyAccess::createPropertyAccessor())]);
    }

    #[DataProvider('invalidTypeDataProvider')]
    public function testNotNormalizableValueExceptionCurrentType(
        string $objectClass,
        mixed $data,
        array $context = [],
        ?SerializerInterface $serializer = null,
    ): void {
        $serializer = $serializer ?? self::createSerializerLight();

        $invalidValue = $data['field'];
        $expectedType = get_debug_type($invalidValue);

        try {
            $serializer->denormalize($data, $objectClass, context: $context);
            $this->fail('Expected NotNormalizableValueException to be thrown');
        } catch (NotNormalizableValueException $e) {
            $this->assertSame($expectedType, $e->getCurrentType(),'$e->getCurrentType() returned unexpected type');
        }
    }

    /**
     * Data provider with various invalid types
     *
     * Note: We only include types that PropertyAccessor will reject.
     * Some combinations are excluded because PHP's type juggling allows automatic conversion:
     * - bool, int, float can be converted to string
     * - bool, float (in some cases) can be converted to int
     * - bool, int, string (numeric) can be converted to float
     */
    public static function invalidTypeDataProvider(): array
    {
        $stdClassObject = new \stdClass();

        return [
            // TypeBool - incompatible types
            'null to bool' => [
                TypeBool::class,
                ['field' => null],
            ],
            'array to bool' => [
                TypeBool::class,
                ['field' => ['value']],
            ],
            'stdClass to bool' => [
                TypeBool::class,
                ['field' => $stdClassObject],
            ],

            // TypeBool - with filter_var()
            'int to bool (heavy)' => [
                TypeBool::class,
                ['field' => 123],
                [AbstractNormalizer::FILTER_BOOL => true],
                self::createSerializerHeavy(),
            ],
            'string to bool (heavy)' => [
                TypeBool::class,
                ['field' => 'not-a-bool'],
                [AbstractNormalizer::FILTER_BOOL => true],
                self::createSerializerHeavy(),
            ],

            // TypeString - incompatible types (int, float, bool are automatically converted)
            'null to string' => [
                TypeString::class,
                ['field' => null],
            ],
            'array to string' => [
                TypeString::class,
                ['field' => ['abc']],
            ],
            'stdClass to string' => [
                TypeString::class,
                ['field' => $stdClassObject],
            ],

            // TypeInt - incompatible types (bool, float are sometimes converted)
            'null to int' => [
                TypeInt::class,
                ['field' => null],
            ],
            'string to int' => [
                TypeInt::class,
                ['field' => 'not a number'],
            ],
            'array to int' => [
                TypeInt::class,
                ['field' => [123]],
            ],
            'stdClass to int' => [
                TypeInt::class,
                ['field' => $stdClassObject],
            ],

            // TypeFloat - incompatible types (bool, int, numeric string are converted)
            'null to float' => [
                TypeFloat::class,
                ['field' => null],
            ],
            'string to float' => [
                TypeFloat::class,
                ['field' => 'not a number'],
            ],
            'array to float' => [
                TypeFloat::class,
                ['field' => [3.14]],
            ],
            'stdClass to float' => [
                TypeFloat::class,
                ['field' => $stdClassObject],
            ],

            // TypeArray - all incompatible types
            'null to array' => [
                TypeArray::class,
                ['field' => null],
            ],
            'bool to array' => [
                TypeArray::class,
                ['field' => true],
            ],
            'int to array' => [
                TypeArray::class,
                ['field' => 42],
            ],
            'float to array' => [
                TypeArray::class,
                ['field' => 2.718],
            ],
            'string to array' => [
                TypeArray::class,
                ['field' => 'string'],
            ],
            'stdClass to array' => [
                TypeArray::class,
                ['field' => $stdClassObject],
            ],
        ];
    }
}

class TypeBool
{
    public bool $field;
}

class TypeString
{
    public string $field;
}

class TypeInt
{
    public int $field;
}

class TypeFloat
{
    public float $field;
}

class TypeArray
{
    public array $field;
}


