<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Service\OrderExperienceManagement;

use Generated\Shared\Transfer\QuoteTransfer;

interface OrderExperienceManagementServiceInterface
{
    /**
     * Specification:
     * - Returns false when `QuoteTransfer.isLocked` is true — the quote is locked and cannot be used to create a recurring schedule.
     * - Returns false when `QuoteTransfer.quoteRequestVersionReference` is set — the quote originated from an RFQ.
     * - Returns false when `QuoteTransfer.customer.isGuest` is true — guest carts are not eligible for recurring orders.
     * - Returns false when the quote has no payment method matching any key in `OrderExperienceManagementConfig::getInvoicePaymentMethodKeys()`.
     * - Returns true when none of the above conditions apply and the quote is eligible to become a recurring order.
     *
     * @api
     */
    public function isEligibleForRecurringOrder(QuoteTransfer $quoteTransfer): bool;
}
