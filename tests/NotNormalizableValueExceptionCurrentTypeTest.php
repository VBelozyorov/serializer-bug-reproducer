<?php

declare(strict_types=1);

namespace Vb\SerializerBugReproducer\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class NotNormalizableValueExceptionCurrentTypeTest extends TestCase
{
    #[DataProvider('invalidTypeDataProvider')]
    public function testNotNormalizableValueExceptionCurrentType(string $objectClass, mixed $data, mixed $invalidValue): void
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $serializer = new Serializer([new ObjectNormalizer(propertyAccessor: $propertyAccessor)]);

        $expectedType = get_debug_type($invalidValue);

        try {
            $serializer->denormalize($data, $objectClass);
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
                null
            ],
            'array to bool' => [
                TypeBool::class,
                ['field' => ['value']],
                ['value'],
            ],
            'stdClass to bool' => [
                TypeBool::class,
                ['field' => $stdClassObject],
                $stdClassObject,
            ],

            // TypeString - incompatible types (int, float, bool are automatically converted)
            'null to string' => [
                TypeString::class,
                ['field' => null],
                null,
            ],
            'array to string' => [
                TypeString::class,
                ['field' => ['abc']],
                ['abc'],
            ],
            'stdClass to string' => [
                TypeString::class,
                ['field' => $stdClassObject],
                $stdClassObject,
            ],

            // TypeInt - incompatible types (bool, float are sometimes converted)
            'null to int' => [
                TypeInt::class,
                ['field' => null],
                null,
            ],
            'string to int' => [
                TypeInt::class,
                ['field' => 'not a number'],
                'not a number',
            ],
            'array to int' => [
                TypeInt::class,
                ['field' => [123]],
                [123],
            ],
            'stdClass to int' => [
                TypeInt::class,
                ['field' => $stdClassObject],
                $stdClassObject,
            ],

            // TypeFloat - incompatible types (bool, int, numeric string are converted)
            'null to float' => [
                TypeFloat::class,
                ['field' => null],
                null,
            ],
            'string to float' => [
                TypeFloat::class,
                ['field' => 'not a number'],
                'not a number',
            ],
            'array to float' => [
                TypeFloat::class,
                ['field' => [3.14]],
                [3.14],
            ],
            'stdClass to float' => [
                TypeFloat::class,
                ['field' => $stdClassObject],
                $stdClassObject,
            ],

            // TypeArray - all incompatible types
            'null to array' => [
                TypeArray::class,
                ['field' => null],
                null,
            ],
            'bool to array' => [
                TypeArray::class,
                ['field' => true],
                true,
            ],
            'int to array' => [
                TypeArray::class,
                ['field' => 42],
                42,
            ],
            'float to array' => [
                TypeArray::class,
                ['field' => 2.718],
                2.718,
            ],
            'string to array' => [
                TypeArray::class,
                ['field' => 'string'],
                'string',
            ],
            'stdClass to array' => [
                TypeArray::class,
                ['field' => $stdClassObject],
                $stdClassObject,
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


