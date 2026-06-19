<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review;

use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer;

interface ScheduleReviewBuilderInterface
{
    public function buildReview(RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer): RecurringScheduleReviewResponseTransfer;

    /**
     * @param \Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer> $acceptedItemReviewTransfers
     */
    public function buildApprovalReview(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer,
        array $acceptedItemReviewTransfers,
    ): RecurringScheduleReviewResponseTransfer;
}
