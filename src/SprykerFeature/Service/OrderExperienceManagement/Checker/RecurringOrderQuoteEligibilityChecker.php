<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Service\OrderExperienceManagement\Checker;

use Generated\Shared\Transfer\QuoteTransfer;
use SprykerFeature\Service\OrderExperienceManagement\OrderExperienceManagementConfig;

class RecurringOrderQuoteEligibilityChecker implements RecurringOrderQuoteEligibilityCheckerInterface
{
    public function __construct(protected readonly OrderExperienceManagementConfig $subscriptionConfig)
    {
    }

    protected function hasInvoicePaymentMethod(QuoteTransfer $quoteTransfer): bool
    {
        $invoiceKeys = array_fill_keys($this->subscriptionConfig->getInvoicePaymentMethodKeys(), true);

        foreach ($quoteTransfer->getPayments() as $paymentTransfer) {
            if (isset($invoiceKeys[$paymentTransfer->getPaymentSelection()]) || isset($invoiceKeys[$paymentTransfer->getPaymentMethod()])) {
                return true;
            }
        }

        $legacyPayment = $quoteTransfer->getPayment();

        if ($legacyPayment === null) {
            return false;
        }

        return isset($invoiceKeys[$legacyPayment->getPaymentSelection()]) || isset($invoiceKeys[$legacyPayment->getPaymentMethod()]);
    }

    public function isEligibleForRecurringOrder(QuoteTransfer $quoteTransfer): bool
    {
        if ($quoteTransfer->getIsLocked()) {
            return false;
        }

        if ($quoteTransfer->getQuoteRequestVersionReference() !== null) {
            return false;
        }

        if ($quoteTransfer->getCustomer()?->getIsGuest()) {
            return false;
        }

        return $this->hasInvoicePaymentMethod($quoteTransfer);
    }
}
