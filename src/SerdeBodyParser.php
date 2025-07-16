<?php

declare(strict_types=1);

namespace Crell\HttpTools;

use Crell\Serde\InvalidArrayKeyType;
use Crell\Serde\MissingRequiredValueWhenDeserializing;
use Crell\Serde\Serde;
use Crell\Serde\TypeMismatch;

class SerdeBodyParser implements BodyParser
{
    public function __construct(
        private Serde $serde,
    ) {}

    public function canParse(string $contentType, string $className): bool
    {
        return class_exists($className) && in_array($contentType, ['application/json', 'application/yaml', 'application/toml', 'text/csv']);
    }

    public function parse(string $contentType, string $unparsed, string $className): object
    {
        try {
            return match ($contentType) {
                'application/json' => $this->serde->deserialize($unparsed, from: 'json', to: $className),
                'application/yaml' => $this->serde->deserialize($unparsed, from: 'yaml', to: $className),
                'application/toml' => $this->serde->deserialize($unparsed, from: 'toml', to: $className),
                'text/csv' => $this->serde->deserialize($unparsed, from: 'csv', to: $className),
                default => new BodyParserError(sprintf('Unhandleable content-type: %s', $contentType)),
            };
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
    }
}
