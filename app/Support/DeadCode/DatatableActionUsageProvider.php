<?php

declare(strict_types=1);

namespace App\Support\DeadCode;

use App\Support\Datatable\Actions\Contracts\DatatableAction;
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

class DatatableActionUsageProvider implements MemberUsageProvider
{
    // TODO(dead-code): Replace blanket protected-property virtual usage with explicit
    // framework-consumed fields when DatatableAction runtime contract is formalized.

    /**
     * @var array<string, true>
     */
    private const CONTRACT_METHODS = [
        'key' => true,
        'group' => true,
        'label' => true,
        'permission' => true,
        'isvisible' => true,
        'isenabled' => true,
        'resolve' => true,
    ];

    /**
     * @return list<ClassMethodUsage|ClassPropertyUsage>
     */
    public function getUsages(Node $node, Scope $scope): array
    {
        if (! $node instanceof InClassNode) { // @phpstan-ignore phpstanApi.instanceofAssumption
            return [];
        }

        $classReflection = $node->getClassReflection();

        $isDatatableActionContract = $classReflection->getName() === DatatableAction::class;

        if (! $isDatatableActionContract && ! $classReflection->isSubclassOf(DatatableAction::class)) {
            return [];
        }

        $nativeReflection = $classReflection->getNativeReflection();
        $className = $classReflection->getName();
        $usages = [];

        if ($classReflection->hasNativeMethod('__construct')) {
            $usages[] = new ClassMethodUsage(
                UsageOrigin::createVirtual($this, VirtualUsageData::withNote('Datatable action object can be instantiated dynamically from table definitions')),
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

            if (! isset(self::CONTRACT_METHODS[strtolower($method->getName())])) {
                continue;
            }

            $usages[] = new ClassMethodUsage(
                UsageOrigin::createVirtual($this, VirtualUsageData::withNote('Datatable action contract methods are consumed by runtime table renderer')),
                new ClassMethodRef($className, $method->getName(), possibleDescendant: false),
            );
        }

        foreach ($nativeReflection->getProperties() as $property) {
            if ($property->getDeclaringClass()->getName() !== $className) {
                continue;
            }

            if (! $property->isProtected()) {
                continue;
            }

            if ($property->isStatic()) {
                continue;
            }

            $propertyRef = new ClassPropertyRef($className, $property->getName(), possibleDescendant: false);
            $origin = UsageOrigin::createVirtual($this, VirtualUsageData::withNote('Datatable action state is consumed indirectly via resolve() payload'));

            $usages[] = new ClassPropertyUsage($origin, $propertyRef, AccessType::READ);
            $usages[] = new ClassPropertyUsage($origin, $propertyRef, AccessType::WRITE);
        }

        return $usages;
    }
}
