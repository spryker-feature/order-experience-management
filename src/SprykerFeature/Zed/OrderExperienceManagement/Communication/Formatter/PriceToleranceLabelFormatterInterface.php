<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Communication\Formatter;

use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;

interface PriceToleranceLabelFormatterInterface
{
    public function formatItemTolerance(
        RecurringScheduleItemTransfer $recurringScheduleItemTransfer,
        RecurringScheduleTransfer $recurringScheduleTransfer,
    ): string;

    /**
     * @return array<int|string, string>
     */
    public function formatItemTolerances(RecurringScheduleTransfer $recurringScheduleTransfer): array;

    public function formatOrderTolerance(RecurringScheduleTransfer $recurringScheduleTransfer): string;

    public function formatToleranceState(RecurringScheduleTransfer $recurringScheduleTransfer): string;

    public function isProductPriceChangeIgnored(RecurringScheduleTransfer $recurringScheduleTransfer): bool;
}
