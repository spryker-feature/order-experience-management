<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item;

use Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer;

interface ScheduleReviewItemRemoverInterface
{
    public function remove(RecurringScheduleItemReviewTransfer $recurringScheduleItemReviewTransfer, int $idRecurringSchedule): void;

    public function skipForNextOrder(RecurringScheduleItemReviewTransfer $recurringScheduleItemReviewTransfer, int $idRecurringSchedule): void;
}
