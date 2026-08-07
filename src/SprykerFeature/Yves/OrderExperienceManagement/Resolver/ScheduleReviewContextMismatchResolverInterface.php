<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Resolver;

use Generated\Shared\Transfer\RecurringScheduleTransfer;

interface ScheduleReviewContextMismatchResolverInterface
{
    public function isCurrencyMismatch(RecurringScheduleTransfer $recurringScheduleTransfer): bool;

    public function isPriceModeMismatch(RecurringScheduleTransfer $recurringScheduleTransfer): bool;
}
