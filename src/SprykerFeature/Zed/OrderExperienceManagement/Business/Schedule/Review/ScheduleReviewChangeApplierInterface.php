<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review;

use Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer;

interface ScheduleReviewChangeApplierInterface
{
    /**
     * Persists the approved review changes for the schedule in a single transaction: removes
     * unpurchasable flagged items (all rows sharing their group key) and writes the buyer-accepted
     * prices as the new reference price for every row of the accepted items' group keys.
     *
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer> $acceptedItemReviewTransfers
     */
    public function applyApprovedChanges(
        RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer,
        array $acceptedItemReviewTransfers,
    ): void;
}
