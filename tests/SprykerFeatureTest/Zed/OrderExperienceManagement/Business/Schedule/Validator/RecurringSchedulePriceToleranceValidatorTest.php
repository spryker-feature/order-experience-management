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
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
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

    protected const string GLOSSARY_KEY_ITEM_NOT_FOUND = 'recurring_orders.price_tolerance.error.item_not_found';

    protected const string GLOSSARY_KEY_IGNORE_NEEDS_ORDER_TOLERANCE = 'recurring_orders.price_tolerance.error.ignore_requires_order_tolerance';

    protected const string GLOSSARY_KEY_LOCKED_IN_REVIEW = 'recurring_orders.price_tolerance.locked_notice';

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
        $this->assertNotNull($errorTransfer);
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
        $this->assertNotNull($errorTransfer);
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
        $this->assertNotNull($errorTransfer);
        $this->assertSame('recurring_orders.price_tolerance.error.not_in_step', $errorTransfer->getMessage());
        $this->assertSame(['%step%' => '0.25'], $errorTransfer->getParameters());
    }

    public function testAcceptsARequestThatChangesNoTolerance(): void
    {
        // Arrange
        $validator = $this->createValidator('100', 2);

        // Act
        $errorTransfer = $validator->findError(new RecurringScheduleTransfer(), [], $this->createExistingSchedule());

        // Assert
        $this->assertNull($errorTransfer);
    }

    public function testRejectsAnItemToleranceForAnItemThatIsNotInTheSchedule(): void
    {
        // Arrange
        $validator = $this->createValidator('100', 2);
        $requestedItemTransfer = (new RecurringScheduleItemTransfer())
            ->setGroupKey('group-unknown')
            ->setPriceTolerancePercent(Decimal::create('1'));

        // Act
        $errorTransfer = $validator->findError(new RecurringScheduleTransfer(), [$requestedItemTransfer], $this->createExistingSchedule());

        // Assert
        $this->assertNotNull($errorTransfer);
        $this->assertSame(static::GLOSSARY_KEY_ITEM_NOT_FOUND, $errorTransfer->getMessage());
    }

    public function testRejectsIgnoringPriceChangesWithoutAPositiveOrderTolerance(): void
    {
        // Arrange
        $validator = $this->createValidator('100', 2);
        $requestedScheduleTransfer = (new RecurringScheduleTransfer())->setIsProductPriceChangeIgnored(true);

        // Act
        $errorTransfer = $validator->findError($requestedScheduleTransfer, [], $this->createExistingSchedule());

        // Assert
        $this->assertNotNull($errorTransfer);
        $this->assertSame(static::GLOSSARY_KEY_IGNORE_NEEDS_ORDER_TOLERANCE, $errorTransfer->getMessage());
    }

    public function testRejectsAToleranceChangeWhileTheScheduleIsLockedInReview(): void
    {
        // Arrange
        $validator = $this->createValidator('100', 2);
        $existingScheduleTransfer = $this->createExistingSchedule()->setStatus(SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED);

        // Act
        $errorTransfer = $validator->findError($this->createRequest('1.5'), [], $existingScheduleTransfer);

        // Assert
        $this->assertNotNull($errorTransfer);
        $this->assertSame(static::GLOSSARY_KEY_LOCKED_IN_REVIEW, $errorTransfer->getMessage());
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
