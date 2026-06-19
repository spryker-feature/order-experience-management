<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander;

use Generated\Shared\Transfer\PaginationTransfer;
use Generated\Shared\Transfer\RecurringScheduleCollectionTransfer;

interface RecurringScheduleHistoryExpanderInterface
{
    public function expandWithHistory(
        RecurringScheduleCollectionTransfer $recurringScheduleCollectionTransfer,
        ?PaginationTransfer $historyPaginationTransfer = null,
    ): RecurringScheduleCollectionTransfer;
}
