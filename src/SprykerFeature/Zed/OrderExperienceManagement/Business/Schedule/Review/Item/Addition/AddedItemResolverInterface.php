<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition;

use Generated\Shared\Transfer\RecurringScheduleTransfer;

interface AddedItemResolverInterface
{
    /**
     * @param array<int, \Generated\Shared\Transfer\RecurringScheduleItemAdditionTransfer> $recurringScheduleItemAdditionTransfers
     *
     * @return array<int, array<\Generated\Shared\Transfer\ItemTransfer>> Keyed by the addition's array index.
     */
    public function resolveAddedItems(
        array $recurringScheduleItemAdditionTransfers,
        RecurringScheduleTransfer $recurringScheduleTransfer,
    ): array;
}
