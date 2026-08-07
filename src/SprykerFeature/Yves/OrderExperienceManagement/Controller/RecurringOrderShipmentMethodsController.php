<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Controller;

use Spryker\Yves\Kernel\View\View;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementFactory getFactory()
 */
class RecurringOrderShipmentMethodsController extends AbstractRecurringOrderController
{
    protected const string REQUEST_PARAM_UUID = 'uuid';

    protected const string REQUEST_PARAM_SKU = 'sku';

    protected const string REQUEST_PARAM_ID_SHIPPING_ADDRESS = 'idShippingAddress';

    protected const string REQUEST_PARAM_SHIPPING_ADDRESS_KEY = 'shippingAddressKey';

    public function indexAction(Request $request): View
    {
        $customerTransfer = $this->getFactory()->getCustomerClient()->getCustomer();
        $sku = (string)$request->query->get(static::REQUEST_PARAM_SKU, '');
        $shippingAddressKey = (string)$request->query->get(static::REQUEST_PARAM_SHIPPING_ADDRESS_KEY, '');
        $idShippingAddress = $request->query->getInt(static::REQUEST_PARAM_ID_SHIPPING_ADDRESS);

        $hasChosenAddress = $shippingAddressKey !== '' || $idShippingAddress !== 0;

        if ($customerTransfer === null || $sku === '' || !$hasChosenAddress) {
            return $this->renderShipmentMethods([]);
        }

        $shipmentMethodTransfers = $this->getFactory()
            ->createAddedItemShipmentMethodReader()
            ->getShipmentMethods(
                (string)$request->attributes->get(static::REQUEST_PARAM_UUID),
                $sku,
                $this->resolveProductOfferReference($request),
                $shippingAddressKey,
                $idShippingAddress,
                $customerTransfer,
            );

        return $this->renderShipmentMethods($shipmentMethodTransfers);
    }

    /**
     * @param array<\Generated\Shared\Transfer\ShipmentMethodTransfer> $shipmentMethodTransfers
     */
    protected function renderShipmentMethods(array $shipmentMethodTransfers): View
    {
        return $this->view(
            ['shipmentMethods' => $shipmentMethodTransfers],
            [],
            '@OrderExperienceManagement/views/added-item-shipment-methods/added-item-shipment-methods.twig',
        );
    }
}
