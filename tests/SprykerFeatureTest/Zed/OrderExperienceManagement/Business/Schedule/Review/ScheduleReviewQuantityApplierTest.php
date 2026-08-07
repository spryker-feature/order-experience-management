<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Schedule\Review;

use Codeception\Test\Unit;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\ScheduleReviewQuantityApplier;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Schedule
 * @group Review
 * @group ScheduleReviewQuantityApplierTest
 * Add your own group annotations below this line
 */
class ScheduleReviewQuantityApplierTest extends Unit
{
    protected const string GROUP_KEY_A = 'group-key-a';

    protected const string GROUP_KEY_MISSING = 'group-key-missing';

    protected const int ID_ITEM_FIRST = 21;

    protected const int ID_ITEM_SECOND = 22;

    protected const int ID_ITEM_THIRD = 23;

    protected const int ACCEPTED_QUANTITY = 5;

    protected const int NEXT_DELIVERY_QUANTITY_SKIP = 0;

    public function testCollapsesSplitGroupIntoLowestIdRowUnderStandingScope(): void
    {
        // Arrange - one line stored as three rows sharing a group key.
        // Act
        [$recurringScheduleItemTransfers, $collapsedRecurringScheduleItemIds] = (new ScheduleReviewQuantityApplier())
            ->applyStandingQuantities(
                [static::GROUP_KEY_A => static::ACCEPTED_QUANTITY],
                $this->createSplitGroupIndex(),
            );

        // Assert - the whole accepted quantity lands on the lowest-ID row and the siblings are collapsed away.
        $this->assertSame([static::ID_ITEM_FIRST], array_keys($recurringScheduleItemTransfers));
        $this->assertSame(static::ACCEPTED_QUANTITY, $recurringScheduleItemTransfers[static::ID_ITEM_FIRST]->getQuantity());
        $this->assertSame([static::ID_ITEM_SECOND, static::ID_ITEM_THIRD], $collapsedRecurringScheduleItemIds);
    }

    public function testSkipsGroupKeyThatIsNotInTheScheduleUnderStandingScope(): void
    {
        // Act
        [$recurringScheduleItemTransfers, $collapsedRecurringScheduleItemIds] = (new ScheduleReviewQuantityApplier())
            ->applyStandingQuantities(
                [static::GROUP_KEY_MISSING => static::ACCEPTED_QUANTITY],
                $this->createSplitGroupIndex(),
            );

        // Assert - nothing is written and nothing is deleted, mirroring the no-surviving-row early return.
        $this->assertSame([], $recurringScheduleItemTransfers);
        $this->assertSame([], $collapsedRecurringScheduleItemIds);
    }

    public function testSkipsWholeGroupForNextDeliveryThenSetsLowestIdRowUnderOccurrenceScope(): void
    {
        // Act
        $recurringScheduleItemTransfers = (new ScheduleReviewQuantityApplier())->applyOccurrenceQuantities(
            [static::GROUP_KEY_A => static::ACCEPTED_QUANTITY],
            $this->createSplitGroupIndex(),
        );

        // Assert - every row is zeroed and only the lowest-ID row carries the accepted quantity.
        $this->assertSame(
            [static::ID_ITEM_FIRST, static::ID_ITEM_SECOND, static::ID_ITEM_THIRD],
            array_keys($recurringScheduleItemTransfers),
        );
        $this->assertSame(static::ACCEPTED_QUANTITY, $recurringScheduleItemTransfers[static::ID_ITEM_FIRST]->getNextDeliveryQuantity());
        $this->assertSame(static::NEXT_DELIVERY_QUANTITY_SKIP, $recurringScheduleItemTransfers[static::ID_ITEM_SECOND]->getNextDeliveryQuantity());
        $this->assertSame(static::NEXT_DELIVERY_QUANTITY_SKIP, $recurringScheduleItemTransfers[static::ID_ITEM_THIRD]->getNextDeliveryQuantity());
    }

    public function testLeavesStandingQuantityUntouchedUnderOccurrenceScope(): void
    {
        // Act
        $recurringScheduleItemTransfers = (new ScheduleReviewQuantityApplier())->applyOccurrenceQuantities(
            [static::GROUP_KEY_A => static::ACCEPTED_QUANTITY],
            $this->createSplitGroupIndex(),
        );

        // Assert
        foreach ($recurringScheduleItemTransfers as $recurringScheduleItemTransfer) {
            $this->assertNull($recurringScheduleItemTransfer->getQuantity());
        }
    }

    public function testSkipsGroupKeyThatIsNotInTheScheduleUnderOccurrenceScope(): void
    {
        // Act
        $recurringScheduleItemTransfers = (new ScheduleReviewQuantityApplier())->applyOccurrenceQuantities(
            [static::GROUP_KEY_MISSING => static::ACCEPTED_QUANTITY],
            $this->createSplitGroupIndex(),
        );

        // Assert
        $this->assertSame([], $recurringScheduleItemTransfers);
    }

    /**
     * @return array<int, string>
     */
    protected function createSplitGroupIndex(): array
    {
        return [
            static::ID_ITEM_FIRST => static::GROUP_KEY_A,
            static::ID_ITEM_SECOND => static::GROUP_KEY_A,
            static::ID_ITEM_THIRD => static::GROUP_KEY_A,
        ];
    }
}
