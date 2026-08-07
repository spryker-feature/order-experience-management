<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Plan;

interface ScheduleReviewItemUpdatePlanMergerInterface
{
    /**
     * @param array<int, \Generated\Shared\Transfer\RecurringScheduleItemTransfer> $recurringScheduleItemTransfers
     * @param array<int, \Generated\Shared\Transfer\RecurringScheduleItemTransfer> $additionalRecurringScheduleItemTransfers
     *
     * @return array<int, \Generated\Shared\Transfer\RecurringScheduleItemTransfer>
     */
    public function merge(array $recurringScheduleItemTransfers, array $additionalRecurringScheduleItemTransfers): array;
}
