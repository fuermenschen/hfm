<?php

declare(strict_types=1);

namespace App\Support\DeadCode;

use App\Enums\EventState;
use App\Models\DonationEvent;
use App\Models\Partner;
use App\Models\Sponsor;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use ShipMonk\PHPStan\DeadCode\Graph\ClassMethodRef;
use ShipMonk\PHPStan\DeadCode\Graph\ClassMethodUsage;
use ShipMonk\PHPStan\DeadCode\Graph\UsageOrigin;
use ShipMonk\PHPStan\DeadCode\Provider\MemberUsageProvider;
use ShipMonk\PHPStan\DeadCode\Provider\VirtualUsageData;

class BladeViewUsageProvider implements MemberUsageProvider
{
    /**
     * @var array<class-string, list<string>>
     */
    private const METHODS = [
        DonationEvent::class => ['contentInlineMarkdown', 'contentPlainText'],
        EventState::class => ['label'],
        Partner::class => ['logoLightUrl', 'logoDarkUrl'],
        Sponsor::class => ['logoUrl'],
    ];

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

        if (! isset(self::METHODS[$className])) {
            return [];
        }

        return array_map(fn (string $method): ClassMethodUsage => new ClassMethodUsage(
            UsageOrigin::createVirtual($this, VirtualUsageData::withNote('Method is used from Blade views excluded from static analysis')),
            new ClassMethodRef($className, $method, possibleDescendant: false),
        ), self::METHODS[$className]);
    }
}
