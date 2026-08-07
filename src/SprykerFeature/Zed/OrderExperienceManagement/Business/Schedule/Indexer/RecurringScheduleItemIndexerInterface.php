<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Indexer;

use Generated\Shared\Transfer\RecurringScheduleTransfer;

interface RecurringScheduleItemIndexerInterface
{
    /**
     * @return array<string, \Generated\Shared\Transfer\RecurringScheduleItemTransfer>
     */
    public function indexByGroupKey(RecurringScheduleTransfer $recurringScheduleTransfer): array;

    /**
     * @return array<string, \Generated\Shared\Transfer\RecurringScheduleItemTransfer>
     */
    public function indexByBundleItemIdentifier(RecurringScheduleTransfer $recurringScheduleTransfer): array;
}
