<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Filter;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\RecurringScheduleConditionsTransfer;
use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use Spryker\Zed\Kernel\PermissionAwareTrait;

class RecurringScheduleAccessFilter implements RecurringScheduleAccessFilterInterface
{
    use PermissionAwareTrait;

    /**
     * @uses \Spryker\Zed\CompanySalesConnector\Communication\Plugin\Permission\SeeCompanyOrdersPermissionPlugin
     */
    protected const string PERMISSION_SEE_COMPANY_ORDERS = 'SeeCompanyOrdersPermissionPlugin';

    /**
     * @uses \Spryker\Zed\CompanyBusinessUnitSalesConnector\Communication\Plugin\Permission\SeeBusinessUnitOrdersPermissionPlugin
     */
    protected const string PERMISSION_SEE_BUSINESS_UNIT_ORDERS = 'SeeBusinessUnitOrdersPermissionPlugin';

    public function applyAccessFilter(RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer): RecurringScheduleCriteriaTransfer
    {
        $customerTransfer = $recurringScheduleCriteriaTransfer->getCustomer();

        if ($customerTransfer === null) {
            return $recurringScheduleCriteriaTransfer;
        }

        $recurringScheduleConditionsTransfer = $recurringScheduleCriteriaTransfer->getRecurringScheduleConditions()
            ?? new RecurringScheduleConditionsTransfer();

        return $recurringScheduleCriteriaTransfer->setRecurringScheduleConditions(
            $this->applyOwnershipScope($recurringScheduleConditionsTransfer, $customerTransfer),
        );
    }

    protected function applyOwnershipScope(
        RecurringScheduleConditionsTransfer $recurringScheduleConditionsTransfer,
        CustomerTransfer $customerTransfer,
    ): RecurringScheduleConditionsTransfer {
        $companyUserTransfer = $customerTransfer->getCompanyUserTransfer();

        if ($companyUserTransfer === null) {
            return $recurringScheduleConditionsTransfer->addCustomerId($customerTransfer->getIdCustomerOrFail());
        }

        $idCompanyUser = $companyUserTransfer->getIdCompanyUserOrFail();

        if ($this->can(static::PERMISSION_SEE_COMPANY_ORDERS, $idCompanyUser)) {
            return $recurringScheduleConditionsTransfer->addCompanyId($companyUserTransfer->getFkCompanyOrFail());
        }

        $idCompanyBusinessUnit = $companyUserTransfer->getFkCompanyBusinessUnit();

        if ($idCompanyBusinessUnit !== null && $this->can(static::PERMISSION_SEE_BUSINESS_UNIT_ORDERS, $idCompanyUser)) {
            return $recurringScheduleConditionsTransfer->addCompanyBusinessUnitId($idCompanyBusinessUnit);
        }

        return $recurringScheduleConditionsTransfer->addCustomerId($customerTransfer->getIdCustomerOrFail());
    }
}
