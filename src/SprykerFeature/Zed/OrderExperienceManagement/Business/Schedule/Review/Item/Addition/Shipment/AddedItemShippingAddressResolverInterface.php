<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment;

use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;

interface AddedItemShippingAddressResolverInterface
{
    /**
     * @return array<string, \Generated\Shared\Transfer\RecurringScheduleShippingAddressChoiceTransfer> Keyed by choice key.
     */
    public function getOwnedAddressChoices(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        QuoteTransfer $scheduleQuoteTransfer,
    ): array;
}
