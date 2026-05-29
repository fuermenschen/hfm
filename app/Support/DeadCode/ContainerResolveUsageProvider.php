<?php

declare(strict_types=1);

namespace App\Support\DeadCode;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use ShipMonk\PHPStan\DeadCode\Graph\ClassMethodRef;
use ShipMonk\PHPStan\DeadCode\Graph\ClassMethodUsage;
use ShipMonk\PHPStan\DeadCode\Graph\UsageOrigin;
use ShipMonk\PHPStan\DeadCode\Provider\MemberUsageProvider;

class ContainerResolveUsageProvider implements MemberUsageProvider
{
    /**
     * @return list<ClassMethodUsage>
     */
    public function getUsages(Node $node, Scope $scope): array
    {
        if (! $node instanceof FuncCall) {
            return [];
        }

        $usages = [];

        foreach ($this->extractResolvedClassNames($node, $scope) as $className) {
            $usages[] = new ClassMethodUsage(
                UsageOrigin::createRegular($node, $scope),
                new ClassMethodRef($className, '__construct', possibleDescendant: false),
            );
        }

        if ($node->name instanceof FuncCall) {
            foreach ($this->extractResolvedClassNames($node->name, $scope) as $className) {
                foreach (['__construct', '__invoke'] as $methodName) {
                    $usages[] = new ClassMethodUsage(
                        UsageOrigin::createRegular($node, $scope),
                        new ClassMethodRef($className, $methodName, possibleDescendant: false),
                    );
                }
            }
        }

        return $usages;
    }

    /**
     * @return list<string>
     */
    protected function extractResolvedClassNames(FuncCall $node, Scope $scope): array
    {
        if (! $node->name instanceof Name) {
            return [];
        }

        $functionName = strtolower($node->name->toString());

        if (! in_array($functionName, ['app', 'resolve'], true)) {
            return [];
        }

        $args = $node->getArgs();

        if (! isset($args[0])) {
            return [];
        }

        return $this->constantStrings($args[0], $scope);
    }

    /**
     * @return list<string>
     */
    protected function constantStrings(Arg $arg, Scope $scope): array
    {
        $strings = [];

        foreach ($scope->getType($arg->value)->getConstantStrings() as $constantString) {
            $strings[] = $constantString->getValue();
        }

        return $strings;
    }
}
