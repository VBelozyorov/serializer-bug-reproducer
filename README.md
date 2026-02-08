# Symfony Serializer Bug Reproducer

A project to demonstrate a bug in `symfony/serializer` where `NotNormalizableValueException::getCurrentType()` returns `'array'` instead of the actual type of the problematic value.

Issue reference: https://github.com/symfony/symfony/issues/63318

## How to see the bug

```bash
# Install dependencies
composer install

# Run all tests (1 test with 20 samples)
composer test
```

## Suggested fixes

Either:
```
--- vendor/symfony/serializer/Normalizer/AbstractObjectNormalizer.php
+++ vendor/symfony/serializer/Normalizer/AbstractObjectNormalizer.php
@@ -403,7 +403,7 @@
             } catch (PropertyAccessInvalidArgumentException $e) {
                 $exception = NotNormalizableValueException::createForUnexpectedDataType(
                     \sprintf('Failed to denormalize attribute "%s" value for class "%s": '.$e->getMessage(), $attribute, $resolvedClass),
-                    $data,
+                    $value,
                     $e instanceof InvalidTypeException ? [$e->expectedType] : ['unknown'],
                     $attributeContext['deserialization_path'] ?? null,
                     false,
```

or:
```
--- vendor/symfony/serializer/Normalizer/AbstractObjectNormalizer.php
+++ vendor/symfony/serializer/Normalizer/AbstractObjectNormalizer.php
@@ -403,7 +403,7 @@
             } catch (PropertyAccessInvalidArgumentException $e) {
                 $exception = NotNormalizableValueException::createForUnexpectedDataType(
                     \sprintf('Failed to denormalize attribute "%s" value for class "%s": '.$e->getMessage(), $attribute, $resolvedClass),
-                    $data,
+                    $data[$attribute] ?? $value,
                     $e instanceof InvalidTypeException ? [$e->expectedType] : ['unknown'],
                     $attributeContext['deserialization_path'] ?? null,
                     false,
```
