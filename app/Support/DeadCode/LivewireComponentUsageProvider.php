<?php

declare(strict_types=1);

namespace App\Support\DeadCode;

use Livewire\Component;
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

class LivewireComponentUsageProvider implements MemberUsageProvider
{
    // TODO(dead-code): Narrow to lifecycle + template-referenced members once Blade views
    // are included in static analysis or wire:* references are parsed structurally.

    /**
     * @var array<string, true>
     */
    private const VALIDATION_CALLBACKS = [
        'rules' => true,
        'messages' => true,
        'validationattributes' => true,
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

        if (! $classReflection->isSubclassOf(Component::class)) {
            return [];
        }

        $nativeReflection = $classReflection->getNativeReflection();
        $className = $classReflection->getName();
        $usages = [];

        foreach ($nativeReflection->getMethods() as $method) {
            if ($method->getDeclaringClass()->getName() !== $className) {
                continue;
            }

            if ($method->isStatic()) {
                continue;
            }

            if ($method->getName() === '__construct') {
                continue;
            }

            $isValidationCallback = $method->isProtected()
                && isset(self::VALIDATION_CALLBACKS[strtolower($method->getName())]);

            if (! $method->isPublic() && ! $isValidationCallback) {
                continue;
            }

            $usages[] = new ClassMethodUsage(
                UsageOrigin::createVirtual($this, VirtualUsageData::withNote(
                    $isValidationCallback
                        ? 'Livewire invokes protected validation callback'
                        : 'Livewire public component method can be called from template/lifecycle; Blade views are excluded from static analysis',
                )),
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
            $origin = UsageOrigin::createVirtual($this, VirtualUsageData::withNote('Livewire public component property can be read/written by hydration and wire:model'));

            $usages[] = new ClassPropertyUsage($origin, $propertyRef, AccessType::READ);
            $usages[] = new ClassPropertyUsage($origin, $propertyRef, AccessType::WRITE);
        }

        return $usages;
    }
}
