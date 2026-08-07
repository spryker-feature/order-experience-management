<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin;

use Generated\Shared\Transfer\CheckoutErrorTransfer;
use Generated\Shared\Transfer\QuoteTransfer;

interface RecurringOrderCheckoutValidatorPluginInterface
{
    /**
     * Specification:
     * - Validates one aspect of a quote that is about to be checked out as a recurring order.
     * - Plugins are invoked only when `QuoteTransfer.recurringOrderSettings` is set.
     * - Returns a `CheckoutErrorTransfer` with `message` set to a glossary key, and `parameters` set when the message is parameterized, if the quote must not be checked out as a recurring order.
     * - Returns `null` when this aspect passes.
     *
     * @api
     */
    public function validate(QuoteTransfer $quoteTransfer): ?CheckoutErrorTransfer;
}
