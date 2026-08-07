<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment;

use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Mapper\AddedItemShippingAddressMapperInterface;

class ScheduleAddressReader implements ScheduleAddressReaderInterface
{
    public function __construct(protected readonly AddedItemShippingAddressMapperInterface $addedItemShippingAddressMapper)
    {
    }

    /**
     * @return array<\Generated\Shared\Transfer\AddressTransfer>
     */
    public function getAddressTransfers(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        QuoteTransfer $scheduleQuoteTransfer,
    ): array {
        return array_merge(
            $this->getStoredLineAddressTransfers($recurringScheduleTransfer),
            $this->getQuoteItemAddressTransfers($scheduleQuoteTransfer),
        );
    }

    /**
     * @return array<\Generated\Shared\Transfer\AddressTransfer>
     */
    protected function getStoredLineAddressTransfers(RecurringScheduleTransfer $recurringScheduleTransfer): array
    {
        $addressTransfers = [];

        foreach ($recurringScheduleTransfer->getItems() as $recurringScheduleItemTransfer) {
            $addressData = $this->findStoredAddressData($recurringScheduleItemTransfer->getItemData());

            if ($addressData === null) {
                continue;
            }

            $addressTransfers[] = $this->addedItemShippingAddressMapper->mapStoredAddressDataToAddressTransfer(
                $addressData,
                new AddressTransfer(),
            );
        }

        return $addressTransfers;
    }

    /**
     * @return array<\Generated\Shared\Transfer\AddressTransfer>
     */
    protected function getQuoteItemAddressTransfers(QuoteTransfer $scheduleQuoteTransfer): array
    {
        $addressTransfers = [];

        foreach ($scheduleQuoteTransfer->getItems() as $itemTransfer) {
            $addressTransfer = $itemTransfer->getShipment()?->getShippingAddress();

            if ($addressTransfer === null) {
                continue;
            }

            $addressTransfers[] = $this->addedItemShippingAddressMapper->mapStoredAddressDataToAddressTransfer(
                $addressTransfer->modifiedToArray(),
                new AddressTransfer(),
            );
        }

        return $addressTransfers;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function findStoredAddressData(?string $itemData): ?array
    {
        if ($itemData === null || $itemData === '') {
            return null;
        }

        $itemDataArray = json_decode($itemData, true, flags: JSON_THROW_ON_ERROR);
        $addressData = $itemDataArray[ItemTransfer::SHIPMENT][ShipmentTransfer::SHIPPING_ADDRESS] ?? null;

        if (!is_array($addressData) || $addressData === []) {
            return null;
        }

        return $addressData;
    }
}
