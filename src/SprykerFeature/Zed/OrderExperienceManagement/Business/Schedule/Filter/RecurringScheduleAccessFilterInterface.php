<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Filter;

use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;

interface RecurringScheduleAccessFilterInterface
{
    public function applyAccessFilter(RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer): RecurringScheduleCriteriaTransfer;
}
