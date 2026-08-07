<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Resolver;

use Generated\Shared\Transfer\ProductOfferStorageTransfer;
use Generated\Shared\Transfer\ShipmentTypeStorageConditionsTransfer;
use Generated\Shared\Transfer\ShipmentTypeStorageCriteriaTransfer;
use Spryker\Client\ShipmentTypeStorage\ShipmentTypeStorageClientInterface;

class DeliveryShipmentTypeResolver implements DeliveryShipmentTypeResolverInterface
{
    /**
     * @param array<string> $supportedShipmentTypeKeys Delivery-like shipment type keys in preference order.
     */
    public function __construct(
        protected readonly ShipmentTypeStorageClientInterface $shipmentTypeStorageClient,
        protected readonly array $supportedShipmentTypeKeys,
    ) {
    }

    /**
     * The resolved UUID drives the native ShipmentTypeShipmentMethodFilterPlugin, so the method list is filtered
     * to a supported (delivery-like) shipment type upstream. An offer that declares its own shipment types is
     * addable only when a supported type is among them (returns null otherwise, so no methods are offered and the
     * product cannot be added). An offer without declared shipment types falls back to the store's supported type.
     */
    public function resolveDeliveryShipmentTypeUuid(?ProductOfferStorageTransfer $productOfferStorageTransfer, ?string $storeName): ?string
    {
        $shipmentTypeStorageTransfers = $productOfferStorageTransfer?->getShipmentTypes();

        if ($shipmentTypeStorageTransfers !== null && count($shipmentTypeStorageTransfers) > 0) {
            return $this->findSupportedShipmentTypeUuid($shipmentTypeStorageTransfers);
        }

        return $this->findStoreSupportedShipmentTypeUuid($storeName);
    }

    protected function findStoreSupportedShipmentTypeUuid(?string $storeName): ?string
    {
        if ($storeName === null) {
            return null;
        }

        $shipmentTypeStorageCollectionTransfer = $this->shipmentTypeStorageClient->getShipmentTypeStorageCollection(
            (new ShipmentTypeStorageCriteriaTransfer())->setShipmentTypeStorageConditions(
                (new ShipmentTypeStorageConditionsTransfer())->setStoreName($storeName),
            ),
        );

        return $this->findSupportedShipmentTypeUuid($shipmentTypeStorageCollectionTransfer->getShipmentTypeStorages());
    }

    /**
     * @param iterable<\Generated\Shared\Transfer\ShipmentTypeStorageTransfer> $shipmentTypeStorageTransfers
     */
    protected function findSupportedShipmentTypeUuid(iterable $shipmentTypeStorageTransfers): ?string
    {
        $uuidsByKey = [];

        foreach ($shipmentTypeStorageTransfers as $shipmentTypeStorageTransfer) {
            $uuidsByKey[$shipmentTypeStorageTransfer->getKey()] = $shipmentTypeStorageTransfer->getUuid();
        }

        foreach ($this->supportedShipmentTypeKeys as $shipmentTypeKey) {
            if (isset($uuidsByKey[$shipmentTypeKey])) {
                return $uuidsByKey[$shipmentTypeKey];
            }
        }

        return null;
    }
}
