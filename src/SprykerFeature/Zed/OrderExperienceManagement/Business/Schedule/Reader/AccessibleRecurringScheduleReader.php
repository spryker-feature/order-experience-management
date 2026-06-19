<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Reader;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\RecurringScheduleConditionsTransfer;
use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Filter\RecurringScheduleAccessFilterInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface;

class AccessibleRecurringScheduleReader implements AccessibleRecurringScheduleReaderInterface
{
    public function __construct(
        protected OrderExperienceManagementRepositoryInterface $subscriptionRepository,
        protected RecurringScheduleAccessFilterInterface $recurringScheduleAccessFilter,
    ) {
    }

    public function findAccessibleScheduleByUuid(
        string $uuid,
        int $idCustomer,
        ?CustomerTransfer $customerTransfer = null,
    ): ?RecurringScheduleTransfer {
        $customerTransfer ??= (new CustomerTransfer())->setIdCustomer($idCustomer);

        $recurringScheduleCriteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setCustomer($customerTransfer)
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())->addUuid($uuid),
            );

        $recurringScheduleCriteriaTransfer = $this->recurringScheduleAccessFilter->applyAccessFilter($recurringScheduleCriteriaTransfer);

        foreach ($this->subscriptionRepository->getRecurringScheduleCollection($recurringScheduleCriteriaTransfer)->getRecurringSchedules() as $recurringScheduleTransfer) {
            return $recurringScheduleTransfer;
        }

        return null;
    }
}
