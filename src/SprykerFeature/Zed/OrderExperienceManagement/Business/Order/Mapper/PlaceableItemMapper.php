<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Order\Mapper;

use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;

class PlaceableItemMapper implements PlaceableItemMapperInterface
{
    public function mapRecurringScheduleItemToItemTransfer(
        RecurringScheduleItemTransfer $recurringScheduleItemTransfer,
        int $quantity,
        ItemTransfer $itemTransfer,
    ): ItemTransfer {
        $itemData = json_decode($recurringScheduleItemTransfer->getItemDataOrFail(), true, 512, JSON_THROW_ON_ERROR);

        $itemTransfer
            ->fromArray($itemData, true)
            ->setQuantity($quantity)
            ->setIdSalesOrderItem(null)
            ->setUuid(null)
            ->setIdSalesOrder(null)
            ->setPriceProduct(null)
            ->setSourceUnitGrossPrice(null)
            ->setSourceUnitNetPrice(null)
            ->setForcedUnitGrossPrice(null);

        $itemTransfer->getShipment()?->setIdSalesShipment(null)->setUuid(null);
        $itemTransfer->getShipment()?->getShippingAddress()?->setIdSalesOrderAddress(null);

        return $itemTransfer;
    }
}
