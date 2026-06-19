<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Order;

use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteTransfer;

class ItemShipmentMethodResolver implements ItemShipmentMethodResolverInterface
{
    public function buildExpenseShipmentMethodMap(QuoteTransfer $quoteTransfer): array
    {
        $map = [];

        foreach ($quoteTransfer->getExpenses() as $expenseTransfer) {
            $expenseShipment = $expenseTransfer->getShipment();

            if ($expenseShipment?->getMethod()?->getIdShipmentMethod() === null) {
                continue;
            }

            $key = $expenseShipment->getMerchantReference() ?? '';
            $map[$key] = $expenseShipment->getMethod()->getIdShipmentMethod();
        }

        return $map;
    }

    public function applyShipmentMethodId(ItemTransfer $itemTransfer, array $shipmentMethodIdMap): void
    {
        $shipmentTransfer = $itemTransfer->getShipment();

        if ($shipmentTransfer?->getMethod() === null) {
            return;
        }

        if ($shipmentTransfer->getMethod()->getIdShipmentMethod() !== null) {
            return;
        }

        $key = $shipmentTransfer->getMerchantReference() ?? '';

        if (!array_key_exists($key, $shipmentMethodIdMap)) {
            return;
        }

        $shipmentTransfer->getMethod()->setIdShipmentMethod($shipmentMethodIdMap[$key]);
    }
}
