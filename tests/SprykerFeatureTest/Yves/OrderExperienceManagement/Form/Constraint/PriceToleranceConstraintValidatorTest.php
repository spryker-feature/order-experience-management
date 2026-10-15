<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\OrderExperienceManagement\Form\Constraint;

use SprykerFeature\Yves\OrderExperienceManagement\Form\Constraint\PriceToleranceConstraint;
use SprykerFeature\Yves\OrderExperienceManagement\Form\Constraint\PriceToleranceConstraintValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Yves
 * @group OrderExperienceManagement
 * @group Form
 * @group Constraint
 * @group PriceToleranceConstraintValidatorTest
 * Add your own group annotations below this line
 */
class PriceToleranceConstraintValidatorTest extends ConstraintValidatorTestCase
{
    protected const string DECIMALS_MESSAGE = 'decimals message';

    protected const string STEP_MESSAGE = 'step message';

    public function testAcceptsABlankValue(): void
    {
        // Act
        $this->validator->validate('', $this->createConstraint(2, '0.01'));

        // Assert
        $this->assertNoViolation();
    }

    public function testAcceptsAValueThatIsAMultipleOfTheStep(): void
    {
        // Act
        $this->validator->validate('1.75', $this->createConstraint(2, '0.25'));

        // Assert
        $this->assertNoViolation();
    }

    public function testRejectsAValueThatIsNotAMultipleOfTheStep(): void
    {
        // Act
        $this->validator->validate('1.3', $this->createConstraint(2, '0.25'));

        // Assert
        $this->buildViolation(static::STEP_MESSAGE)->setParameter('{{ step }}', '0.25')->assertRaised();
    }

    public function testRejectsAValueWithMoreDecimalsThanTheScale(): void
    {
        // Act
        $this->validator->validate('1.234', $this->createConstraint(2, '0.01'));

        // Assert
        $this->buildViolation(static::DECIMALS_MESSAGE)->setParameter('{{ decimals }}', '2')->assertRaised();
    }

    public function testIgnoresTrailingZerosBeyondTheScale(): void
    {
        // Act
        $this->validator->validate('1.500', $this->createConstraint(2, '0.01'));

        // Assert
        $this->assertNoViolation();
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new PriceToleranceConstraintValidator();
    }

    protected function createConstraint(int $scale, string $step): PriceToleranceConstraint
    {
        return new PriceToleranceConstraint($scale, $step, static::DECIMALS_MESSAGE, static::STEP_MESSAGE);
    }
}
