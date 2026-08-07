<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Order;

use Generated\Shared\Transfer\CalculableObjectTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Spryker\Zed\Payment\Business\PaymentFacadeInterface;

class PlacementQuotePreparer implements PlacementQuotePreparerInterface
{
    public function __construct(
        protected PaymentFacadeInterface $paymentFacade,
        protected PlaceableQuoteShipmentExpenseBuilderInterface $shipmentExpenseBuilder,
    ) {
    }

    public function prepareForCheckout(
        QuoteTransfer $reloadedQuoteTransfer,
        QuoteTransfer $sourceQuoteTransfer,
        RecurringScheduleTransfer $recurringScheduleTransfer,
    ): QuoteTransfer {
        $reloadedQuoteTransfer = $this->shipmentExpenseBuilder->appendMissingShipmentExpenses(
            $reloadedQuoteTransfer,
            $recurringScheduleTransfer,
        );

        $reloadedQuoteTransfer = $this->applySourceQuotePayments($reloadedQuoteTransfer, $sourceQuoteTransfer);
        $reloadedQuoteTransfer = $this->recalculatePayments($reloadedQuoteTransfer);
        $reloadedQuoteTransfer = $this->skipAddressSaving($reloadedQuoteTransfer);

        return $reloadedQuoteTransfer;
    }

    protected function applySourceQuotePayments(
        QuoteTransfer $reloadedQuoteTransfer,
        QuoteTransfer $sourceQuoteTransfer,
    ): QuoteTransfer {
        return $reloadedQuoteTransfer
            ->setPayment($sourceQuoteTransfer->getPayment())
            ->setPayments(clone $sourceQuoteTransfer->getPayments());
    }

    protected function recalculatePayments(QuoteTransfer $quoteTransfer): QuoteTransfer
    {
        $calculableObjectTransfer = (new CalculableObjectTransfer())->fromArray($quoteTransfer->toArray(), true);
        $calculableObjectTransfer->setOriginalQuote($quoteTransfer);

        $this->paymentFacade->recalculatePayments($calculableObjectTransfer);

        return $quoteTransfer->fromArray($calculableObjectTransfer->toArray(), true);
    }

    protected function skipAddressSaving(QuoteTransfer $quoteTransfer): QuoteTransfer
    {
        $quoteTransfer->setIsAddressSavingSkipped(true);

        $quoteTransfer->getBillingAddress()?->setIsAddressSavingSkipped(true);

        foreach ($quoteTransfer->getItems() as $itemTransfer) {
            $itemTransfer->getShipment()?->getShippingAddress()?->setIsAddressSavingSkipped(true);
        }

        return $quoteTransfer;
    }
}
