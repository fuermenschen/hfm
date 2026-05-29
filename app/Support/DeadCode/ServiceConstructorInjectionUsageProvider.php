<?php

declare(strict_types=1);

namespace App\Support\DeadCode;

use Livewire\Component;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use ReflectionMethod;
use ReflectionNamedType;
use ShipMonk\PHPStan\DeadCode\Graph\ClassMethodRef;
use ShipMonk\PHPStan\DeadCode\Graph\ClassMethodUsage;
use ShipMonk\PHPStan\DeadCode\Graph\UsageOrigin;
use ShipMonk\PHPStan\DeadCode\Provider\MemberUsageProvider;
use ShipMonk\PHPStan\DeadCode\Provider\VirtualUsageData;

class ServiceConstructorInjectionUsageProvider implements MemberUsageProvider
{
    // TODO(dead-code): Switch controller action detection to route-aware discovery so
    // only actually routed methods contribute constructor usages.

    /**
     * @return list<ClassMethodUsage>
     */
    public function getUsages(Node $node, Scope $scope): array
    {
        if (! $node instanceof InClassNode) { // @phpstan-ignore phpstanApi.instanceofAssumption
            return [];
        }

        $classReflection = $node->getClassReflection();
        $className = $classReflection->getName();

        if (! $this->isInjectionEntrypointClass($className, $classReflection->isSubclassOf(Component::class))) {
            return [];
        }

        $usages = [];

        foreach ($classReflection->getNativeReflection()->getMethods() as $method) {
            if (! $this->isInjectionEntrypointMethod($className, $method)) {
                continue;
            }

            foreach ($method->getParameters() as $parameter) {
                $type = $parameter->getType();
                if (! $type instanceof ReflectionNamedType) {
                    continue;
                }

                if ($type->isBuiltin()) {
                    continue;
                }

                $parameterClassName = $type->getName();

                if (! str_starts_with($parameterClassName, 'App\\Services\\')) {
                    continue;
                }

                $usages[] = new ClassMethodUsage(
                    UsageOrigin::createVirtual($this, VirtualUsageData::withNote('Service constructor resolved by Laravel container via typed method injection')),
                    new ClassMethodRef($parameterClassName, '__construct', possibleDescendant: false),
                );
            }
        }

        return $usages;
    }

    protected function isInjectionEntrypointClass(string $className, bool $isLivewireComponent): bool
    {
        if (str_starts_with($className, 'App\\Http\\Controllers\\')) {
            return true;
        }

        if (str_starts_with($className, 'App\\Jobs\\')) {
            return true;
        }

        return $isLivewireComponent;
    }

    protected function isInjectionEntrypointMethod(string $className, ReflectionMethod $method): bool
    {
        if ($method->isStatic()) {
            return false;
        }

        $methodName = $method->getName();

        if (str_starts_with($className, 'App\\Http\\Controllers\\')) {
            if (! $method->isPublic()) {
                return false;
            }

            return in_array($methodName, ['__invoke', 'index', 'create', 'store', 'show', 'edit', 'update', 'destroy'], true);
        }

        if (str_starts_with($className, 'App\\Jobs\\')) {
            return $methodName === 'handle';
        }

        return in_array($methodName, ['mount', 'boot'], true);
    }
}
