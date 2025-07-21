<?php

declare(strict_types=1);

namespace Crell\HttpTools;

use Crell\AttributeUtils\ParseParameters;

#[\Attribute(\Attribute::TARGET_FUNCTION | \Attribute::TARGET_METHOD)]
class ActionMetadata implements ParseParameters
{
    public private(set) ?string $parsedBodyParameter;

    /**
     * @var array<string, string|null>|null
     */
    public private(set) ?array $parameterTypes;

    /**
     * @param array<string, ActionParameter> $parameters
     * @return void
     */
    public function setParameters(array $parameters): void
    {
        foreach ($parameters as $name => $p) {
            $this->parameterTypes[$name] = $p->typeDef->getSimpleType();
        }
        $this->parsedBodyParameter = array_find_key($parameters, static fn(ActionParameter $p) => $p instanceof ParsedBody);
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
