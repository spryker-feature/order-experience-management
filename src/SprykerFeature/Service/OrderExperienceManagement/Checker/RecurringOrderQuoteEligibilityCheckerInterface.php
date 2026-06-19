<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Service\OrderExperienceManagement\Checker;

use Generated\Shared\Transfer\QuoteTransfer;

interface RecurringOrderQuoteEligibilityCheckerInterface
{
    /**
     * Specification:
     * - Returns false when the quote originated from an RFQ (quoteRequestVersionReference is set).
     * - Returns false when the quote has no invoice-based payment method.
     * - Returns true when the quote is eligible to become a recurring order.
     *
     * @api
     */
    public function isEligibleForRecurringOrder(QuoteTransfer $quoteTransfer): bool;
}
