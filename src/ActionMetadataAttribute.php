<?php

declare(strict_types=1);

namespace Crell\HttpTools;

use Crell\AttributeUtils\HasSubAttributes;
use Crell\AttributeUtils\ParseParameters;
use Psr\Http\Message\ServerRequestInterface;

use function Crell\fp\afilter;
use function Crell\fp\amapWithKeys;
use function Crell\fp\pipe;
use function Crell\fp\prop;

#[\Attribute(\Attribute::TARGET_FUNCTION | \Attribute::TARGET_METHOD)]
class ActionMetadataAttribute implements ActionMetadata, ParseParameters, HasSubAttributes
{
    /**
     * @var array<string, string>
     */
    private(set) array $parameterTypes = [];

    private(set) ?string $parsedBodyParameter;
    private(set) ?string $requestParameter;

    /**
     * @var array<class-string|string>
     */
    private(set) array $additionalMiddleware = [];

    /**
     * @var array<string, string>
     */
    private(set) array $requestAttributes = [];

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

        $requestFinder = static fn(string $type, string $name) => is_a($type, ServerRequestInterface::class, true);

        $this->parsedBodyParameter = array_find_key($parameters, static fn(ActionParameter $p) => $p instanceof ParsedBody);
        $this->requestParameter = array_find_key($this->parameterTypes ?? [], $requestFinder);

        $this->requestAttributes = pipe(
            $parameters,
            afilter(static fn(ActionParameter $p) => $p instanceof RequestAttribute),
            amapWithKeys(static fn(RequestAttribute $param, string $name) => $param->name ?? $name),
        );
    }

    public function subAttributes(): array
    {
        return [
            Middleware::class => $this->setAdditionalMiddleware(...),
        ];
    }

    /**
     * @param array<string|class-string> $middleware
     */
    private function setAdditionalMiddleware(array $middleware): void
    {
        $this->additionalMiddleware = array_map(prop('name'), $middleware);
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
