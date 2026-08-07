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
    public function isEligibleForRecurringOrder(QuoteTransfer $quoteTransfer): bool;
}
