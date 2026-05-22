<?php

declare(strict_types=1);

namespace App\Support\DeadCode;

use App\Models\DonationEvent;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use ShipMonk\PHPStan\DeadCode\Graph\ClassMethodRef;
use ShipMonk\PHPStan\DeadCode\Graph\ClassMethodUsage;
use ShipMonk\PHPStan\DeadCode\Graph\UsageOrigin;
use ShipMonk\PHPStan\DeadCode\Provider\MemberUsageProvider;
use ShipMonk\PHPStan\DeadCode\Provider\VirtualUsageData;

class DonationEventContentUsageProvider implements MemberUsageProvider
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

        if ($classReflection->getName() !== DonationEvent::class) {
            return [];
        }

        return [
            new ClassMethodUsage(
                UsageOrigin::createVirtual($this, VirtualUsageData::withNote('DonationEvent content helper is used from Blade views excluded from static analysis')),
                new ClassMethodRef($classReflection->getName(), 'contentInlineMarkdown', possibleDescendant: false),
            ),
            new ClassMethodUsage(
                UsageOrigin::createVirtual($this, VirtualUsageData::withNote('DonationEvent content helper is used from Blade views excluded from static analysis')),
                new ClassMethodRef($classReflection->getName(), 'contentPlainText', possibleDescendant: false),
            ),
        ];
    }
}
