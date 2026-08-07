<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin;

use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemAdditionTransfer;

interface AddedItemValidatorPluginInterface
{
    /**
     * Specification:
     * - Validates one aspect of a product the customer added on the Review Required page.
     * - Runs after the item was resolved through the cart, so the given items carry the resolved prices, shipment
     *   and the data the cart item expanders added; a bundle resolves into several items.
     * - Runs after the module's own availability, price and shipment checks; only reachable when those passed.
     * - Returns an error transfer carrying the plugin's own glossary key to reject the addition, or null to accept.
     *
     * @api
     *
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     */
    public function validate(
        RecurringScheduleItemAdditionTransfer $recurringScheduleItemAdditionTransfer,
        array $itemTransfers,
    ): ?ErrorTransfer;
}
