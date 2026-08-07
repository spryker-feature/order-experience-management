<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition;

use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\PlaceableQuoteDeserializerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment\AddedItemShipmentResolverInterface;

class AddedItemResolver implements AddedItemResolverInterface
{
    public function __construct(
        protected readonly PlaceableQuoteDeserializerInterface $placeableQuoteDeserializer,
        protected readonly AddedItemMerchantReferenceResolverInterface $addedItemMerchantReferenceResolver,
        protected readonly AddedItemCartAdderInterface $addedItemCartAdder,
        protected readonly AddedItemShipmentResolverInterface $addedItemShipmentResolver,
    ) {
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\RecurringScheduleItemAdditionTransfer> $recurringScheduleItemAdditionTransfers
     *
     * @return array<int, array<\Generated\Shared\Transfer\ItemTransfer>>
     */
    public function resolveAddedItems(
        array $recurringScheduleItemAdditionTransfers,
        RecurringScheduleTransfer $recurringScheduleTransfer,
    ): array {
        if ($recurringScheduleItemAdditionTransfers === []) {
            return [];
        }

        $scheduleQuoteTransfer = $this->placeableQuoteDeserializer->deserialize($recurringScheduleTransfer->getQuoteDataOrFail());
        $merchantReferenceMap = $this->addedItemMerchantReferenceResolver->resolveMerchantReferences($recurringScheduleItemAdditionTransfers);

        $itemTransfersByIndex = $this->addedItemCartAdder->addItems(
            $recurringScheduleItemAdditionTransfers,
            $merchantReferenceMap,
            $scheduleQuoteTransfer,
        );

        $shipmentTransfersByIndex = $this->addedItemShipmentResolver->resolveShipments(
            $recurringScheduleItemAdditionTransfers,
            $recurringScheduleTransfer,
            $scheduleQuoteTransfer,
            $merchantReferenceMap,
        );

        return $this->applyChosenShipments($itemTransfersByIndex, $shipmentTransfersByIndex);
    }

    /**
     * @param array<int, array<\Generated\Shared\Transfer\ItemTransfer>> $itemTransfersByIndex
     * @param array<int, \Generated\Shared\Transfer\ShipmentTransfer> $shipmentTransfersByIndex
     *
     * @return array<int, array<\Generated\Shared\Transfer\ItemTransfer>>
     */
    protected function applyChosenShipments(array $itemTransfersByIndex, array $shipmentTransfersByIndex): array
    {
        foreach ($itemTransfersByIndex as $index => $itemTransfers) {
            $shipmentTransfer = $shipmentTransfersByIndex[$index] ?? null;

            if ($shipmentTransfer === null) {
                continue;
            }

            foreach ($itemTransfers as $itemTransfer) {
                $itemTransfer->setShipment((new ShipmentTransfer())->fromArray($shipmentTransfer->toArray(true, true), true));
            }
        }

        return $itemTransfersByIndex;
    }
}
