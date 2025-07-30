<?php

declare(strict_types=1);

namespace Crell\Carica;

use Crell\Serde\InvalidArrayKeyType;
use Crell\Serde\MissingRequiredValueWhenDeserializing;
use Crell\Serde\Serde;
use Crell\Serde\TypeMismatch;

class SerdeBodyParser implements BodyParser
{
    private const array TypeMap = [
        'application/json' => 'json',
        'application/yaml' => 'yaml',
        'application/toml' => 'toml',
        'text/csv' => 'csv',
        BodyParser::PhpArrayType => 'array',
    ];

    public function __construct(
        private readonly Serde $serde,
    ) {}

    public function canParse(string $contentType, string $className): bool
    {
        return class_exists($className) && array_key_exists($contentType, self::TypeMap);
    }

    public function parse(string $contentType, string|array $unparsed, string $className): object
    {
        try {
            return $this->serde->deserialize($unparsed, from: self::TypeMap[$contentType], to: $className);
        // There's no need to manufacture these test cases right now.
        // @codeCoverageIgnoreStart
        } catch (MissingRequiredValueWhenDeserializing $e) {
            // @todo Log with more details.
            return new BodyParserError(sprintf('The %s property is required.', $e->name));
        } catch (TypeMismatch $e) {
            // @todo Log with more details.
            return new BodyParserError(sprintf('The %s property has an invalid type.', $e->name));
        } catch (InvalidArrayKeyType $e) {
            // @todo Log with more details.
            return new BodyParserError(sprintf('The %s property keys are invalid.', $e->field->serializedName));
        }
        // @codeCoverageIgnoreEnd
    }
}
