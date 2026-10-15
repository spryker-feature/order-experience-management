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
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\PriceDrift\BundlePriceDriftChecker;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\PriceDrift\ItemPriceDriftChecker;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\PriceDrift\OrderTotalPriceDriftChecker;

/**
 * How the per-product tolerance, the order-total tolerance and the "ignore product price changes" setting combine.
 * Every tolerance defaults to 0: a product at 0 reviews any increase, an order tolerance of 0 switches that check off,
 * and the ignore setting only takes effect while the order tolerance is above 0.
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Schedule
 * @group Validator
 * @group PriceDrift
 * @group PriceDriftToleranceMatrixTest
 */
class PriceDriftToleranceMatrixTest extends Unit
{
    protected const string PRICE_MODE_GROSS = 'GROSS_MODE';

    public function testDefaultsReviewAnyIncreaseOnAProductAndNothingElse(): void
    {
        // Arrange
        $rows = [['a', '0', 10000, 10001]];

        // Act
        $validationResultTransfer = $this->checkAll('0', $rows);

        // Assert
        $this->assertSame(['a' => [SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED]], $this->reasonsByGroupKey($validationResultTransfer));
        $this->assertNull($validationResultTransfer->getOrderReview());
        $this->assertFalse($validationResultTransfer->getIsValid());
    }

    public function testDefaultsPlaceAnUnchangedOrFallingPrice(): void
    {
        // Arrange
        $rows = [['a', '0', 10000, 10000], ['b', '0', 10000, 9000]];

        // Act
        $validationResultTransfer = $this->checkAll('0', $rows);

        // Assert
        $this->assertSame([], $this->reasonsByGroupKey($validationResultTransfer));
        $this->assertTrue($validationResultTransfer->getIsValid());
    }

    public function testProductIsPlacedWithinItsTolerance(): void
    {
        // Arrange
        $rows = [['a', '5', 10000, 10400]];

        // Act
        $validationResultTransfer = $this->checkAll('0', $rows);

        // Assert
        $this->assertTrue($validationResultTransfer->getIsValid());
    }

    public function testProductIsPlacedExactlyAtItsLimit(): void
    {
        // Arrange
        $rows = [['a', '5', 10000, 10500]];

        // Act
        $validationResultTransfer = $this->checkAll('0', $rows);

        // Assert
        $this->assertTrue($validationResultTransfer->getIsValid());
    }

    public function testProductIsFlaggedBeyondItsTolerance(): void
    {
        // Arrange
        $rows = [['a', '5', 10000, 10501]];

        // Act
        $validationResultTransfer = $this->checkAll('0', $rows);

        // Assert
        $this->assertSame(['a' => [SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED]], $this->reasonsByGroupKey($validationResultTransfer));
        $this->assertNull($validationResultTransfer->getOrderReview());
    }

    /**
     * a (5%) rose 4% and is placed; b (0) rose one minor unit and is reviewed.
     */
    public function testProductsAreCheckedIndependentlyOfEachOther(): void
    {
        // Arrange
        $rows = [['a', '5', 10000, 10400], ['b', '0', 10000, 10001]];

        // Act
        $validationResultTransfer = $this->checkAll('0', $rows);

        // Assert
        $this->assertSame(['b' => [SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED]], $this->reasonsByGroupKey($validationResultTransfer));
    }

    public function testAnOrderTotalRisingWithinTheProductTolerancesIsPlacedWhileTheOrderCheckIsOff(): void
    {
        // Arrange
        $rows = [['a', '5', 10000, 10400], ['b', '5', 10000, 10400], ['c', '5', 10000, 10400]];

        // Act
        $validationResultTransfer = $this->checkAll('0', $rows);

        // Assert
        $this->assertTrue($validationResultTransfer->getIsValid());
        $this->assertNull($validationResultTransfer->getOrderReview());
    }

    public function testTheIgnoreSettingHasNoEffectWhileTheOrderCheckIsOff(): void
    {
        // Arrange
        $rows = [['a', '0', 10000, 10001]];

        // Act
        $validationResultTransfer = $this->checkAll('0', $rows, true);

        // Assert
        $this->assertSame(['a' => [SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED]], $this->reasonsByGroupKey($validationResultTransfer));
    }

    /**
     * Order check on and products not ignored: both checks run.
     */
    public function testAProductAtZeroStaysStrictWhileTheOrderToleranceIsOn(): void
    {
        // Arrange
        $rows = [['a', '0', 10000, 10001]];

        // Act
        $validationResultTransfer = $this->checkAll('50', $rows);

        // Assert
        $this->assertSame(['a' => [SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED]], $this->reasonsByGroupKey($validationResultTransfer));
        $this->assertNull($validationResultTransfer->getOrderReview());
    }

    /**
     * a (5%) rose 10% = 1000 on a 20000 total (5%), within the 10% order tolerance.
     */
    public function testBothOnReportsOnlyTheProductWhenOnlyTheProductBreaches(): void
    {
        // Arrange
        $rows = [['a', '5', 10000, 11000], ['b', '0', 10000, 10000]];

        // Act
        $validationResultTransfer = $this->checkAll('10', $rows);

        // Assert
        $this->assertSame(['a' => [SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED]], $this->reasonsByGroupKey($validationResultTransfer));
        $this->assertNull($validationResultTransfer->getOrderReview());
        $this->assertFalse($validationResultTransfer->getIsValid());
    }

    /**
     * Each product stays within its own 5%, together the total rose 4% against a 2% order tolerance.
     */
    public function testBothOnReportsOnlyTheOrderTotalWhenNoProductBreaches(): void
    {
        // Arrange
        $rows = [['a', '5', 10000, 10400], ['b', '5', 10000, 10400], ['c', '5', 10000, 10400]];

        // Act
        $validationResultTransfer = $this->checkAll('2', $rows);

        // Assert
        $this->assertSame([], $this->reasonsByGroupKey($validationResultTransfer));
        $this->assertSame(['a', 'b', 'c'], $this->driftedGroupKeys($validationResultTransfer));
        $this->assertFalse($validationResultTransfer->getIsValid());
    }

    public function testBothOnReportsTheProductAndTheOrderTotalTogether(): void
    {
        // Arrange
        $rows = [['a', '1', 10000, 10500], ['b', '1', 10000, 10000]];

        // Act
        $validationResultTransfer = $this->checkAll('2', $rows);

        // Assert
        $this->assertSame(['a' => [SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED]], $this->reasonsByGroupKey($validationResultTransfer));
        $this->assertNotNull($validationResultTransfer->getOrderReview());
    }

    /**
     * a breaches its 1%, b rose 4% within its own 5%: both rose, and both contributed to the breach.
     */
    public function testBothOnRecordsEveryRisingProductAsDriftedWhenTheTotalBreaches(): void
    {
        // Arrange
        $rows = [['a', '1', 10000, 10500], ['b', '5', 10000, 10400]];

        // Act
        $validationResultTransfer = $this->checkAll('2', $rows);

        // Assert
        $this->assertSame(['a' => [SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED]], $this->reasonsByGroupKey($validationResultTransfer));
        $this->assertSame(['a', 'b'], $this->driftedGroupKeys($validationResultTransfer));
    }

    /**
     * Order check on and products ignored: only the order total decides. a rose 50% but the total stays within a 60% order tolerance.
     */
    public function testIgnoringProductsPlacesAProductRiseWhileTheTotalIsWithinTolerance(): void
    {
        // Arrange
        $rows = [['a', '0', 10000, 15000]];

        // Act
        $validationResultTransfer = $this->checkAll('60', $rows, true);

        // Assert
        $this->assertSame([], $this->reasonsByGroupKey($validationResultTransfer));
        $this->assertNull($validationResultTransfer->getOrderReview());
        $this->assertTrue($validationResultTransfer->getIsValid());
    }

    public function testIgnoringProductsFlagsOnlyTheOrderTotalWhenItBreaches(): void
    {
        // Arrange
        $rows = [['a', '1', 10000, 10300]];

        // Act
        $validationResultTransfer = $this->checkAll('2', $rows, true);

        // Assert
        $this->assertSame([], $this->reasonsByGroupKey($validationResultTransfer));
        $this->assertSame(['a'], $this->driftedGroupKeys($validationResultTransfer));
        $this->assertFalse($validationResultTransfer->getIsValid());
    }

    /**
     * a is set to 1% and rose 5%: with products ignored only the 20% order tolerance counts.
     */
    public function testIgnoringProductsIgnoresTheProductTolerancesToo(): void
    {
        // Arrange
        $rows = [['a', '1', 10000, 10500]];

        // Act
        $validationResultTransfer = $this->checkAll('20', $rows, true);

        // Assert
        $this->assertSame([], $this->reasonsByGroupKey($validationResultTransfer));
        $this->assertTrue($validationResultTransfer->getIsValid());
    }

    /**
     * An unavailable price is a different problem and is never tolerated or ignored.
     */
    public function testAMissingPriceIsReviewedWhateverTheSettings(): void
    {
        // Arrange
        $rows = [['a', '100', 10000, 0]];

        // Act
        $validationResultTransfer = $this->checkAll('50', $rows, true);

        // Assert
        $this->assertSame(['a' => [SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_UNAVAILABLE]], $this->reasonsByGroupKey($validationResultTransfer));
    }

    /**
     * Bundles follow the same rules as products.
     */
    public function testBundleParentIsPlacedWithinItsTolerance(): void
    {
        // Arrange
        $itemTolerance = '10';

        // Act
        $validationResultTransfer = $this->checkBundle('0', $itemTolerance, 20000, 21000);

        // Assert
        $this->assertTrue($validationResultTransfer->getIsValid());
    }

    public function testBundleParentIsFlaggedBeyondItsTolerance(): void
    {
        // Arrange
        $itemTolerance = '4';

        // Act
        $validationResultTransfer = $this->checkBundle('0', $itemTolerance, 20000, 21000);

        // Assert
        $this->assertFalse($validationResultTransfer->getIsValid());
        $this->assertCount(1, $validationResultTransfer->getItemReviews());
    }

    public function testBundleParentAtZeroReviewsAnyIncrease(): void
    {
        // Arrange
        $itemTolerance = '0';

        // Act
        $validationResultTransfer = $this->checkBundle('0', $itemTolerance, 20000, 20001);

        // Assert
        $this->assertFalse($validationResultTransfer->getIsValid());
    }

    public function testBundleParentIsNotCheckedWhenProductsAreIgnored(): void
    {
        // Arrange
        $orderTolerance = '50';

        // Act
        $validationResultTransfer = $this->checkBundle($orderTolerance, '0', 20000, 21000, true);

        // Assert
        $this->assertTrue($validationResultTransfer->getIsValid());
    }

    /**
     * @param array<array{0: string, 1: string, 2: int, 3: int}> $rows [groupKey, item tolerance, reference price, current price]
     */
    protected function checkAll(
        string $orderTolerancePercent,
        array $rows,
        bool $isProductPriceChangeIgnored = false
    ): RecurringScheduleValidationResultTransfer {
        $recurringScheduleTransfer = (new RecurringScheduleTransfer())
            ->setPriceTolerancePercent($orderTolerancePercent)
            ->setIsProductPriceChangeIgnored($isProductPriceChangeIgnored);
        $cartChangeTransfer = new CartChangeTransfer();

        foreach ($rows as [$groupKey, $itemTolerance, $referencePrice, $currentPrice]) {
            $recurringScheduleTransfer->addItem(
                (new RecurringScheduleItemTransfer())
                    ->setGroupKey($groupKey)
                    ->setSku($groupKey)
                    ->setQuantity(1)
                    ->setReferenceGrossPrice($referencePrice)
                    ->setReferenceNetPrice($referencePrice)
                    ->setPriceTolerancePercent($itemTolerance),
            );
            $cartChangeTransfer->addItem(
                (new ItemTransfer())->setGroupKey($groupKey)->setSku($groupKey)->setQuantity(1)->setUnitGrossPrice($currentPrice)->setUnitNetPrice($currentPrice),
            );
        }

        return $this->runCheckers($recurringScheduleTransfer, new QuoteTransfer(), $cartChangeTransfer);
    }

    protected function checkBundle(
        string $orderTolerancePercent,
        string $itemTolerance,
        int $referencePrice,
        int $currentPrice,
        bool $isProductPriceChangeIgnored = false,
    ): RecurringScheduleValidationResultTransfer {
        $recurringScheduleTransfer = (new RecurringScheduleTransfer())
            ->setPriceTolerancePercent($orderTolerancePercent)
            ->setIsProductPriceChangeIgnored($isProductPriceChangeIgnored)
            ->addItem(
                (new RecurringScheduleItemTransfer())
                    ->setSku('bundle')
                    ->setQuantity(1)
                    ->setBundleItemIdentifier('bundle-1')
                    ->setReferenceGrossPrice($referencePrice)
                    ->setReferenceNetPrice($referencePrice)
                    ->setPriceTolerancePercent($itemTolerance),
            );

        $quoteTransfer = (new QuoteTransfer())->addBundleItem((new ItemTransfer())->setBundleItemIdentifier('bundle-1')->setSku('bundle')->setQuantity(1));
        $cartChangeTransfer = (new CartChangeTransfer())->addItem(
            (new ItemTransfer())->setBundleItemIdentifier('bundle-1')->setSku('bundle')->setQuantity(1)->setUnitGrossPrice($currentPrice)->setUnitNetPrice($currentPrice),
        );

        return $this->runCheckers($recurringScheduleTransfer, $quoteTransfer, $cartChangeTransfer);
    }

    protected function runCheckers(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        QuoteTransfer $quoteTransfer,
        CartChangeTransfer $cartChangeTransfer,
    ): RecurringScheduleValidationResultTransfer {
        $indexer = new RecurringScheduleItemIndexer();
        $validationResultTransfer = (new RecurringScheduleValidationResultTransfer())->setIsValid(true);

        foreach ([new ItemPriceDriftChecker($indexer), new BundlePriceDriftChecker($indexer), new OrderTotalPriceDriftChecker($indexer)] as $checker) {
            $validationResultTransfer = $checker->check($recurringScheduleTransfer, $quoteTransfer, $cartChangeTransfer, static::PRICE_MODE_GROSS, $validationResultTransfer);
        }

        return $validationResultTransfer;
    }

    /**
     * @return array<string>
     */
    protected function driftedGroupKeys(RecurringScheduleValidationResultTransfer $validationResultTransfer): array
    {
        $groupKeys = [];

        foreach ($validationResultTransfer->getOrderReviewOrFail()->getDriftedItems() as $driftedItemTransfer) {
            $groupKeys[] = (string)$driftedItemTransfer->getRecurringScheduleItemOrFail()->getGroupKey();
        }

        return $groupKeys;
    }

    /**
     * @return array<string, array<string>>
     */
    protected function reasonsByGroupKey(RecurringScheduleValidationResultTransfer $validationResultTransfer): array
    {
        $reasonsByGroupKey = [];

        foreach ($validationResultTransfer->getItemReviews() as $itemReviewTransfer) {
            $reasonsByGroupKey[(string)$itemReviewTransfer->getRecurringScheduleItemOrFail()->getGroupKey()] = $itemReviewTransfer->getReviewReasons();
        }

        return $reasonsByGroupKey;
    }
}
