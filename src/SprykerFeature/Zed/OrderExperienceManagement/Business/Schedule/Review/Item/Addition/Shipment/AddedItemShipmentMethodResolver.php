<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment;

use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\ShipmentMethodsCollectionTransfer;
use Generated\Shared\Transfer\ShipmentMethodTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;
use Spryker\Service\Shipment\ShipmentServiceInterface;
use Spryker\Zed\Shipment\Business\ShipmentFacadeInterface;

class AddedItemShipmentMethodResolver implements AddedItemShipmentMethodResolverInterface
{
    /**
     * @param array<string> $supportedShipmentTypeKeys Delivery-like shipment type keys accepted for the added item.
     */
    public function __construct(
        protected readonly ShipmentFacadeInterface $shipmentFacade,
        protected readonly ShipmentServiceInterface $shipmentService,
        protected readonly array $supportedShipmentTypeKeys,
    ) {
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\RecurringScheduleItemAdditionTransfer> $recurringScheduleItemAdditionTransfers
     * @param array<int, \Generated\Shared\Transfer\AddressTransfer> $addressTransfersByIndex
     * @param array<int, string> $merchantReferenceMap
     *
     * @return array<int, \Generated\Shared\Transfer\ShipmentMethodTransfer>
     */
    public function findAvailableMethods(
        array $recurringScheduleItemAdditionTransfers,
        array $addressTransfersByIndex,
        array $merchantReferenceMap,
        QuoteTransfer $scheduleQuoteTransfer,
    ): array {
        $probeItemTransfersByIndex = $this->createProbeItems(
            $recurringScheduleItemAdditionTransfers,
            $addressTransfersByIndex,
            $merchantReferenceMap,
        );

        if ($probeItemTransfersByIndex === []) {
            return [];
        }

        $shipmentMethodsCollectionTransfer = $this->shipmentFacade->getAvailableMethodsByShipment(
            $this->buildProbeQuote($scheduleQuoteTransfer, $probeItemTransfersByIndex),
        );
        $shipmentMethodTransfersByHash = $this->indexMethodsByShipmentHash($shipmentMethodsCollectionTransfer);

        $shipmentMethodTransfersByIndex = [];

        foreach ($probeItemTransfersByIndex as $index => $itemTransfer) {
            $shipmentHashKey = $this->shipmentService->getShipmentHashKey($itemTransfer->getShipmentOrFail());
            $shipmentMethodTransfer = $this->findSupportedMethod(
                $shipmentMethodTransfersByHash[$shipmentHashKey] ?? [],
                (int)$recurringScheduleItemAdditionTransfers[$index]->getIdShipmentMethod(),
            );

            if ($shipmentMethodTransfer !== null) {
                $shipmentMethodTransfersByIndex[$index] = $shipmentMethodTransfer;
            }
        }

        return $shipmentMethodTransfersByIndex;
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\RecurringScheduleItemAdditionTransfer> $recurringScheduleItemAdditionTransfers
     * @param array<int, \Generated\Shared\Transfer\AddressTransfer> $addressTransfersByIndex Keyed by addition index.
     * @param array<int, string> $merchantReferenceMap
     *
     * @return array<int, \Generated\Shared\Transfer\ItemTransfer>
     */
    protected function createProbeItems(
        array $recurringScheduleItemAdditionTransfers,
        array $addressTransfersByIndex,
        array $merchantReferenceMap,
    ): array {
        $probeItemTransfersByIndex = [];

        foreach ($recurringScheduleItemAdditionTransfers as $index => $recurringScheduleItemAdditionTransfer) {
            $addressTransfer = $addressTransfersByIndex[$index] ?? null;

            if ($addressTransfer === null || $recurringScheduleItemAdditionTransfer->getIdShipmentMethod() === null) {
                continue;
            }

            $merchantReference = $merchantReferenceMap[$index] ?? null;

            $probeItemTransfersByIndex[$index] = (new ItemTransfer())
                ->setSku($recurringScheduleItemAdditionTransfer->getSkuOrFail())
                ->setQuantity($recurringScheduleItemAdditionTransfer->getQuantityOrFail())
                ->setMerchantReference($merchantReference)
                ->setShipment(
                    (new ShipmentTransfer())
                        ->setShippingAddress($addressTransfer)
                        ->setMerchantReference($merchantReference),
                );
        }

        return $probeItemTransfersByIndex;
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\ItemTransfer> $probeItemTransfersByIndex
     */
    protected function buildProbeQuote(QuoteTransfer $scheduleQuoteTransfer, array $probeItemTransfersByIndex): QuoteTransfer
    {
        $quoteTransfer = (new QuoteTransfer())
            ->setStore($scheduleQuoteTransfer->getStore())
            ->setCurrency($scheduleQuoteTransfer->getCurrency())
            ->setPriceMode($scheduleQuoteTransfer->getPriceMode());

        foreach ($probeItemTransfersByIndex as $itemTransfer) {
            $quoteTransfer->addItem($itemTransfer);
        }

        return $quoteTransfer;
    }

    /**
     * @return array<string, array<\Generated\Shared\Transfer\ShipmentMethodTransfer>>
     */
    protected function indexMethodsByShipmentHash(ShipmentMethodsCollectionTransfer $shipmentMethodsCollectionTransfer): array
    {
        $shipmentMethodTransfersByHash = [];

        foreach ($shipmentMethodsCollectionTransfer->getShipmentMethods() as $shipmentMethodsTransfer) {
            $shipmentMethodTransfersByHash[(string)$shipmentMethodsTransfer->getShipmentHash()] = $shipmentMethodsTransfer->getMethods()->getArrayCopy();
        }

        return $shipmentMethodTransfersByHash;
    }

    /**
     * @param array<\Generated\Shared\Transfer\ShipmentMethodTransfer> $shipmentMethodTransfers
     */
    protected function findSupportedMethod(array $shipmentMethodTransfers, int $idShipmentMethod): ?ShipmentMethodTransfer
    {
        foreach ($shipmentMethodTransfers as $shipmentMethodTransfer) {
            if (
                $shipmentMethodTransfer->getIdShipmentMethod() === $idShipmentMethod
                && in_array($shipmentMethodTransfer->getShipmentType()?->getKey(), $this->supportedShipmentTypeKeys, true)
            ) {
                return $shipmentMethodTransfer;
            }
        }

        return null;
    }
}
