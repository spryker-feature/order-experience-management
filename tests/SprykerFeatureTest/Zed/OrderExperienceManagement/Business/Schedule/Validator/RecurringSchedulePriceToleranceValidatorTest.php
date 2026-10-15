<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Schedule\Validator;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Spryker\DecimalObject\Decimal;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\RecurringSchedulePriceToleranceValidator;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Schedule
 * @group Validator
 * @group RecurringSchedulePriceToleranceValidatorTest
 * Add your own group annotations below this line
 */
class RecurringSchedulePriceToleranceValidatorTest extends Unit
{
    protected const string GLOSSARY_KEY_OUT_OF_RANGE = 'recurring_orders.price_tolerance.error.out_of_range';

    public function testAcceptsATolerancePastTheDefaultLimitWhenTheConfigRaisesIt(): void
    {
        // Arrange
        $validator = $this->createValidator('2000', 2);

        // Act
        $errorTransfer = $validator->findError($this->createRequest('1500'), [], $this->createExistingSchedule());

        // Assert
        $this->assertNull($errorTransfer);
    }

    public function testRejectsATolerancePastTheLimitOfTheConfig(): void
    {
        // Arrange
        $validator = $this->createValidator('10', 2);

        // Act
        $errorTransfer = $validator->findError($this->createRequest('10.5'), [], $this->createExistingSchedule());

        // Assert
        $this->assertSame(static::GLOSSARY_KEY_OUT_OF_RANGE, $errorTransfer->getMessage());
        $this->assertSame(['%max%' => '10'], $errorTransfer->getParameters());
    }

    public function testRejectsMoreDecimalsThanTheConfigAllows(): void
    {
        // Arrange
        $validator = $this->createValidator('100', 1);

        // Act
        $errorTransfer = $validator->findError($this->createRequest('2.25'), [], $this->createExistingSchedule());

        // Assert
        $this->assertSame('recurring_orders.price_tolerance.error.too_precise', $errorTransfer->getMessage());
        $this->assertSame(['%decimals%' => '1'], $errorTransfer->getParameters());
    }

    public function testAcceptsAToleranceThatIsAMultipleOfTheConfiguredStep(): void
    {
        // Arrange
        $validator = $this->createValidator('100', 2, '0.25');

        // Act
        $errorTransfer = $validator->findError($this->createRequest('1.75'), [], $this->createExistingSchedule());

        // Assert
        $this->assertNull($errorTransfer);
    }

    public function testRejectsAToleranceThatIsNotAMultipleOfTheConfiguredStep(): void
    {
        // Arrange
        $validator = $this->createValidator('100', 2, '0.25');

        // Act
        $errorTransfer = $validator->findError($this->createRequest('1.3'), [], $this->createExistingSchedule());

        // Assert
        $this->assertSame('recurring_orders.price_tolerance.error.not_in_step', $errorTransfer->getMessage());
        $this->assertSame(['%step%' => '0.25'], $errorTransfer->getParameters());
    }

    protected function createValidator(string $maxPercent, int $scale, string $step = '0.01'): RecurringSchedulePriceToleranceValidator
    {
        $configMock = $this->createMock(OrderExperienceManagementConfig::class);
        $configMock->method('getPriceToleranceMaxPercent')->willReturn($maxPercent);
        $configMock->method('getPriceToleranceScale')->willReturn($scale);
        $configMock->method('getPriceToleranceStep')->willReturn($step);

        return new RecurringSchedulePriceToleranceValidator($configMock);
    }

    protected function createRequest(string $orderTolerance): RecurringScheduleTransfer
    {
        return (new RecurringScheduleTransfer())->setPriceTolerancePercent(Decimal::create($orderTolerance));
    }

    protected function createExistingSchedule(): RecurringScheduleTransfer
    {
        return (new RecurringScheduleTransfer())->addItem((new RecurringScheduleItemTransfer())->setGroupKey('group-a'));
    }
}
