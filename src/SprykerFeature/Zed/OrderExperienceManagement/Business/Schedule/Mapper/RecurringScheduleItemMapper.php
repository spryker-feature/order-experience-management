<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Mapper;

use Generated\Shared\Transfer\ExpenseTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\ShipmentMethodTransfer;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;

class RecurringScheduleItemMapper implements RecurringScheduleItemMapperInterface
{
    public function __construct(protected readonly UtilEncodingServiceInterface $utilEncodingService)
    {
    }

    /**
     * @param array<string, array{idShipmentMethod: int, unitGrossPrice: int, unitNetPrice: int}> $shipmentDataByShipmentTypeUuid
     */
    public function mapItemToRecurringScheduleItem(
        ItemTransfer $itemTransfer,
        int $fkRecurringSchedule,
        array $shipmentDataByShipmentTypeUuid,
    ): RecurringScheduleItemTransfer {
        $itemTransferToStore = (new ItemTransfer())->fromArray($itemTransfer->toArray(true, true), true);
        $shipmentData = $this->resolveShipmentData($itemTransfer, $shipmentDataByShipmentTypeUuid);
        $this->expandItemWithShipmentMethodId($itemTransferToStore, $shipmentData);

        $configuredBundleTransfer = $itemTransfer->getConfiguredBundle();

        return (new RecurringScheduleItemTransfer())
            ->setIdRecurringSchedule($fkRecurringSchedule)
            ->setSku($itemTransfer->getSkuOrFail())
            ->setProductName($itemTransfer->getName())
            ->setQuantity($itemTransfer->getQuantityOrFail())
            ->setReferenceGrossPrice($itemTransfer->getUnitGrossPrice() ?? 0)
            ->setReferenceNetPrice($itemTransfer->getUnitNetPrice() ?? 0)
            ->setMerchantReference($itemTransfer->getMerchantReference())
            ->setProductOfferReference($itemTransfer->getProductOfferReference())
            ->setGroupKey($itemTransfer->getGroupKey())
            ->setBundleItemIdentifier($itemTransfer->getBundleItemIdentifier())
            ->setRelatedBundleItemIdentifier($itemTransfer->getRelatedBundleItemIdentifier())
            ->setConfigurableBundleTemplateUuid($configuredBundleTransfer?->getTemplate()?->getUuid())
            ->setConfiguredBundleGroupKey($configuredBundleTransfer?->getGroupKey())
            ->setConfigurableBundleName($configuredBundleTransfer?->getTemplate()?->getName())
            ->setConfiguredBundleQuantity($configuredBundleTransfer?->getQuantity())
            ->setIdShipmentMethod($shipmentData[ShipmentMethodTransfer::ID_SHIPMENT_METHOD] ?? null)
            ->setShipmentUnitGrossPrice($shipmentData[ExpenseTransfer::UNIT_GROSS_PRICE] ?? null)
            ->setShipmentUnitNetPrice($shipmentData[ExpenseTransfer::UNIT_NET_PRICE] ?? null)
            ->setItemData((string)$this->utilEncodingService->encodeJson($itemTransferToStore->toArray(true, true)));
    }

    /**
     * @param array<string, array{ShipmentMethodTransfer::ID_SHIPMENT_METHOD: int, ExpenseTransfer::UNIT_GROSS_PRICE: int, ExpenseTransfer::UNIT_NET_PRICE: int}> $shipmentDataByShipmentTypeUuid
     *
     * @return array{ShipmentMethodTransfer::ID_SHIPMENT_METHOD: int, ExpenseTransfer::UNIT_GROSS_PRICE: int, ExpenseTransfer::UNIT_NET_PRICE: int}|array{}
     */
    protected function resolveShipmentData(ItemTransfer $itemTransfer, array $shipmentDataByShipmentTypeUuid): array
    {
        $shipmentTypeUuid = $itemTransfer->getShipment()?->getShipmentTypeUuid() ?? '';

        return $shipmentDataByShipmentTypeUuid[$shipmentTypeUuid] ?? [];
    }

    /**
     * @param array<string, int> $shipmentData
     */
    protected function expandItemWithShipmentMethodId(ItemTransfer $itemTransfer, array $shipmentData): void
    {
        if (!isset($shipmentData[ShipmentMethodTransfer::ID_SHIPMENT_METHOD])) {
            return;
        }

        $shipmentTransfer = $itemTransfer->getShipment();

        if ($shipmentTransfer?->getMethod() === null) {
            return;
        }

        if ($shipmentTransfer->getMethod()->getIdShipmentMethod() !== null) {
            return;
        }

        $shipmentTransfer->getMethod()->setIdShipmentMethod($shipmentData[ShipmentMethodTransfer::ID_SHIPMENT_METHOD]);
    }
}
