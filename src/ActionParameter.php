<?php

declare(strict_types=1);

namespace Crell\Carica;

use Crell\AttributeUtils\FromReflectionParameter;
use Crell\AttributeUtils\TypeDef;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class ActionParameter implements FromReflectionParameter
{
    protected(set) string $phpName;

    protected(set) TypeDef $typeDef;

    public function fromReflection(\ReflectionParameter $subject): void
    {
        $this->phpName = $subject->getName();

        $this->typeDef = new TypeDef($subject->getType());
        if (!$this->typeDef->isSimple()) {
            // @todo Better exception.
            throw new \InvalidArgumentException('Action parameters must be simple types. Union and intersection types are not supported.');
        }
    }
}
