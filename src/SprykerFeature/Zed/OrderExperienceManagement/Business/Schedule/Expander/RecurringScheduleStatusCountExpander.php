<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander;

use Generated\Shared\Transfer\RecurringScheduleCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface;

class RecurringScheduleStatusCountExpander implements RecurringScheduleExpanderInterface
{
    public function __construct(protected readonly OrderExperienceManagementRepositoryInterface $repository)
    {
    }

    public function isApplicable(RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer): bool
    {
        return $recurringScheduleCriteriaTransfer->getStatusCountConditions() !== null;
    }

    public function expand(
        RecurringScheduleCollectionTransfer $recurringScheduleCollectionTransfer,
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer,
    ): RecurringScheduleCollectionTransfer {
        $statusCountCriteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions($recurringScheduleCriteriaTransfer->getStatusCountConditionsOrFail());

        $recurringScheduleStatusCountCollectionTransfer = $this->repository
            ->getRecurringScheduleStatusCountCollection($statusCountCriteriaTransfer);

        return $recurringScheduleCollectionTransfer
            ->setStatusCounts($recurringScheduleStatusCountCollectionTransfer->getStatusCounts());
    }
}
