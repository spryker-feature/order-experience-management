<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Order;

use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;

interface PlacementQuotePreparerInterface
{
    public function prepareForCheckout(
        QuoteTransfer $reloadedQuoteTransfer,
        QuoteTransfer $sourceQuoteTransfer,
        RecurringScheduleTransfer $recurringScheduleTransfer,
    ): QuoteTransfer;
}
