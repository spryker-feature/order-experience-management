<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition;

use Generated\Shared\Transfer\QuoteTransfer;

interface AddedItemCartAdderInterface
{
    /**
     * @param array<int, \Generated\Shared\Transfer\RecurringScheduleItemAdditionTransfer> $recurringScheduleItemAdditionTransfers
     * @param array<int, string> $merchantReferenceMap
     *
     * @return array<int, array<\Generated\Shared\Transfer\ItemTransfer>>
     */
    public function addItems(
        array $recurringScheduleItemAdditionTransfers,
        array $merchantReferenceMap,
        QuoteTransfer $scheduleQuoteTransfer,
    ): array;
}
