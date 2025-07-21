<?php

declare(strict_types=1);

namespace Crell\HttpTools;

use Crell\AttributeUtils\ParseParameters;
use Psr\Http\Message\ServerRequestInterface;

#[\Attribute(\Attribute::TARGET_FUNCTION | \Attribute::TARGET_METHOD)]
class ActionMetadataAttribute implements ActionMetadata, ParseParameters
{
    /**
     * @var array<string, string>
     */
    public private(set) array $parameterTypes = [];

    public private(set) ?string $parsedBodyParameter;
    public private(set) ?string $requestParameter;

    /**
     * @param array<string, ActionParameter> $parameters
     * @return void
     */
    public function setParameters(array $parameters): void
    {
        foreach ($parameters as $name => $p) {
            // It should be impossible for getSimpleType to return null here, as
            // TypeDef will error in ActionParameter if it's not a simple type.
            // But PHPStan doesn't know that.
            // @phpstan-ignore assign.propertyType (We know this cannot be null)
            $this->parameterTypes[$name] = $p->typeDef->getSimpleType();
        }
        $this->parsedBodyParameter = array_find_key($parameters, static fn(ActionParameter $p) => $p instanceof ParsedBody);

        $this->requestParameter = array_find_key($this->parameterTypes ?? [], static fn(?string $name, string $type) => is_a($type, ServerRequestInterface::class, true));
    }

    public function includeParametersByDefault(): bool
    {
        return true;
    }

    public function parameterAttribute(): string
    {
        return ActionParameter::class;
    }
}
