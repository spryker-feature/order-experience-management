<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Reader;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\PaginationTransfer;
use Generated\Shared\Transfer\RecurringScheduleCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;

interface RecurringScheduleReaderInterface
{
    public function findScheduleDetail(
        string $uuid,
        CustomerTransfer $customerTransfer,
        ?PaginationTransfer $historyPaginationTransfer = null,
    ): ?RecurringScheduleTransfer;

    public function findScheduleReview(
        string $uuid,
        CustomerTransfer $customerTransfer,
    ): RecurringScheduleReviewResponseTransfer;

    public function getScheduleCollection(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer,
    ): RecurringScheduleCollectionTransfer;
}
