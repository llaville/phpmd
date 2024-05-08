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

namespace PHPMD\Rule;

use OutOfBoundsException;
use PDepend\Source\AST\ASTArray;
use PDepend\Source\AST\ASTArrayElement;
use PDepend\Source\AST\ASTLiteral;
use PDepend\Source\AST\ASTMethodPostfix;
use PDepend\Source\AST\ASTSelfReference;
use PDepend\Source\AST\ASTVariable;
use PHPMD\AbstractNode;
use PHPMD\AbstractRule;
use PHPMD\Node\ASTNode;
use PHPMD\Node\ClassNode;
use PHPMD\Node\MethodNode;
use RuntimeException;

/**
 * This rule collects all private methods in a class that aren't used in any
 * method of the analyzed class.
 */
class UnusedPrivateMethod extends AbstractRule implements ClassAware
{
    /**
     * This method checks that all private class methods are at least accessed
     * by one method.
     *
     * @throws RuntimeException
     */
    public function apply(AbstractNode $class): void
    {
        if (!$class instanceof ClassNode) {
            return;
        }

        foreach ($this->collectUnusedPrivateMethods($class) as $node) {
            $this->addViolation($node, [$node->getImage()]);
        }
    }

    /**
     * This method collects all methods in the given class that are declared
     * as private and are not used in the same class' context.
     *
     * @return array<string, MethodNode>
     * @throws OutOfBoundsException
     * @throws RuntimeException
     */
    protected function collectUnusedPrivateMethods(ClassNode $class)
    {
        $methods = $this->collectPrivateMethods($class);

        return $this->removeUsedMethods($class, $methods);
    }

    /**
     * Collects all private methods declared in the given class node.
     *
     * @return array<string, MethodNode>
     * @throws RuntimeException
     */
    protected function collectPrivateMethods(ClassNode $class)
    {
        $methods = [];

        foreach ($class->getMethods() as $method) {
            if ($this->acceptMethod($class, $method)) {
                $methods[strtolower($method->getImage())] = $method;
            }
        }

        return $methods;
    }

    /**
     * Returns <b>true</b> when the given method should be used for this rule's
     * analysis.
     *
     * @return bool
     * @throws RuntimeException
     */
    protected function acceptMethod(ClassNode $class, MethodNode $method)
    {
        return (
            $method->isPrivate() &&
            !$method->hasSuppressWarningsAnnotationFor($this) &&
            strcasecmp($method->getImage(), $class->getImage()) !== 0 &&
            strcasecmp($method->getImage(), '__construct') !== 0 &&
            strcasecmp($method->getImage(), '__destruct') !== 0 &&
            strcasecmp($method->getImage(), '__clone') !== 0
        );
    }

    /**
     * This method removes all used methods from the given methods array.
     *
     * @param array<string, MethodNode> $methods
     * @return array<string, MethodNode>
     * @throws OutOfBoundsException
     */
    protected function removeUsedMethods(ClassNode $class, array $methods)
    {
        $methods = $this->removeExplicitCalls($class, $methods);
        $methods = $this->removeCallableArrayRepresentations($class, $methods);

        return $methods;
    }

    /**
     * $this->privateMethod() makes "privateMethod" marked as used as an explicit call.
     *
     * @param array<string, MethodNode> $methods
     * @return array<string, MethodNode>
     * @throws OutOfBoundsException
     */
    protected function removeExplicitCalls(ClassNode $class, array $methods)
    {
        foreach ($class->findChildrenOfType(ASTMethodPostfix::class) as $postfix) {
            if ($this->isClassScope($class, $postfix)) {
                unset($methods[strtolower($postfix->getImage())]);
            }
        }

        return $methods;
    }

    /**
     * [$this 'privateMethod'] makes "privateMethod" marked as used as very likely to be used as a callable value.
     *
     * @param array<string, MethodNode> $methods
     * @return array<string, MethodNode>
     * @throws OutOfBoundsException
     */
    protected function removeCallableArrayRepresentations(ClassNode $class, array $methods)
    {
        foreach ($class->findChildrenOfType(ASTVariable::class) as $variable) {
            $parent = $variable->getParent();
            if ($parent && $this->isClassScope($class, $variable) && $variable->getImage() === '$this') {
                $method = $this->getMethodNameFromArraySecondElement($parent);

                if ($method) {
                    unset($methods[strtolower($method)]);
                }
            }
        }

        return $methods;
    }

    /**
     * Return represented method name if the given element is a 2-items array
     * and that the second one is a literal static string.
     *
     * @return string|null
     * @throws OutOfBoundsException
     */
    protected function getMethodNameFromArraySecondElement(AbstractNode $parent)
    {
        if ($parent->isInstanceOf(ASTArrayElement::class)) {
            $array = $parent->getParent();

            if ($array?->isInstanceOf(ASTArray::class)
                && count($array->getChildren()) === 2
            ) {
                $secondElement = $array->getChild(1)->getChild(0);

                if ($secondElement->isInstanceOf(ASTLiteral::class)) {
                    return substr($secondElement->getImage(), 1, -1);
                }
            }
        }

        return null;
    }

    /**
     * This method checks that the given method postfix is accessed on an
     * instance or static reference to the given class.
     *
     * @return bool
     * @throws OutOfBoundsException
     */
    protected function isClassScope(ClassNode $class, ASTNode $postfix)
    {
        $owner = $postfix->getParent()->getChild(0);

        return (
            $owner->isInstanceOf(ASTMethodPostfix::class) ||
            $owner->isInstanceOf(ASTSelfReference::class) ||
            strcasecmp($owner->getImage(), '$this') === 0 ||
            strcasecmp($owner->getImage(), $class->getImage()) === 0
        );
    }
}
