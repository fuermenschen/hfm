<?php

declare(strict_types=1);

namespace App\Support\DeadCode;

use Illuminate\View\Component;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use ShipMonk\PHPStan\DeadCode\Enum\AccessType;
use ShipMonk\PHPStan\DeadCode\Graph\ClassMethodRef;
use ShipMonk\PHPStan\DeadCode\Graph\ClassMethodUsage;
use ShipMonk\PHPStan\DeadCode\Graph\ClassPropertyRef;
use ShipMonk\PHPStan\DeadCode\Graph\ClassPropertyUsage;
use ShipMonk\PHPStan\DeadCode\Graph\UsageOrigin;
use ShipMonk\PHPStan\DeadCode\Provider\MemberUsageProvider;
use ShipMonk\PHPStan\DeadCode\Provider\VirtualUsageData;

class BladeComponentUsageProvider implements MemberUsageProvider
{
    /**
     * @return list<ClassMethodUsage|ClassPropertyUsage>
     */
    public function getUsages(Node $node, Scope $scope): array
    {
        if (! $node instanceof InClassNode) { // @phpstan-ignore phpstanApi.instanceofAssumption
            return [];
        }

        $classReflection = $node->getClassReflection();

        if (! $classReflection->isSubclassOf(Component::class)) {
            return [];
        }

        $nativeReflection = $classReflection->getNativeReflection();
        $className = $classReflection->getName();
        $usages = [];

        if ($classReflection->hasNativeMethod('__construct')) {
            $usages[] = new ClassMethodUsage(
                UsageOrigin::createVirtual($this, VirtualUsageData::withNote('Blade component constructor can be invoked by Laravel component resolver')),
                new ClassMethodRef($className, '__construct', possibleDescendant: false),
            );
        }

        foreach ($nativeReflection->getMethods() as $method) {
            if ($method->getDeclaringClass()->getName() !== $className) {
                continue;
            }

            if (! $method->isPublic()) {
                continue;
            }

            if ($method->isStatic()) {
                continue;
            }

            if (in_array($method->getName(), ['__construct', 'render'], true)) {
                continue;
            }

            $usages[] = new ClassMethodUsage(
                UsageOrigin::createVirtual($this, VirtualUsageData::withNote('Blade component public method can be called from its view; Blade views are excluded from static analysis')),
                new ClassMethodRef($className, $method->getName(), possibleDescendant: false),
            );
        }

        foreach ($nativeReflection->getProperties() as $property) {
            if ($property->getDeclaringClass()->getName() !== $className) {
                continue;
            }

            if (! $property->isPublic()) {
                continue;
            }

            if ($property->isStatic()) {
                continue;
            }

            $propertyRef = new ClassPropertyRef($className, $property->getName(), possibleDescendant: false);
            $origin = UsageOrigin::createVirtual($this, VirtualUsageData::withNote('Blade component public property can be read by its view; Blade views are excluded from static analysis'));

            $usages[] = new ClassPropertyUsage($origin, $propertyRef, AccessType::READ);
            $usages[] = new ClassPropertyUsage($origin, $propertyRef, AccessType::WRITE);
        }

        return $usages;
    }
}
