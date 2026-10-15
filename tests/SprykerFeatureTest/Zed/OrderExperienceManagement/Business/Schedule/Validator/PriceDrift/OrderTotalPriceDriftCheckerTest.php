<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Schedule\Validator\PriceDrift;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\RecurringScheduleValidationResultTransfer;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Indexer\RecurringScheduleItemIndexer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\PriceDrift\OrderTotalPriceDriftChecker;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Schedule
 * @group Validator
 * @group PriceDrift
 * @group OrderTotalPriceDriftCheckerTest
 */
class OrderTotalPriceDriftCheckerTest extends Unit
{
    protected const string PRICE_MODE_GROSS = 'GROSS_MODE';

    protected const string PRICE_MODE_NET = 'NET_MODE';

    public function testZeroToleranceDoesNotFlagAnUnchangedTotal(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule('0', [$this->createScheduleItem('a', 2, 10000)]);
        $cartChangeTransfer = $this->createCartChange([$this->createRepricedItem('a', 2, 10000)]);

        // Act
        $validationResultTransfer = $this->check($recurringScheduleTransfer, $cartChangeTransfer);

        // Assert
        $this->assertTrue($validationResultTransfer->getIsValid());
        $this->assertNull($validationResultTransfer->getOrderReview());
    }

    public function testZeroToleranceSwitchesTheCheckOff(): void
    {
        // Arrange: the total rose by one minor unit, but 0 means the order-total check is off
        $recurringScheduleTransfer = $this->createSchedule('0', [$this->createScheduleItem('a', 2, 10000)]);
        $cartChangeTransfer = $this->createCartChange([$this->createRepricedItem('a', 2, 10001)]);

        // Act
        $validationResultTransfer = $this->check($recurringScheduleTransfer, $cartChangeTransfer);

        // Assert
        $this->assertTrue($validationResultTransfer->getIsValid());
        $this->assertNull($validationResultTransfer->getOrderReview());
    }

    public function testScheduleWithoutAToleranceIsTreatedAsOff(): void
    {
        // Arrange
        $recurringScheduleTransfer = (new RecurringScheduleTransfer())->addItem($this->createScheduleItem('a', 1, 10000));
        $cartChangeTransfer = $this->createCartChange([$this->createRepricedItem('a', 1, 20000)]);

        // Act
        $validationResultTransfer = $this->check($recurringScheduleTransfer, $cartChangeTransfer);

        // Assert
        $this->assertTrue($validationResultTransfer->getIsValid());
        $this->assertNull($validationResultTransfer->getOrderReview());
    }

    public function testSmallestPositiveToleranceFlagsAnyRiseBeyondIt(): void
    {
        // Arrange: 0.01% of 20000 is 2, so the limit is 20002 and 20003 is the first total that breaches
        $recurringScheduleTransfer = $this->createSchedule('0.01', [$this->createScheduleItem('a', 2, 10000)]);

        // Act
        $atLimit = $this->check($recurringScheduleTransfer, $this->createCartChange([$this->createRepricedItem('a', 2, 10001)]));
        $beyondLimit = $this->check($recurringScheduleTransfer, $this->createCartChange([$this->createRepricedItem('a', 2, 10002)]));

        // Assert
        $this->assertTrue($atLimit->getIsValid());
        $this->assertFalse($beyondLimit->getIsValid());
        $orderReviewTransfer = $beyondLimit->getOrderReviewOrFail();
        $this->assertSame(SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_ORDER_TOTAL_INCREASED, $orderReviewTransfer->getReviewReason());
        $this->assertSame(20000, $orderReviewTransfer->getPreviousTotal());
        $this->assertSame(20004, $orderReviewTransfer->getCurrentTotal());
        $this->assertSame('0.01', $orderReviewTransfer->getTolerancePercent()->trim()->toString());
    }

    public function testTotalExactlyAtTheToleranceIsAccepted(): void
    {
        // Arrange: 2% of 91474 is 1829.48, so the limit is 93303.48 and 93303 is still inside it
        $recurringScheduleTransfer = $this->createSchedule('2', [$this->createScheduleItem('a', 1, 91474)]);
        $cartChangeTransfer = $this->createCartChange([$this->createRepricedItem('a', 1, 93303)]);

        // Act
        $validationResultTransfer = $this->check($recurringScheduleTransfer, $cartChangeTransfer);

        // Assert
        $this->assertTrue($validationResultTransfer->getIsValid());
        $this->assertNull($validationResultTransfer->getOrderReview());
    }

    public function testSupportsTolerancesFinerThanOneHundredthOfAPercent(): void
    {
        // Arrange: 0.005% of 100000 is 5, so the limit is 100005
        $recurringScheduleTransfer = $this->createSchedule('0.005', [$this->createScheduleItem('a', 1, 100000)]);

        // Act
        $atLimitResultTransfer = $this->check($recurringScheduleTransfer, $this->createCartChange([$this->createRepricedItem('a', 1, 100005)]));
        $overLimitResultTransfer = $this->check($recurringScheduleTransfer, $this->createCartChange([$this->createRepricedItem('a', 1, 100006)]));

        // Assert
        $this->assertTrue($atLimitResultTransfer->getIsValid());
        $this->assertFalse($overLimitResultTransfer->getIsValid());
        $this->assertSame('0.005', $overLimitResultTransfer->getOrderReviewOrFail()->getTolerancePercent()->trim()->toString());
    }

    public function testTotalOneMinorUnitBeyondTheToleranceIsFlagged(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule('2', [$this->createScheduleItem('a', 1, 91474)]);
        $cartChangeTransfer = $this->createCartChange([$this->createRepricedItem('a', 1, 93304)]);

        // Act
        $validationResultTransfer = $this->check($recurringScheduleTransfer, $cartChangeTransfer);

        // Assert
        $orderReviewTransfer = $validationResultTransfer->getOrderReview();
        $this->assertFalse($validationResultTransfer->getIsValid());
        $this->assertNotNull($orderReviewTransfer);
        $this->assertSame(91474, $orderReviewTransfer->getPreviousTotal());
        $this->assertSame(93304, $orderReviewTransfer->getCurrentTotal());
        $this->assertSame('2', $orderReviewTransfer->getTolerancePercent()->trim()->toString());
    }

    public function testRiseInOneProductOffsetByAFallInAnotherKeepsTheTotalWithinTolerance(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule('1', [
            $this->createScheduleItem('a', 1, 10000),
            $this->createScheduleItem('b', 1, 10000),
        ]);
        $cartChangeTransfer = $this->createCartChange([
            $this->createRepricedItem('a', 1, 10500),
            $this->createRepricedItem('b', 1, 9500),
        ]);

        // Act
        $validationResultTransfer = $this->check($recurringScheduleTransfer, $cartChangeTransfer);

        // Assert
        $this->assertTrue($validationResultTransfer->getIsValid());
        $this->assertNull($validationResultTransfer->getOrderReview());
    }

    public function testSeveralSmallRisesAddUpToAnOrderTotalBreach(): void
    {
        // Arrange: each product rose 1%, the total rose 1% and the tolerance is 0.5%
        $recurringScheduleTransfer = $this->createSchedule('0.5', [
            $this->createScheduleItem('a', 1, 10000),
            $this->createScheduleItem('b', 1, 20000),
        ]);
        $cartChangeTransfer = $this->createCartChange([
            $this->createRepricedItem('a', 1, 10100),
            $this->createRepricedItem('b', 1, 20200),
        ]);

        // Act
        $validationResultTransfer = $this->check($recurringScheduleTransfer, $cartChangeTransfer);

        // Assert
        $this->assertFalse($validationResultTransfer->getIsValid());
        $this->assertSame(30000, $validationResultTransfer->getOrderReviewOrFail()->getPreviousTotal());
        $this->assertSame(30300, $validationResultTransfer->getOrderReviewOrFail()->getCurrentTotal());
        $this->assertSame('1', $validationResultTransfer->getOrderReviewOrFail()->getIncreasePercent());
        $this->assertSame('0.5', $validationResultTransfer->getOrderReviewOrFail()->getTolerancePercent()->trim()->toString());
    }

    public function testOrderReviewListsOnlyTheItemsWhosePriceRose(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule('1', [
            $this->createScheduleItem('a', 1, 10000),
            $this->createScheduleItem('b', 1, 10000),
            $this->createScheduleItem('c', 1, 10000),
        ]);
        $cartChangeTransfer = $this->createCartChange([
            $this->createRepricedItem('a', 1, 11000),
            $this->createRepricedItem('b', 1, 9500),
            $this->createRepricedItem('c', 1, 10000),
        ]);

        // Act
        $driftedItemTransfers = $this->check($recurringScheduleTransfer, $cartChangeTransfer)->getOrderReviewOrFail()->getDriftedItems();

        // Assert
        $this->assertCount(1, $driftedItemTransfers);
        $this->assertSame('a', $driftedItemTransfers[0]->getRecurringScheduleItemOrFail()->getGroupKey());
        $this->assertSame(10000, $driftedItemTransfers[0]->getPreviousPrice());
        $this->assertSame(11000, $driftedItemTransfers[0]->getCurrentPrice());
    }

    public function testPercentagesBelowOnePercentKeepTwoDecimals(): void
    {
        // Arrange: 914.74 -> 916.20 is +0.1596%
        $recurringScheduleTransfer = $this->createSchedule('0.1', [$this->createScheduleItem('a', 1, 91474)]);
        $cartChangeTransfer = $this->createCartChange([$this->createRepricedItem('a', 1, 91620)]);

        // Act
        $orderReviewTransfer = $this->check($recurringScheduleTransfer, $cartChangeTransfer)->getOrderReviewOrFail();

        // Assert
        $this->assertSame('0.16', $orderReviewTransfer->getIncreasePercent());
        $this->assertSame('0.1', $orderReviewTransfer->getTolerancePercent()->trim()->toString());
    }

    public function testPercentagesAtOrAboveOnePercentUseOneDecimal(): void
    {
        // Arrange: 914.74 -> 933.80 is +2.0838%
        $recurringScheduleTransfer = $this->createSchedule('2', [$this->createScheduleItem('a', 1, 91474)]);
        $cartChangeTransfer = $this->createCartChange([$this->createRepricedItem('a', 1, 93380)]);

        // Act
        $orderReviewTransfer = $this->check($recurringScheduleTransfer, $cartChangeTransfer)->getOrderReviewOrFail();

        // Assert
        $this->assertSame('2.1', $orderReviewTransfer->getIncreasePercent());
        $this->assertSame('2', $orderReviewTransfer->getTolerancePercent()->trim()->toString());
    }

    public function testFallingTotalIsNeverFlagged(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule('1', [$this->createScheduleItem('a', 1, 10000)]);
        $cartChangeTransfer = $this->createCartChange([$this->createRepricedItem('a', 1, 9000)]);

        // Act
        $validationResultTransfer = $this->check($recurringScheduleTransfer, $cartChangeTransfer);

        // Assert
        $this->assertTrue($validationResultTransfer->getIsValid());
        $this->assertNull($validationResultTransfer->getOrderReview());
    }

    public function testNextDeliveryPriceIsTheReferenceWhenSet(): void
    {
        // Arrange: the buyer accepted 110.00 for the next order only, so a current price of 110.00 is not a rise
        $scheduleItemTransfer = $this->createScheduleItem('a', 1, 10000)->setNextDeliveryGrossPrice(11000);
        $recurringScheduleTransfer = $this->createSchedule('1', [$scheduleItemTransfer]);
        $cartChangeTransfer = $this->createCartChange([$this->createRepricedItem('a', 1, 11000)]);

        // Act
        $validationResultTransfer = $this->check($recurringScheduleTransfer, $cartChangeTransfer);

        // Assert
        $this->assertTrue($validationResultTransfer->getIsValid());
    }

    public function testNetModeComparesNetPrices(): void
    {
        // Arrange
        $scheduleItemTransfer = $this->createScheduleItem('a', 1, 10000)->setReferenceNetPrice(8000);
        $recurringScheduleTransfer = $this->createSchedule('1', [$scheduleItemTransfer]);
        $repricedItemTransfer = $this->createRepricedItem('a', 1, 10000)->setUnitNetPrice(8100);

        // Act
        $validationResultTransfer = $this->check($recurringScheduleTransfer, $this->createCartChange([$repricedItemTransfer]), static::PRICE_MODE_NET);

        // Assert
        $this->assertSame(8000, $validationResultTransfer->getOrderReviewOrFail()->getPreviousTotal());
        $this->assertSame(8100, $validationResultTransfer->getOrderReviewOrFail()->getCurrentTotal());
    }

    public function testItemWithoutAnAvailablePriceIsLeftOutOfBothTotals(): void
    {
        // Arrange: the unavailable item is reviewed on its own, it must not read as a falling total that masks a rise elsewhere
        $recurringScheduleTransfer = $this->createSchedule('0.5', [
            $this->createScheduleItem('a', 1, 10000),
            $this->createScheduleItem('b', 1, 50000),
        ]);
        $cartChangeTransfer = $this->createCartChange([
            $this->createRepricedItem('a', 1, 10100),
            $this->createRepricedItem('b', 1, 0),
        ]);

        // Act
        $orderReviewTransfer = $this->check($recurringScheduleTransfer, $cartChangeTransfer)->getOrderReviewOrFail();

        // Assert
        $this->assertSame(10000, $orderReviewTransfer->getPreviousTotal());
        $this->assertSame(10100, $orderReviewTransfer->getCurrentTotal());
    }

    public function testBundleParentIsCountedAndItsChildrenAreNot(): void
    {
        // Arrange
        $bundleScheduleItemTransfer = $this->createScheduleItem('bundle', 1, 20000)->setBundleItemIdentifier('bundle-1');
        $childScheduleItemTransfer = $this->createScheduleItem('child', 1, 0)->setRelatedBundleItemIdentifier('bundle-1');
        $recurringScheduleTransfer = $this->createSchedule('1', [$bundleScheduleItemTransfer, $childScheduleItemTransfer]);

        $repricedBundleTransfer = $this->createRepricedItem('bundle', 1, 21000)->setBundleItemIdentifier('bundle-1');
        $repricedChildTransfer = $this->createRepricedItem('child', 1, 7000)->setRelatedBundleItemIdentifier('bundle-1');

        // Act
        $orderReviewTransfer = $this->check($recurringScheduleTransfer, $this->createCartChange([$repricedChildTransfer, $repricedBundleTransfer]))->getOrderReviewOrFail();

        // Assert
        $this->assertSame(20000, $orderReviewTransfer->getPreviousTotal());
        $this->assertSame(21000, $orderReviewTransfer->getCurrentTotal());
    }

    public function testRepricedItemWithoutAScheduleItemIsIgnored(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule('0', [$this->createScheduleItem('a', 1, 10000)]);
        $cartChangeTransfer = $this->createCartChange([
            $this->createRepricedItem('a', 1, 10000),
            $this->createRepricedItem('unknown', 1, 99999),
        ]);

        // Act
        $validationResultTransfer = $this->check($recurringScheduleTransfer, $cartChangeTransfer);

        // Assert
        $this->assertTrue($validationResultTransfer->getIsValid());
    }

    public function testExistingInvalidResultIsNeverTurnedValid(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule('0', [$this->createScheduleItem('a', 1, 10000)]);
        $cartChangeTransfer = $this->createCartChange([$this->createRepricedItem('a', 1, 10000)]);
        $validationResultTransfer = (new RecurringScheduleValidationResultTransfer())->setIsValid(false);

        // Act
        $validationResultTransfer = $this->check($recurringScheduleTransfer, $cartChangeTransfer, static::PRICE_MODE_GROSS, $validationResultTransfer);

        // Assert
        $this->assertFalse($validationResultTransfer->getIsValid());
    }

    public function testEmptyCartChangeDoesNotFlag(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule('0', [$this->createScheduleItem('a', 1, 10000)]);

        // Act
        $validationResultTransfer = $this->check($recurringScheduleTransfer, new CartChangeTransfer());

        // Assert
        $this->assertTrue($validationResultTransfer->getIsValid());
        $this->assertNull($validationResultTransfer->getOrderReview());
    }

    protected function check(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        CartChangeTransfer $cartChangeTransfer,
        string $priceMode = self::PRICE_MODE_GROSS,
        ?RecurringScheduleValidationResultTransfer $validationResultTransfer = null,
    ): RecurringScheduleValidationResultTransfer {
        return (new OrderTotalPriceDriftChecker(new RecurringScheduleItemIndexer()))->check(
            $recurringScheduleTransfer,
            new QuoteTransfer(),
            $cartChangeTransfer,
            $priceMode,
            $validationResultTransfer ?? (new RecurringScheduleValidationResultTransfer())->setIsValid(true),
        );
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemTransfer> $recurringScheduleItemTransfers
     */
    protected function createSchedule(string $priceTolerancePercent, array $recurringScheduleItemTransfers): RecurringScheduleTransfer
    {
        $recurringScheduleTransfer = (new RecurringScheduleTransfer())->setPriceTolerancePercent($priceTolerancePercent);

        foreach ($recurringScheduleItemTransfers as $recurringScheduleItemTransfer) {
            $recurringScheduleTransfer->addItem($recurringScheduleItemTransfer);
        }

        return $recurringScheduleTransfer;
    }

    protected function createScheduleItem(string $groupKey, int $quantity, int $grossPrice): RecurringScheduleItemTransfer
    {
        return (new RecurringScheduleItemTransfer())
            ->setGroupKey($groupKey)
            ->setSku($groupKey)
            ->setQuantity($quantity)
            ->setReferenceGrossPrice($grossPrice)
            ->setReferenceNetPrice($grossPrice);
    }

    protected function createRepricedItem(string $groupKey, int $quantity, int $grossPrice): ItemTransfer
    {
        return (new ItemTransfer())
            ->setGroupKey($groupKey)
            ->setSku($groupKey)
            ->setQuantity($quantity)
            ->setUnitGrossPrice($grossPrice)
            ->setUnitNetPrice($grossPrice);
    }

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     */
    protected function createCartChange(array $itemTransfers): CartChangeTransfer
    {
        $cartChangeTransfer = new CartChangeTransfer();

        foreach ($itemTransfers as $itemTransfer) {
            $cartChangeTransfer->addItem($itemTransfer);
        }

        return $cartChangeTransfer;
    }
}
