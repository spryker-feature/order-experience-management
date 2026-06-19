<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator;

use Generated\Shared\Transfer\QuoteTransfer;

interface RecurringScheduleCheckoutValidatorInterface
{
    public function canCreateFromCheckout(QuoteTransfer $quoteTransfer): bool;

    /**
     * Returns the glossary error key when the recurring quote is invalid for checkout,
     * or null when it is valid or the quote is not a recurring order.
     */
    public function validateCheckout(QuoteTransfer $quoteTransfer): ?string;
}
