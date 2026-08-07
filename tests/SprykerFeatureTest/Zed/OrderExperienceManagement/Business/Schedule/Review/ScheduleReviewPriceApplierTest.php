<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Schedule\Review;

use Codeception\Test\Unit;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\ScheduleReviewPriceApplier;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Schedule
 * @group Review
 * @group ScheduleReviewPriceApplierTest
 * Add your own group annotations below this line
 */
class ScheduleReviewPriceApplierTest extends Unit
{
    protected const string GROUP_KEY_A = 'group-key-a';

    protected const string GROUP_KEY_B = 'group-key-b';

    protected const int ID_ITEM_FIRST = 11;

    protected const int ID_ITEM_SECOND = 12;

    protected const int ID_ITEM_THIRD = 13;

    protected const int ACCEPTED_PRICE = 750;

    protected const bool GROSS_MODE = false;

    protected const bool NET_MODE = true;

    public function testReBaselinesEveryRowOfTheGroupInGrossMode(): void
    {
        // Arrange
        $acceptedPricesByGroupKey = [static::GROUP_KEY_A => static::ACCEPTED_PRICE];
        $groupKeysByIdRecurringScheduleItem = [
            static::ID_ITEM_FIRST => static::GROUP_KEY_A,
            static::ID_ITEM_SECOND => static::GROUP_KEY_A,
        ];

        // Act
        $recurringScheduleItemTransfers = (new ScheduleReviewPriceApplier())->reBaselineAcceptedPrices(
            $acceptedPricesByGroupKey,
            $groupKeysByIdRecurringScheduleItem,
            static::GROSS_MODE,
        );

        // Assert - the accepted price is the new reference for the whole line, not only its first row.
        $this->assertSame([static::ID_ITEM_FIRST, static::ID_ITEM_SECOND], array_keys($recurringScheduleItemTransfers));

        foreach ($recurringScheduleItemTransfers as $idRecurringScheduleItem => $recurringScheduleItemTransfer) {
            $this->assertSame($idRecurringScheduleItem, $recurringScheduleItemTransfer->getIdRecurringScheduleItem());
            $this->assertSame(static::ACCEPTED_PRICE, $recurringScheduleItemTransfer->getReferenceGrossPrice());
            $this->assertNull($recurringScheduleItemTransfer->getReferenceNetPrice());
        }
    }

    public function testReBaselinesNetPriceInNetMode(): void
    {
        // Arrange
        $acceptedPricesByGroupKey = [static::GROUP_KEY_A => static::ACCEPTED_PRICE];
        $groupKeysByIdRecurringScheduleItem = [static::ID_ITEM_FIRST => static::GROUP_KEY_A];

        // Act
        $recurringScheduleItemTransfers = (new ScheduleReviewPriceApplier())->reBaselineAcceptedPrices(
            $acceptedPricesByGroupKey,
            $groupKeysByIdRecurringScheduleItem,
            static::NET_MODE,
        );

        // Assert
        $this->assertSame(static::ACCEPTED_PRICE, $recurringScheduleItemTransfers[static::ID_ITEM_FIRST]->getReferenceNetPrice());
        $this->assertNull($recurringScheduleItemTransfers[static::ID_ITEM_FIRST]->getReferenceGrossPrice());
    }

    public function testSkipsRowsWhoseGroupKeyHasNoAcceptedPrice(): void
    {
        // Arrange
        $acceptedPricesByGroupKey = [static::GROUP_KEY_A => static::ACCEPTED_PRICE];
        $groupKeysByIdRecurringScheduleItem = [
            static::ID_ITEM_FIRST => static::GROUP_KEY_A,
            static::ID_ITEM_SECOND => static::GROUP_KEY_B,
            static::ID_ITEM_THIRD => static::GROUP_KEY_B,
        ];

        // Act
        $recurringScheduleItemTransfers = (new ScheduleReviewPriceApplier())->reBaselineAcceptedPrices(
            $acceptedPricesByGroupKey,
            $groupKeysByIdRecurringScheduleItem,
            static::GROSS_MODE,
        );

        // Assert
        $this->assertSame([static::ID_ITEM_FIRST], array_keys($recurringScheduleItemTransfers));
    }

    public function testReturnsNoChangesWithoutAcceptedPrices(): void
    {
        // Act
        $recurringScheduleItemTransfers = (new ScheduleReviewPriceApplier())->reBaselineAcceptedPrices(
            [],
            [static::ID_ITEM_FIRST => static::GROUP_KEY_A],
            static::GROSS_MODE,
        );

        // Assert
        $this->assertSame([], $recurringScheduleItemTransfers);
    }
}
