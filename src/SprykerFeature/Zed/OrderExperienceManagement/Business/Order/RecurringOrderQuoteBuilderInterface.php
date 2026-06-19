<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Order;

use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;

interface RecurringOrderQuoteBuilderInterface
{
    /**
     * Reconstructs a clean QuoteTransfer ready for checkout operations:
     * - Deserializes quoteData JSON into QuoteTransfer.
     * - Strips all stale persistence IDs (quote, address, expense, shipment).
     * - Re-attaches items from the schedule's item rows, recovering shipment method IDs from expenses.
     * - When $isPlacement is true, applies placement-only adjustments such as expanding packaging-unit
     *   items into quantity-1 items; validation and review flows keep the grouped representation.
     */
    public function buildPlaceableQuote(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        bool $isPlacement = false,
    ): QuoteTransfer;
}
