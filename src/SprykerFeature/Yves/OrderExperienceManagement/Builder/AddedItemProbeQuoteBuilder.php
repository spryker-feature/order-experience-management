<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Builder;

use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;
use Generated\Shared\Transfer\ShipmentTypeTransfer;
use Generated\Shared\Transfer\StoreTransfer;

class AddedItemProbeQuoteBuilder implements AddedItemProbeQuoteBuilderInterface
{
    protected const int PROBE_QUANTITY = 1;

    public function buildProbeQuote(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        string $sku,
        ?string $merchantReference,
        ?string $shipmentTypeUuid,
        AddressTransfer $addressTransfer,
    ): QuoteTransfer {
        $itemTransfer = (new ItemTransfer())
            ->setSku($sku)
            ->setQuantity(static::PROBE_QUANTITY)
            ->setMerchantReference($merchantReference)
            ->setShipment(
                (new ShipmentTransfer())
                    ->setShippingAddress($addressTransfer)
                    ->setMerchantReference($merchantReference),
            );

        if ($shipmentTypeUuid !== null) {
            $itemTransfer->setShipmentType((new ShipmentTypeTransfer())->setUuid($shipmentTypeUuid));
        }

        return (new QuoteTransfer())
            ->setStore((new StoreTransfer())->setName($recurringScheduleTransfer->getStoreName()))
            ->setCurrency((new CurrencyTransfer())->setCode($recurringScheduleTransfer->getCurrencyIsoCode()))
            ->setPriceMode($recurringScheduleTransfer->getPriceMode())
            ->addItem($itemTransfer);
    }
}
