<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment;

use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;

interface AddedItemShipmentResolverInterface
{
    /**
     * @param array<int, \Generated\Shared\Transfer\RecurringScheduleItemAdditionTransfer> $recurringScheduleItemAdditionTransfers
     * @param array<int, string> $merchantReferenceMap
     *
     * @return array<int, \Generated\Shared\Transfer\ShipmentTransfer>
     */
    public function resolveShipments(
        array $recurringScheduleItemAdditionTransfers,
        RecurringScheduleTransfer $recurringScheduleTransfer,
        QuoteTransfer $scheduleQuoteTransfer,
        array $merchantReferenceMap,
    ): array;
}
