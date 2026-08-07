<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item;

interface ScheduleReviewPriceApplierInterface
{
    /**
     * @param array<string, int> $acceptedPricesByGroupKey
     * @param array<int, string> $groupKeysByIdRecurringScheduleItem
     *
     * @return array<int, \Generated\Shared\Transfer\RecurringScheduleItemTransfer> Reference price changes keyed by recurring schedule item ID.
     */
    public function reBaselineAcceptedPrices(
        array $acceptedPricesByGroupKey,
        array $groupKeysByIdRecurringScheduleItem,
        bool $isNetMode,
    ): array;
}
