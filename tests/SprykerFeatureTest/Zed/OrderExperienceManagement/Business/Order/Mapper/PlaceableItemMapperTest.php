<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Order\Mapper;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\Mapper\PlaceableItemMapper;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Business
 * @group Order
 * @group Mapper
 * @group PlaceableItemMapperTest
 * Add your own group annotations below this line
 */
class PlaceableItemMapperTest extends Unit
{
    protected const string SKU = '131_24872891';

    protected const int NEGOTIATED_PRICE = 9999;

    protected const int QUANTITY = 3;

    /**
     * A recurring schedule stores the WHOLE quote as a JSON snapshot, so every field the original
     * cart carried comes back on each run — including a source price, which is a manual override of
     * one order rather than a standing agreement.
     *
     * Leaving it in place would freeze a negotiation into every future run: the schedule would keep
     * charging a price that was agreed once, long after the contract, the catalogue or the buyer's
     * merchant relationship moved on, and nothing in the recurring flow would ever look at a price
     * again. Clearing them is what makes each run re-price against whatever is current.
     *
     * @dataProvider provideOverridePriceFields
     */
    public function testMapRecurringScheduleItemClearsAPriceOverrideFromTheStoredQuoteSnapshot(
        string $itemDataField
    ): void {
        // Arrange
        $recurringScheduleItemTransfer = (new RecurringScheduleItemTransfer())->setItemData(
            json_encode([
                'sku' => static::SKU,
                $itemDataField => static::NEGOTIATED_PRICE,
            ], JSON_THROW_ON_ERROR),
        );

        // Act
        $itemTransfer = (new PlaceableItemMapper())->mapRecurringScheduleItemToItemTransfer(
            $recurringScheduleItemTransfer,
            static::QUANTITY,
            new ItemTransfer(),
        );

        // Assert
        $this->assertSame(static::SKU, $itemTransfer->getSku(), 'The snapshot itself must still be applied.');
        $this->assertNull($itemTransfer->getSourceUnitGrossPrice());
        $this->assertNull($itemTransfer->getSourceUnitNetPrice());
        $this->assertNull($itemTransfer->getForcedUnitGrossPrice());
    }

    /**
     * @return iterable<string, array<int, string>>
     */
    public function provideOverridePriceFields(): iterable
    {
        yield 'source gross price' => ['sourceUnitGrossPrice'];

        yield 'source net price' => ['sourceUnitNetPrice'];

        yield 'forced gross price' => ['forcedUnitGrossPrice'];
    }

    /**
     * The snapshot belongs to a placed order, so its identity fields would otherwise make the new
     * run try to write over the original order's rows.
     */
    public function testMapRecurringScheduleItemClearsTheIdentityOfTheOrderTheSnapshotCameFrom(): void
    {
        // Arrange
        $recurringScheduleItemTransfer = (new RecurringScheduleItemTransfer())->setItemData(
            json_encode([
                'sku' => static::SKU,
                'idSalesOrderItem' => 42,
                'idSalesOrder' => 7,
                'uuid' => 'b2c3d4e5-f6a7-4b8c-9d0e-1f2a3b4c5d6e',
            ], JSON_THROW_ON_ERROR),
        );

        // Act
        $itemTransfer = (new PlaceableItemMapper())->mapRecurringScheduleItemToItemTransfer(
            $recurringScheduleItemTransfer,
            static::QUANTITY,
            new ItemTransfer(),
        );

        // Assert
        $this->assertNull($itemTransfer->getIdSalesOrderItem());
        $this->assertNull($itemTransfer->getIdSalesOrder());
        $this->assertNull($itemTransfer->getUuid());
        $this->assertSame(static::QUANTITY, $itemTransfer->getQuantity());
    }
}
