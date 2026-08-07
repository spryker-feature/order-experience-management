<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Indexer;

use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;

class RecurringScheduleItemIndexer implements RecurringScheduleItemIndexerInterface
{
    /**
     * @return array<string, \Generated\Shared\Transfer\RecurringScheduleItemTransfer>
     */
    public function indexByGroupKey(RecurringScheduleTransfer $recurringScheduleTransfer): array
    {
        return $this->indexByKey(
            $recurringScheduleTransfer,
            static fn (RecurringScheduleItemTransfer $recurringScheduleItemTransfer): ?string => $recurringScheduleItemTransfer->getGroupKey(),
        );
    }

    /**
     * @return array<string, \Generated\Shared\Transfer\RecurringScheduleItemTransfer>
     */
    public function indexByBundleItemIdentifier(RecurringScheduleTransfer $recurringScheduleTransfer): array
    {
        return $this->indexByKey(
            $recurringScheduleTransfer,
            static fn (RecurringScheduleItemTransfer $recurringScheduleItemTransfer): ?string => $recurringScheduleItemTransfer->getBundleItemIdentifier(),
        );
    }

    /**
     * @param callable(\Generated\Shared\Transfer\RecurringScheduleItemTransfer): ?string $keyExtractor
     *
     * @return array<string, \Generated\Shared\Transfer\RecurringScheduleItemTransfer>
     */
    protected function indexByKey(RecurringScheduleTransfer $recurringScheduleTransfer, callable $keyExtractor): array
    {
        $recurringScheduleItemsByKey = [];

        foreach ($recurringScheduleTransfer->getItems() as $recurringScheduleItemTransfer) {
            $key = $keyExtractor($recurringScheduleItemTransfer);

            if ($key === null) {
                continue;
            }

            $recurringScheduleItemsByKey[$key] = $recurringScheduleItemTransfer;
        }

        return $recurringScheduleItemsByKey;
    }
}
