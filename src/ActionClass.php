<?php

declare(strict_types=1);

namespace Crell\Carica;

use Crell\AttributeUtils\ParseMethods;

/**
 * This exists only to allow AttributeUtils to access a method that will be an action.
 */
class ActionClass implements ParseMethods
{
    /**
     * @var array<string, ActionMetadataAttribute>
     */
    protected(set) array $methods;

    /**
     * @param array<string, ActionMetadataAttribute> $methods
     */
    public function setMethods(array $methods): void
    {
        $this->methods = $methods;
    }

    public function includeMethodsByDefault(): bool
    {
        return true;
    }

    public function methodAttribute(): string
    {
        return ActionMetadataAttribute::class;
    }
}
