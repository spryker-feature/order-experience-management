<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\RecurringOrder;

use Generated\Shared\Transfer\RecurringOrderQuoteUpdateRequestTransfer;
use Generated\Shared\Transfer\RecurringOrderQuoteUpdateResponseTransfer;

interface RecurringOrderQuoteUpdaterInterface
{
    public function updateRecurringOrderSettingsOnQuote(
        RecurringOrderQuoteUpdateRequestTransfer $recurringOrderQuoteUpdateRequestTransfer
    ): RecurringOrderQuoteUpdateResponseTransfer;
}
