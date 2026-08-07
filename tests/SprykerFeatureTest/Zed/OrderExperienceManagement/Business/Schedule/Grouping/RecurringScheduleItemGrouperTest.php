<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Schedule\Grouping;

use ArrayObject;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Grouping\RecurringScheduleItemGrouper;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Schedule
 * @group Grouping
 * @group RecurringScheduleItemGrouperTest
 * Add your own group annotations below this line
 */
class RecurringScheduleItemGrouperTest extends Unit
{
    public function testGroupItemsCollapsesSameGroupKeyRowsAndSumsTotals(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule([
            (new RecurringScheduleItemTransfer())->setGroupKey('g1')->setQuantity(2)->setItemTotal(200),
            (new RecurringScheduleItemTransfer())->setGroupKey('g1')->setQuantity(3)->setItemTotal(300),
        ]);

        // Act
        (new RecurringScheduleItemGrouper())->groupItems($recurringScheduleTransfer);

        // Assert
        $recurringScheduleItemTransfers = $recurringScheduleTransfer->getItems();
        $this->assertCount(1, $recurringScheduleItemTransfers);
        $this->assertSame(5, $recurringScheduleItemTransfers[0]->getQuantity());
        $this->assertSame(500, $recurringScheduleItemTransfers[0]->getItemTotal());
        $this->assertSame(500, $recurringScheduleTransfer->getEstimatedTotal());
    }

    public function testGroupItemsKeepsItemsWithoutGroupKeySeparate(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule([
            (new RecurringScheduleItemTransfer())->setQuantity(1)->setItemTotal(100),
            (new RecurringScheduleItemTransfer())->setQuantity(1)->setItemTotal(150),
        ]);

        // Act
        (new RecurringScheduleItemGrouper())->groupItems($recurringScheduleTransfer);

        // Assert
        $this->assertCount(2, $recurringScheduleTransfer->getItems());
        $this->assertSame(250, $recurringScheduleTransfer->getEstimatedTotal());
    }

    public function testGroupItemsAttachesBundleChildrenToParentAndDropsThemAsTopLevelRows(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule([
            (new RecurringScheduleItemTransfer())->setBundleItemIdentifier('b1')->setQuantity(1)->setItemTotal(500),
            (new RecurringScheduleItemTransfer())->setRelatedBundleItemIdentifier('b1')->setQuantity(1)->setItemTotal(0),
            (new RecurringScheduleItemTransfer())->setRelatedBundleItemIdentifier('b1')->setQuantity(1)->setItemTotal(0),
        ]);

        // Act
        (new RecurringScheduleItemGrouper())->groupItems($recurringScheduleTransfer);

        // Assert - only the bundle parent remains at top level, with both children attached.
        $recurringScheduleItemTransfers = $recurringScheduleTransfer->getItems();
        $this->assertCount(1, $recurringScheduleItemTransfers);
        $this->assertSame('b1', $recurringScheduleItemTransfers[0]->getBundleItemIdentifier());
        $this->assertCount(2, $recurringScheduleItemTransfers[0]->getBundledItems());
        $this->assertSame(500, $recurringScheduleTransfer->getEstimatedTotal());
    }

    public function testGroupItemsKeepsOrphanBundleChildrenWhenParentIsMissing(): void
    {
        // Arrange - a child references a bundle identifier that has no parent row.
        $recurringScheduleTransfer = $this->createSchedule([
            (new RecurringScheduleItemTransfer())->setRelatedBundleItemIdentifier('missing')->setQuantity(1)->setItemTotal(120),
        ]);

        // Act
        (new RecurringScheduleItemGrouper())->groupItems($recurringScheduleTransfer);

        // Assert
        $this->assertCount(1, $recurringScheduleTransfer->getItems());
        $this->assertSame(120, $recurringScheduleTransfer->getEstimatedTotal());
    }

    public function testGroupItemsMergesNextDeliveryQuantityWhenPresent(): void
    {
        // Arrange - one row carries a next-delivery override, the other falls back to its standing quantity.
        $recurringScheduleTransfer = $this->createSchedule([
            (new RecurringScheduleItemTransfer())->setGroupKey('g1')->setQuantity(2)->setItemTotal(200)->setNextDeliveryQuantity(1),
            (new RecurringScheduleItemTransfer())->setGroupKey('g1')->setQuantity(3)->setItemTotal(300),
        ]);

        // Act
        (new RecurringScheduleItemGrouper())->groupItems($recurringScheduleTransfer);

        // Assert - merged next-delivery = 1 (override) + 3 (source standing fallback).
        $recurringScheduleItemTransfers = $recurringScheduleTransfer->getItems();
        $this->assertCount(1, $recurringScheduleItemTransfers);
        $this->assertSame(4, $recurringScheduleItemTransfers[0]->getNextDeliveryQuantity());
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemTransfer> $recurringScheduleItemTransfers
     */
    protected function createSchedule(array $recurringScheduleItemTransfers): RecurringScheduleTransfer
    {
        return (new RecurringScheduleTransfer())
            ->setItems(new ArrayObject($recurringScheduleItemTransfers));
    }
}
