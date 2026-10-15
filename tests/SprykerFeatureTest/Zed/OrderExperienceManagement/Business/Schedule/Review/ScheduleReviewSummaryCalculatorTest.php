<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Schedule\Review;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleOrderReviewTransfer;
use Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\ScheduleReviewSummaryCalculator;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Schedule
 * @group Review
 * @group ScheduleReviewSummaryCalculatorTest
 */
class ScheduleReviewSummaryCalculatorTest extends Unit
{
    public function testUpdatedTotalIsUnchangedWhenThereIsNoOrderReview(): void
    {
        // Arrange
        $recurringScheduleReviewResponseTransfer = $this->createResponse()
            ->addUnchangedItem($this->createScheduleItem(1, 500))
            ->addFlaggedItem($this->createFlaggedItem(1, 500, 500, 600));

        // Act
        $recurringScheduleReviewResponseTransfer = $this->calculate($recurringScheduleReviewResponseTransfer);

        // Assert
        $this->assertSame(1000, $recurringScheduleReviewResponseTransfer->getOriginalTotal());
        $this->assertSame(1100, $recurringScheduleReviewResponseTransfer->getUpdatedTotal());
    }

    public function testUpdatedTotalCarriesTheWholeOrderRiseWhenNoItemIsFlagged(): void
    {
        // Arrange: 2 x 500 + 300 became 2 x 510 + 300
        $recurringScheduleReviewResponseTransfer = $this->createResponse()
            ->addUnchangedItem($this->createScheduleItem(2, 500))
            ->addUnchangedItem($this->createScheduleItem(1, 300))
            ->setOrderReview($this->createOrderReview(1300, 1320));

        // Act
        $recurringScheduleReviewResponseTransfer = $this->calculate($recurringScheduleReviewResponseTransfer);

        // Assert
        $this->assertSame(1300, $recurringScheduleReviewResponseTransfer->getOriginalTotal());
        $this->assertSame(1320, $recurringScheduleReviewResponseTransfer->getUpdatedTotal());
    }

    public function testFlaggedPriceRiseIsNotCountedTwice(): void
    {
        // Arrange: item A is flagged 500 -> 600, item B drifted 300 -> 320 without being flagged; the order went 800 -> 920
        $recurringScheduleReviewResponseTransfer = $this->createResponse()
            ->addFlaggedItem($this->createFlaggedItem(1, 500, 500, 600))
            ->addUnchangedItem($this->createScheduleItem(1, 300))
            ->setOrderReview($this->createOrderReview(800, 920));

        // Act
        $recurringScheduleReviewResponseTransfer = $this->calculate($recurringScheduleReviewResponseTransfer);

        // Assert
        $this->assertSame(800, $recurringScheduleReviewResponseTransfer->getOriginalTotal());
        $this->assertSame(920, $recurringScheduleReviewResponseTransfer->getUpdatedTotal());
    }

    public function testFlaggedQuantityIsAppliedToTheAlreadyCountedRise(): void
    {
        // Arrange: 3 x 100 -> 3 x 120 is flagged and accounts for the whole +60
        $recurringScheduleReviewResponseTransfer = $this->createResponse()
            ->addFlaggedItem($this->createFlaggedItem(3, 100, 100, 120))
            ->setOrderReview($this->createOrderReview(300, 360));

        // Act
        $recurringScheduleReviewResponseTransfer = $this->calculate($recurringScheduleReviewResponseTransfer);

        // Assert
        $this->assertSame(360, $recurringScheduleReviewResponseTransfer->getUpdatedTotal());
    }

    public function testUnpurchasableFlaggedItemDoesNotReduceTheCarriedRise(): void
    {
        // Arrange: the unavailable item was left out of the order-level totals, so it has no rise to subtract
        $unavailableItemReviewTransfer = $this->createFlaggedItem(1, 400, 400, 450)->setIsPurchasable(false);
        $recurringScheduleReviewResponseTransfer = $this->createResponse()
            ->addFlaggedItem($unavailableItemReviewTransfer)
            ->addUnchangedItem($this->createScheduleItem(1, 300))
            ->setOrderReview($this->createOrderReview(300, 330));

        // Act
        $recurringScheduleReviewResponseTransfer = $this->calculate($recurringScheduleReviewResponseTransfer);

        // Assert: 300 (unchanged item) + 30 (rise on it), the unavailable item is excluded from the updated total
        $this->assertSame(330, $recurringScheduleReviewResponseTransfer->getUpdatedTotal());
    }

    public function testUnflaggedPriceChangeIsCountedAgainstTheReferencePriceWhenANextDeliveryPriceIsSet(): void
    {
        // Arrange: reference 1000, an accepted next delivery price of 1100 and a current price of 1120
        $scheduleItemTransfer = $this->createScheduleItem(1, 1000)->setNextDeliveryGrossPrice(1100);
        $recurringScheduleReviewResponseTransfer = $this->createResponse()
            ->addUnchangedItem($scheduleItemTransfer)
            ->addUnflaggedPriceChange(
                (new RecurringScheduleItemReviewTransfer())
                    ->setRecurringScheduleItem($scheduleItemTransfer)
                    ->setPreviousPrice(1100)
                    ->setCurrentPrice(1120),
            );

        // Act
        $recurringScheduleReviewResponseTransfer = $this->calculate($recurringScheduleReviewResponseTransfer);

        // Assert
        $this->assertSame(1120, $recurringScheduleReviewResponseTransfer->getUpdatedTotal());
    }

    public function testFlaggedItemWithoutAPriceDoesNotReduceTheCarriedRise(): void
    {
        // Arrange: the order total checker did not count the item without a price either
        $recurringScheduleReviewResponseTransfer = $this->createResponse()
            ->addUnchangedItem($this->createScheduleItem(1, 300))
            ->addFlaggedItem($this->createFlaggedItem(1, 100, 100, 0))
            ->setOrderReview($this->createOrderReview(300, 330));

        // Act
        $recurringScheduleReviewResponseTransfer = $this->calculate($recurringScheduleReviewResponseTransfer);

        // Assert
        $this->assertSame(330, $recurringScheduleReviewResponseTransfer->getUpdatedTotal());
    }

    public function testTotalChangePercentIsSignedAndRoundedToTwoDecimals(): void
    {
        // Arrange
        $recurringScheduleReviewResponseTransfer = $this->createResponse()
            ->addUnchangedItem($this->createScheduleItem(1, 43322))
            ->setOrderReview($this->createOrderReview(43322, 50822));

        // Act
        $recurringScheduleReviewResponseTransfer = $this->calculate($recurringScheduleReviewResponseTransfer);

        // Assert
        $this->assertSame('+17.31', $recurringScheduleReviewResponseTransfer->getTotalChangePercent());
    }

    public function testTotalChangePercentIsNegativeWhenTheTotalFell(): void
    {
        // Arrange
        $recurringScheduleReviewResponseTransfer = $this->createResponse()
            ->addUnchangedItem($this->createScheduleItem(1, 1000))
            ->addFlaggedItem($this->createFlaggedItem(1, 1000, 1000, 900)->setIsPurchasable(false));

        // Act
        $recurringScheduleReviewResponseTransfer = $this->calculate($recurringScheduleReviewResponseTransfer);

        // Assert
        $this->assertSame('-50', $recurringScheduleReviewResponseTransfer->getTotalChangePercent());
    }

    public function testTotalChangePercentIsZeroWhenTheTotalIsUnchanged(): void
    {
        // Arrange
        $recurringScheduleReviewResponseTransfer = $this->createResponse()->addUnchangedItem($this->createScheduleItem(2, 500));

        // Act
        $recurringScheduleReviewResponseTransfer = $this->calculate($recurringScheduleReviewResponseTransfer);

        // Assert
        $this->assertSame('0', $recurringScheduleReviewResponseTransfer->getTotalChangePercent());
    }

    public function testTotalChangePercentIsEmptyWithoutAnOriginalTotal(): void
    {
        // Act
        $recurringScheduleReviewResponseTransfer = $this->calculate($this->createResponse());

        // Assert
        $this->assertNull($recurringScheduleReviewResponseTransfer->getTotalChangePercent());
    }

    protected function calculate(RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer): RecurringScheduleReviewResponseTransfer
    {
        $configMock = $this->createMock(OrderExperienceManagementConfig::class);
        $configMock->method('getPriceChangeReviewReasons')->willReturn([SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED]);
        $configMock->method('getSubstitutableReviewReasons')->willReturn([]);
        $configMock->method('getUnavailableReviewReasons')->willReturn([]);

        return (new ScheduleReviewSummaryCalculator($configMock))->calculate($recurringScheduleReviewResponseTransfer);
    }

    protected function createResponse(): RecurringScheduleReviewResponseTransfer
    {
        return (new RecurringScheduleReviewResponseTransfer())
            ->setRecurringSchedule((new RecurringScheduleTransfer())->setPriceMode('GROSS_MODE'));
    }

    protected function createScheduleItem(int $quantity, int $referenceGrossPrice): RecurringScheduleItemTransfer
    {
        return (new RecurringScheduleItemTransfer())
            ->setQuantity($quantity)
            ->setReferenceGrossPrice($referenceGrossPrice)
            ->setReferenceNetPrice($referenceGrossPrice);
    }

    protected function createFlaggedItem(int $quantity, int $referenceGrossPrice, int $previousPrice, int $currentPrice): RecurringScheduleItemReviewTransfer
    {
        return (new RecurringScheduleItemReviewTransfer())
            ->setRecurringScheduleItem($this->createScheduleItem($quantity, $referenceGrossPrice))
            ->setPreviousPrice($previousPrice)
            ->setCurrentPrice($currentPrice)
            ->addReviewReason(SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED);
    }

    protected function createOrderReview(int $previousTotal, int $currentTotal): RecurringScheduleOrderReviewTransfer
    {
        return (new RecurringScheduleOrderReviewTransfer())
            ->setReviewReason(SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_ORDER_TOTAL_INCREASED)
            ->setPreviousTotal($previousTotal)
            ->setCurrentTotal($currentTotal);
    }
}
