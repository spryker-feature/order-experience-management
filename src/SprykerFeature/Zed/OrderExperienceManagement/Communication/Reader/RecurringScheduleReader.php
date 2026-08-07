<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Communication\Reader;

use Generated\Shared\Transfer\RecurringScheduleConditionsTransfer;
use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementFacadeInterface;

class RecurringScheduleReader
{
    public function __construct(
        protected readonly OrderExperienceManagementFacadeInterface $orderExperienceManagementFacade,
    ) {
    }

    public function findRecurringSchedule(int $idRecurringSchedule): ?RecurringScheduleTransfer
    {
        $recurringScheduleConditionsTransfer = (new RecurringScheduleConditionsTransfer())
            ->addIdRecurringSchedule($idRecurringSchedule)
            ->setIsWithItems(true)
            ->setIsWithCustomer(true)
            ->setIsWithCompany(true)
            ->setIsGroupedByGroupKey(true);

        $recurringScheduleCriteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions($recurringScheduleConditionsTransfer);

        return $this->orderExperienceManagementFacade
            ->getRecurringScheduleCollection($recurringScheduleCriteriaTransfer)
            ->getRecurringSchedules()
            ->getIterator()
            ->current() ?: null;
    }
}
