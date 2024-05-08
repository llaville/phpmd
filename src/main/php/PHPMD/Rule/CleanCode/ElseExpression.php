<?php
/**
 * This file is part of PHP Mess Detector.
 *
 * Copyright (c) Manuel Pichler <mapi@phpmd.org>.
 * All rights reserved.
 *
 * Licensed under BSD License
 * For full copyright and license information, please see the LICENSE file.
 * Redistributions of files must retain the above copyright notice.
 *
 * @author Manuel Pichler <mapi@phpmd.org>
 * @copyright Manuel Pichler. All rights reserved.
 * @license https://opensource.org/licenses/bsd-license.php BSD License
 * @link http://phpmd.org/
 */

namespace PHPMD\Rule\CleanCode;

use OutOfBoundsException;
use PDepend\Source\AST\ASTScopeStatement;
use PHPMD\AbstractNode;
use PHPMD\AbstractRule;
use PHPMD\Node\ASTNode;
use PHPMD\Rule\FunctionAware;
use PHPMD\Rule\MethodAware;

/**
 * Check if there is an else expression somewhere in the method/function and
 * warn about it.
 *
 * Object Calisthenics teaches us, that an else expression can always be
 * avoided by simple guard clause or return statements.
 */
class ElseExpression extends AbstractRule implements MethodAware, FunctionAware
{
    /**
     * This method checks if a method/function uses an else expression and add a violation for each one found.
     */
    public function apply(AbstractNode $node): void
    {
        foreach ($node->findChildrenOfType(ASTScopeStatement::class) as $scope) {
            $parent = $scope->getParent();

            if (!$parent) {
                continue;
            }

            if (!$this->isIfOrElseIfStatement($parent)) {
                continue;
            }

            if (!$this->isElseScope($scope, $parent)) {
                continue;
            }

            $this->addViolation($scope, [$node->getImage()]);
        }
    }

    /**
     * Whether the given scope is an else clause
     *
     * @return bool
     * @throws OutOfBoundsException
     */
    protected function isElseScope(AbstractNode $scope, ASTNode $parent)
    {
        return (
            count($parent->getChildren()) === 3 &&
            $scope->getNode() === $parent->getChild(2)->getNode()
        );
    }

    /**
     * Whether the parent node is an if or an elseif clause
     *
     * @return bool
     */
    protected function isIfOrElseIfStatement(ASTNode $parent)
    {
        return ($parent->getName() === "if" || $parent->getName() === "elseif");
    }
}
