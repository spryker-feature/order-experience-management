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
     * Persists the approved review changes for the schedule in a single transaction, applying the resolved
     * composition according to the request scope: STANDING (or null) persists permanently — removes unpurchasable
     * items, re-baselines accepted prices, and updates standing quantities; OCCURRENCE applies to the next order
     * only — skips unpurchasable items and sets next-delivery quantities, leaving the standing schedule intact.
     *
     * Added products (pre-resolved on the review response) are persisted as new schedule items in the same scope:
     * STANDING creates a permanent line; OCCURRENCE creates a line for the next order only.
     *
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer> $acceptedItemReviewTransfers
     */
    public function applyApprovedChanges(
        RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer,
        array $acceptedItemReviewTransfers,
        ?string $scope,
    ): void;
}
