<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Updater;

use Generated\Shared\Transfer\RecurringOrderQuoteUpdateResponseTransfer;
use Generated\Shared\Transfer\RecurringOrderSettingsTransfer;

interface RecurringOrderQuoteUpdaterInterface
{
    public function updateRecurringOrderSettingsOnQuote(
        int $idQuote,
        ?RecurringOrderSettingsTransfer $recurringOrderSettingsTransfer,
    ): RecurringOrderQuoteUpdateResponseTransfer;
}
