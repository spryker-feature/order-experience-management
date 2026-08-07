<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Reader;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\ShipmentMethodsCollectionTransfer;
use Spryker\Client\Shipment\ShipmentClientInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Builder\AddedItemProbeQuoteBuilderInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Resolver\AddedItemShippingAddressResolverInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Resolver\DeliveryShipmentTypeResolverInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Resolver\ProductOfferStorageResolverInterface;

class AddedItemShipmentMethodReader implements AddedItemShipmentMethodReaderInterface
{
    public function __construct(
        protected readonly RecurringScheduleReaderInterface $recurringScheduleReader,
        protected readonly AddedItemShippingAddressResolverInterface $addedItemShippingAddressResolver,
        protected readonly ProductOfferStorageResolverInterface $productOfferStorageResolver,
        protected readonly DeliveryShipmentTypeResolverInterface $deliveryShipmentTypeResolver,
        protected readonly AddedItemProbeQuoteBuilderInterface $addedItemProbeQuoteBuilder,
        protected readonly ShipmentClientInterface $shipmentClient,
    ) {
    }

    /**
     * @return array<\Generated\Shared\Transfer\ShipmentMethodTransfer>
     */
    public function getShipmentMethods(
        string $uuid,
        string $sku,
        ?string $productOfferReference,
        ?string $shippingAddressKey,
        ?int $idShippingAddress,
        CustomerTransfer $customerTransfer,
    ): array {
        $recurringScheduleReviewResponseTransfer = $this->recurringScheduleReader->findScheduleReview($uuid, $customerTransfer);
        $recurringScheduleTransfer = $recurringScheduleReviewResponseTransfer->getRecurringSchedule();

        if ($recurringScheduleTransfer === null) {
            return [];
        }

        $addressTransfer = $this->addedItemShippingAddressResolver->resolveAddress(
            $shippingAddressKey,
            $idShippingAddress,
            $recurringScheduleReviewResponseTransfer,
        );

        if ($addressTransfer === null) {
            return [];
        }

        $productOfferStorageTransfer = $this->productOfferStorageResolver->resolveProductOfferStorage($productOfferReference);

        $deliveryShipmentTypeUuid = $this->deliveryShipmentTypeResolver->resolveDeliveryShipmentTypeUuid(
            $productOfferStorageTransfer,
            $recurringScheduleTransfer->getStoreName(),
        );

        if ($deliveryShipmentTypeUuid === null) {
            return [];
        }

        $quoteTransfer = $this->addedItemProbeQuoteBuilder->buildProbeQuote(
            $recurringScheduleTransfer,
            $sku,
            $productOfferStorageTransfer?->getMerchantReference(),
            $deliveryShipmentTypeUuid,
            $addressTransfer,
        );

        return $this->extractMethods($this->shipmentClient->getAvailableMethodsByShipment($quoteTransfer));
    }

    /**
     * @return array<\Generated\Shared\Transfer\ShipmentMethodTransfer>
     */
    protected function extractMethods(ShipmentMethodsCollectionTransfer $shipmentMethodsCollectionTransfer): array
    {
        $shipmentMethodTransfers = [];

        foreach ($shipmentMethodsCollectionTransfer->getShipmentMethods() as $shipmentMethodsTransfer) {
            foreach ($shipmentMethodsTransfer->getMethods() as $shipmentMethodTransfer) {
                $shipmentMethodTransfers[] = $shipmentMethodTransfer;
            }
        }

        return $shipmentMethodTransfers;
    }
}
