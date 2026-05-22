<?php

declare(strict_types=1);

namespace App\Support\DeadCode;

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
use Spatie\LaravelSettings\Settings;

class SpatieSettingsUsageProvider implements MemberUsageProvider
{
    /**
     * @var array<string, true>
     */
    private const DYNAMIC_STATIC_METHODS = [
        'group' => true,
        'encrypted' => true,
        'settingsdetails' => true,
        'rules' => true,
        'titles' => true,
        'descriptions' => true,
        'options' => true,
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

        if (! $classReflection->isSubclassOf(Settings::class)) {
            return [];
        }

        $nativeReflection = $classReflection->getNativeReflection();
        $className = $classReflection->getName();
        $usages = [];

        foreach ($nativeReflection->getMethods() as $method) {
            if ($method->getDeclaringClass()->getName() !== $className) {
                continue;
            }

            if (! $method->isPublic()) {
                continue;
            }

            if (! $method->isStatic()) {
                continue;
            }

            if (! isset(self::DYNAMIC_STATIC_METHODS[strtolower($method->getName())])) {
                continue;
            }

            $usages[] = new ClassMethodUsage(
                UsageOrigin::createVirtual($this, VirtualUsageData::withNote('Spatie settings metadata method can be used via runtime introspection')),
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
            $origin = UsageOrigin::createVirtual($this, VirtualUsageData::withNote('Spatie settings public properties are hydrated/persisted dynamically'));

            $usages[] = new ClassPropertyUsage($origin, $propertyRef, AccessType::READ);
            $usages[] = new ClassPropertyUsage($origin, $propertyRef, AccessType::WRITE);
        }

        return $usages;
    }
}
