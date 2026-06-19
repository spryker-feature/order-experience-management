<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Order;

use ArrayObject;
use Generated\Shared\Transfer\QuoteTransfer;

class PlaceableQuoteDeserializer implements PlaceableQuoteDeserializerInterface
{
    public function deserialize(string $quoteDataJson): QuoteTransfer
    {
        $quoteData = json_decode($quoteDataJson, true, 512, JSON_THROW_ON_ERROR);
        $quoteTransfer = (new QuoteTransfer())->fromArray($quoteData, true);

        $quoteTransfer->setIdQuote(null);
        $quoteTransfer->setBundleItems(new ArrayObject());
        $quoteTransfer->getBillingAddress()?->setIdSalesOrderAddress(null);
        $quoteTransfer->getShippingAddress()?->setIdSalesOrderAddress(null);

        foreach ($quoteTransfer->getExpenses() as $expenseTransfer) {
            $expenseTransfer->setIdSalesExpense(null);
            $expenseTransfer->getShipment()?->setIdSalesShipment(null);
        }

        return $quoteTransfer;
    }
}
