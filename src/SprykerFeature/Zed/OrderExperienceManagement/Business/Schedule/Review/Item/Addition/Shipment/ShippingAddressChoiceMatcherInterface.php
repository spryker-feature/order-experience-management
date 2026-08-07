<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment;

use Generated\Shared\Transfer\RecurringScheduleItemAdditionTransfer;
use Generated\Shared\Transfer\RecurringScheduleShippingAddressChoiceTransfer;

interface ShippingAddressChoiceMatcherInterface
{
    /**
     * @param array<string, \Generated\Shared\Transfer\RecurringScheduleShippingAddressChoiceTransfer> $choiceTransfers
     */
    public function findChoice(
        RecurringScheduleItemAdditionTransfer $recurringScheduleItemAdditionTransfer,
        array $choiceTransfers,
    ): ?RecurringScheduleShippingAddressChoiceTransfer;
}
