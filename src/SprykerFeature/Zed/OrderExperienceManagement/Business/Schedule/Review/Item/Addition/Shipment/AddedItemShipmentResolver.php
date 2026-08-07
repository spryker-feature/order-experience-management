<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment;

use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;

class AddedItemShipmentResolver implements AddedItemShipmentResolverInterface
{
    public function __construct(
        protected readonly AddedItemShippingAddressResolverInterface $addedItemShippingAddressResolver,
        protected readonly ShippingAddressChoiceMatcherInterface $shippingAddressChoiceMatcher,
        protected readonly AddedItemShipmentMethodResolverInterface $addedItemShipmentMethodResolver,
    ) {
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\RecurringScheduleItemAdditionTransfer> $recurringScheduleItemAdditionTransfers
     * @param array<int, string> $merchantReferenceMap
     *
     * @return array<int, \Generated\Shared\Transfer\ShipmentTransfer>
     */
    public function resolveShipments(
        array $recurringScheduleItemAdditionTransfers,
        RecurringScheduleTransfer $recurringScheduleTransfer,
        QuoteTransfer $scheduleQuoteTransfer,
        array $merchantReferenceMap,
    ): array {
        $addressTransfersByIndex = $this->resolveAddressesByIndex(
            $recurringScheduleItemAdditionTransfers,
            $recurringScheduleTransfer,
            $scheduleQuoteTransfer,
        );
        $shipmentMethodTransfersByIndex = $this->addedItemShipmentMethodResolver->findAvailableMethods(
            $recurringScheduleItemAdditionTransfers,
            $addressTransfersByIndex,
            $merchantReferenceMap,
            $scheduleQuoteTransfer,
        );

        $shipmentTransfersByIndex = [];

        foreach ($shipmentMethodTransfersByIndex as $index => $shipmentMethodTransfer) {
            $recurringScheduleItemAdditionTransfer = $recurringScheduleItemAdditionTransfers[$index];
            $addressTransfer = $addressTransfersByIndex[$index] ?? null;

            if ($addressTransfer === null) {
                continue;
            }

            $merchantReference = $merchantReferenceMap[$index] ?? null;

            $shipmentTransfersByIndex[$index] = (new ShipmentTransfer())
                ->setShippingAddress($addressTransfer)
                ->setMethod($shipmentMethodTransfer)
                ->setMerchantReference($merchantReference)
                ->setShipmentSelection((string)$recurringScheduleItemAdditionTransfer->getIdShipmentMethodOrFail());
        }

        return $shipmentTransfersByIndex;
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\RecurringScheduleItemAdditionTransfer> $recurringScheduleItemAdditionTransfers
     *
     * @return array<int, \Generated\Shared\Transfer\AddressTransfer>
     */
    protected function resolveAddressesByIndex(
        array $recurringScheduleItemAdditionTransfers,
        RecurringScheduleTransfer $recurringScheduleTransfer,
        QuoteTransfer $scheduleQuoteTransfer,
    ): array {
        $choiceTransfers = $this->addedItemShippingAddressResolver->getOwnedAddressChoices(
            $recurringScheduleTransfer,
            $scheduleQuoteTransfer,
        );

        $addressTransfersByIndex = [];

        foreach ($recurringScheduleItemAdditionTransfers as $index => $recurringScheduleItemAdditionTransfer) {
            $addressTransfer = $this->shippingAddressChoiceMatcher
                ->findChoice($recurringScheduleItemAdditionTransfer, $choiceTransfers)
                ?->getAddress();

            if ($addressTransfer === null) {
                continue;
            }

            $addressTransfersByIndex[$index] = $addressTransfer;
        }

        return $addressTransfersByIndex;
    }
}
