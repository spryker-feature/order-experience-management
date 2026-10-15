<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Builder;

use Generated\Shared\Transfer\RecurringScheduleTransfer;

interface PriceToleranceRowBuilderInterface
{
    /**
     * @return array<\Generated\Shared\Transfer\RecurringScheduleItemTransfer>
     */
    public function getEditableItems(RecurringScheduleTransfer $recurringScheduleTransfer): array;

    public function isProductCheckIgnored(RecurringScheduleTransfer $recurringScheduleTransfer): bool;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildProductRows(RecurringScheduleTransfer $recurringScheduleTransfer): array;
}
