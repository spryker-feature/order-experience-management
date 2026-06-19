<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Reader;

use Generated\Shared\Transfer\RecurringScheduleCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use Generated\Shared\Transfer\RecurringScheduleStatusCountCollectionTransfer;

interface RecurringScheduleReaderInterface
{
    public function getRecurringScheduleCollection(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer
    ): RecurringScheduleCollectionTransfer;

    public function getRecurringScheduleStatusCountCollection(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer
    ): RecurringScheduleStatusCountCollectionTransfer;
}
