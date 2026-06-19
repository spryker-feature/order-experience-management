<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander;

use Generated\Shared\Transfer\CustomerCollectionTransfer;
use Generated\Shared\Transfer\CustomerCriteriaFilterTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\RecurringScheduleCollectionTransfer;
use Spryker\Zed\Customer\Business\CustomerFacadeInterface;

class RecurringScheduleCustomerExpander implements RecurringScheduleCustomerExpanderInterface
{
    public function __construct(protected CustomerFacadeInterface $customerFacade)
    {
    }

    public function expandWithCustomer(
        RecurringScheduleCollectionTransfer $recurringScheduleCollectionTransfer,
    ): RecurringScheduleCollectionTransfer {
        $customerIds = $this->extractCustomerIds($recurringScheduleCollectionTransfer);

        if ($customerIds === []) {
            return $recurringScheduleCollectionTransfer;
        }

        $customerCollectionTransfer = $this->getCustomerCollection($customerIds);
        $customerNamesByIdCustomer = $this->buildCustomerNamesByIdCustomerMap($customerCollectionTransfer);

        return $this->applyCustomerNames($recurringScheduleCollectionTransfer, $customerNamesByIdCustomer);
    }

    /**
     * @param array<int> $customerIds
     */
    protected function getCustomerCollection(array $customerIds): CustomerCollectionTransfer
    {
        $customerCriteriaFilterTransfer = (new CustomerCriteriaFilterTransfer())
            ->setCustomerIds($customerIds);

        return $this->customerFacade->getCustomerCollectionByCriteria($customerCriteriaFilterTransfer);
    }

    /**
     * @return array<int, string>
     */
    protected function buildCustomerNamesByIdCustomerMap(
        CustomerCollectionTransfer $customerCollectionTransfer,
    ): array {
        $customerNamesByIdCustomer = [];

        foreach ($customerCollectionTransfer->getCustomers() as $customerTransfer) {
            $customerNamesByIdCustomer[$customerTransfer->getIdCustomerOrFail()] = $this->resolveCustomerFullName($customerTransfer);
        }

        return $customerNamesByIdCustomer;
    }

    protected function resolveCustomerFullName(CustomerTransfer $customerTransfer): string
    {
        return sprintf('%s %s', $customerTransfer->getFirstName(), $customerTransfer->getLastName());
    }

    /**
     * @param array<int, string> $customerNamesByIdCustomer
     */
    protected function applyCustomerNames(
        RecurringScheduleCollectionTransfer $recurringScheduleCollectionTransfer,
        array $customerNamesByIdCustomer,
    ): RecurringScheduleCollectionTransfer {
        foreach ($recurringScheduleCollectionTransfer->getRecurringSchedules() as $recurringScheduleTransfer) {
            $idCustomer = $recurringScheduleTransfer->getIdCustomer();

            if ($idCustomer === null || !isset($customerNamesByIdCustomer[$idCustomer])) {
                continue;
            }

            $recurringScheduleTransfer->setCreatedByName($customerNamesByIdCustomer[$idCustomer]);
        }

        return $recurringScheduleCollectionTransfer;
    }

    /**
     * @return array<int>
     */
    protected function extractCustomerIds(
        RecurringScheduleCollectionTransfer $recurringScheduleCollectionTransfer,
    ): array {
        $customerIds = [];

        foreach ($recurringScheduleCollectionTransfer->getRecurringSchedules() as $recurringScheduleTransfer) {
            $idCustomer = $recurringScheduleTransfer->getIdCustomer();

            if ($idCustomer !== null) {
                $customerIds[] = $idCustomer;
            }
        }

        return array_unique($customerIds);
    }
}
