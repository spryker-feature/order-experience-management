<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Reader;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\RecurringScheduleCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleConditionsTransfer;
use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use Generated\Shared\Transfer\RecurringScheduleStatusCountCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander\RecurringScheduleExpanderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Filter\RecurringScheduleAccessFilterInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface;

class RecurringScheduleReader implements RecurringScheduleReaderInterface
{
    public function __construct(
        protected readonly OrderExperienceManagementRepositoryInterface $repository,
        protected readonly RecurringScheduleAccessFilterInterface $recurringScheduleAccessFilter,
        protected readonly RecurringScheduleExpanderInterface $recurringScheduleExpander,
    ) {
    }

    public function getRecurringScheduleCollection(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer,
    ): RecurringScheduleCollectionTransfer {
        $recurringScheduleCriteriaTransfer = $this->recurringScheduleAccessFilter->applyAccessFilter($recurringScheduleCriteriaTransfer);

        $recurringScheduleCollectionTransfer = $this->repository->getRecurringScheduleCollection($recurringScheduleCriteriaTransfer);

        return $this->recurringScheduleExpander->expand($recurringScheduleCollectionTransfer, $recurringScheduleCriteriaTransfer);
    }

    public function getRecurringScheduleStatusCountCollection(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer,
    ): RecurringScheduleStatusCountCollectionTransfer {
        return $this->repository->getRecurringScheduleStatusCountCollection($recurringScheduleCriteriaTransfer);
    }

    public function findRecurringScheduleByCriteria(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer,
    ): ?RecurringScheduleTransfer {
        return $this->getRecurringScheduleCollection($recurringScheduleCriteriaTransfer)
            ->getRecurringSchedules()
            ->getIterator()
            ->current();
    }

    public function findRecurringScheduleByUuid(
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

        return $this->findRecurringScheduleByCriteria($recurringScheduleCriteriaTransfer);
    }
}
