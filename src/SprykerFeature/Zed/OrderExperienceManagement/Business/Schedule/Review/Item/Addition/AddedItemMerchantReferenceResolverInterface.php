<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition;

interface AddedItemMerchantReferenceResolverInterface
{
    /**
     * @param array<int, \Generated\Shared\Transfer\RecurringScheduleItemAdditionTransfer> $recurringScheduleItemAdditionTransfers
     *
     * @return array<int, string>
     */
    public function resolveMerchantReferences(array $recurringScheduleItemAdditionTransfers): array;
}
