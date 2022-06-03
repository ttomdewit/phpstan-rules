<?php

declare(strict_types=1);

namespace Tomdewit\PHPStanRules\Rules\Serialization;

use PhpParser\Node;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Reflection\Php\PhpPropertyReflection;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;

/**
 * @implements \PHPStan\Rules\Rule<ClassPropertyNode>
 */
final class OnlySerializeScalars implements Rule
{
    public function getNodeType(): string
    {
        return ClassPropertyNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $scope->getClassReflection();

        if ($classReflection === null) {
            return [];
        }

        if (!$classReflection->implementsInterface(Serializable::class)) {
            return [];
        }

        if (!$classReflection->hasNativeProperty($node->getName())) {
            return [];
        }

        /** @var PhpPropertyReflection $property */
        $property = $classReflection->getNativeProperty($node->getName());

        /** @var ObjectType $nativeType */
        $nativeType = $property->getNativeType();
        $isObject = (new ObjectWithoutClassType())->isSuperTypeOf($nativeType);

        if ($isObject->no()) {
            return [];
        }

        $className = $nativeType->getClassName();
        $variableName = $node->getName();

        if ($isObject->yes()) {
            return [
                sprintf(
                    'You should not serialise variable [$%s] of object [%s], serialise primitives/scalars instead.',
                    $variableName,
                    $className,
                ),
            ];
        }

        return ['???'];
    }
}
