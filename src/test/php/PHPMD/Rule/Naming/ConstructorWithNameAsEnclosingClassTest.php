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

namespace PHPMD\Rule\Naming;

use PHPMD\AbstractTestCase;

/**
 * Test case for the constructor name rule.
 *
 * @covers \PHPMD\Rule\Naming\ConstructorWithNameAsEnclosingClass
 */
class ConstructorWithNameAsEnclosingClassTest extends AbstractTestCase
{
    /**
     * testRuleAppliesToConstructorMethodNamedAsEnclosingClass
     */
    public function testRuleAppliesToConstructorMethodNamedAsEnclosingClass(): void
    {
        $rule = new ConstructorWithNameAsEnclosingClass();
        $rule->setReport($this->getReportWithOneViolation());
        $rule->apply($this->getMethod());
    }

    /**
     * testRuleAppliesToConstructorMethodNamedAsEnclosingClassCaseInsensitive
     */
    public function testRuleAppliesToConstructorMethodNamedAsEnclosingClassCaseInsensitive(): void
    {
        $rule = new ConstructorWithNameAsEnclosingClass();
        $rule->setReport($this->getReportWithOneViolation());
        $rule->apply($this->getMethod());
    }

    /**
     * testRuleNotAppliesToMethodNamedSimilarToEnclosingClass
     */
    public function testRuleNotAppliesToMethodNamedSimilarToEnclosingClass(): void
    {
        $rule = new ConstructorWithNameAsEnclosingClass();
        $rule->setReport($this->getReportWithNoViolation());
        $rule->apply($this->getMethod());
    }

    public function testRuleNotAppliesToMethodNamedAsEnclosingInterface(): void
    {
        $rule = new ConstructorWithNameAsEnclosingClass();
        $rule->setReport($this->getReportWithNoViolation());
        $rule->apply($this->getMethod());
    }

    public function testRuleNotAppliesToMethodInNamespaces(): void
    {
        $rule = new ConstructorWithNameAsEnclosingClass();
        $rule->setReport($this->getReportWithNoViolation());
        $rule->apply($this->getMethod());
    }
}
