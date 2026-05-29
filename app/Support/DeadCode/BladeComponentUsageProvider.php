<?php

declare(strict_types=1);

namespace App\Support\DeadCode;

use Illuminate\View\Component;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use ShipMonk\PHPStan\DeadCode\Graph\ClassMethodRef;
use ShipMonk\PHPStan\DeadCode\Graph\ClassMethodUsage;
use ShipMonk\PHPStan\DeadCode\Graph\UsageOrigin;
use ShipMonk\PHPStan\DeadCode\Provider\MemberUsageProvider;
use ShipMonk\PHPStan\DeadCode\Provider\VirtualUsageData;

class BladeComponentUsageProvider implements MemberUsageProvider
{
    /**
     * @return list<ClassMethodUsage>
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

        if (! $classReflection->hasNativeMethod('__construct')) {
            return [];
        }

        return [
            new ClassMethodUsage(
                UsageOrigin::createVirtual($this, VirtualUsageData::withNote('Blade component constructor can be invoked by Laravel component resolver')),
                new ClassMethodRef($classReflection->getName(), '__construct', possibleDescendant: false),
            ),
        ];
    }
}
