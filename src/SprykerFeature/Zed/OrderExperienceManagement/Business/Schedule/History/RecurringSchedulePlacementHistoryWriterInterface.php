<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\History;

use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;

interface RecurringSchedulePlacementHistoryWriterInterface
{
    public function writeHistory(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        CheckoutResponseTransfer $checkoutResponseTransfer
    ): void;
}
