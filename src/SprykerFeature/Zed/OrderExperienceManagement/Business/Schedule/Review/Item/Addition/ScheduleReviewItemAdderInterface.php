<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition;

use Generated\Shared\Transfer\RecurringScheduleTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Scope\ScheduleReviewScopeStrategyInterface;

interface ScheduleReviewItemAdderInterface
{
    /**
     * @param array<int, array<\Generated\Shared\Transfer\ItemTransfer>> $itemTransfersByAdditionIndex
     */
    public function addItems(
        array $itemTransfersByAdditionIndex,
        RecurringScheduleTransfer $recurringScheduleTransfer,
        ScheduleReviewScopeStrategyInterface $scheduleReviewScopeStrategy,
    ): void;
}
