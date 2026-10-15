<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\OrderExperienceManagement\Builder;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Spryker\DecimalObject\Decimal;
use SprykerFeature\Yves\OrderExperienceManagement\Builder\PriceToleranceRowBuilder;
use SprykerFeature\Yves\OrderExperienceManagement\Calculator\OrderTotalReviewThresholdCalculator;
use SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Yves
 * @group OrderExperienceManagement
 * @group Builder
 * @group PriceToleranceRowBuilderTest
 * Add your own group annotations below this line
 */
class PriceToleranceRowBuilderTest extends Unit
{
    public function testBuildsATriggerPriceForAProductWithATolerance(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule([$this->createItem('a', 10000, '5')]);

        // Act
        $productRows = $this->createBuilder()->buildProductRows($recurringScheduleTransfer);

        // Assert
        $this->assertCount(1, $productRows);
        $this->assertSame(10500, $productRows[0]['triggerPrice']);
        $this->assertTrue($productRows[0]['hasTolerance']);
        $this->assertFalse($productRows[0]['isReviewingAnyIncrease']);
    }

    public function testAProductAtZeroReviewsAnyIncreaseAndHasNoTriggerPrice(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule([$this->createItem('a', 10000, '0')]);

        // Act
        $productRows = $this->createBuilder()->buildProductRows($recurringScheduleTransfer);

        // Assert
        $this->assertNull($productRows[0]['triggerPrice']);
        $this->assertTrue($productRows[0]['isReviewingAnyIncrease']);
    }

    public function testUsesTheNextDeliveryPriceAsTheUnitPriceWhenOneIsSet(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule([$this->createItem('a', 10000, '10')->setNextDeliveryGrossPrice(12000)]);

        // Act
        $productRows = $this->createBuilder()->buildProductRows($recurringScheduleTransfer);

        // Assert
        $this->assertSame(12000, $productRows[0]['unitPrice']);
        $this->assertSame(13200, $productRows[0]['triggerPrice']);
    }

    public function testUsesTheNetPriceInNetMode(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule([$this->createItem('a', 10000, '10')->setReferenceNetPrice(8000)])->setPriceMode('NET_MODE');

        // Act
        $productRows = $this->createBuilder()->buildProductRows($recurringScheduleTransfer);

        // Assert
        $this->assertSame(8000, $productRows[0]['unitPrice']);
        $this->assertSame(8800, $productRows[0]['triggerPrice']);
    }

    public function testShowsNoTriggerPriceWhileProductPriceChangesAreIgnored(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule([$this->createItem('a', 10000, '5')])
            ->setPriceTolerancePercent(Decimal::create('2'))
            ->setIsProductPriceChangeIgnored(true);

        // Act
        $builder = $this->createBuilder();
        $productRows = $builder->buildProductRows($recurringScheduleTransfer);

        // Assert
        $this->assertTrue($builder->isProductCheckIgnored($recurringScheduleTransfer));
        $this->assertNull($productRows[0]['triggerPrice']);
        $this->assertFalse($productRows[0]['hasTolerance']);
    }

    public function testIgnoringProductsNeedsAnOrderTolerance(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule([])->setIsProductPriceChangeIgnored(true);

        // Act & Assert
        $this->assertFalse($this->createBuilder()->isProductCheckIgnored($recurringScheduleTransfer));
    }

    public function testOffersNeitherBundleChildrenNorItemsWithoutAKeyForEditing(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule([
            $this->createItem('a', 100, '0'),
            (new RecurringScheduleItemTransfer())->setBundleItemIdentifier('bundle-1')->setReferenceGrossPrice(100),
            (new RecurringScheduleItemTransfer())->setGroupKey('child')->setRelatedBundleItemIdentifier('bundle-1')->setReferenceGrossPrice(100),
            (new RecurringScheduleItemTransfer())->setReferenceGrossPrice(100),
        ]);

        // Act
        $editableItemTransfers = $this->createBuilder()->getEditableItems($recurringScheduleTransfer);

        // Assert
        $this->assertCount(2, $editableItemTransfers);
        $this->assertSame('a', $editableItemTransfers[0]->getGroupKey());
        $this->assertSame('bundle-1', $editableItemTransfers[1]->getBundleItemIdentifier());
    }

    protected function createBuilder(): PriceToleranceRowBuilder
    {
        return new PriceToleranceRowBuilder(new OrderTotalReviewThresholdCalculator(new OrderExperienceManagementConfig()));
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemTransfer> $itemTransfers
     */
    protected function createSchedule(array $itemTransfers): RecurringScheduleTransfer
    {
        $recurringScheduleTransfer = (new RecurringScheduleTransfer())->setPriceMode('GROSS_MODE');

        foreach ($itemTransfers as $itemTransfer) {
            $recurringScheduleTransfer->addItem($itemTransfer);
        }

        return $recurringScheduleTransfer;
    }

    protected function createItem(string $groupKey, int $referenceGrossPrice, string $tolerancePercent): RecurringScheduleItemTransfer
    {
        return (new RecurringScheduleItemTransfer())
            ->setGroupKey($groupKey)
            ->setQuantity(1)
            ->setReferenceGrossPrice($referenceGrossPrice)
            ->setPriceTolerancePercent(Decimal::create($tolerancePercent));
    }
}
