<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\OrderExperienceManagement\FormHandler;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\RecurringScheduleConditionsTransfer;
use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use Generated\Shared\Transfer\SortTransfer;
use Spryker\Yves\Kernel\PermissionAwareTrait;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderSearchFilterSubForm;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderSearchForm;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class RecurringOrderSearchFormHandler
{
    use PermissionAwareTrait;

    public const string SCOPE_MY_SCHEDULES = 'my_schedules';

    public const string SCOPE_COMPANY = 'company';

    /**
     * @uses \Spryker\Zed\CompanySalesConnector\Communication\Plugin\Permission\SeeCompanyOrdersPermissionPlugin
     */
    public const string PERMISSION_SEE_COMPANY_ORDERS = 'SeeCompanyOrdersPermissionPlugin';

    /**
     * @uses \Spryker\Zed\CompanyBusinessUnitSalesConnector\Communication\Plugin\Permission\SeeBusinessUnitOrdersPermissionPlugin
     */
    public const string PERMISSION_SEE_BUSINESS_UNIT_ORDERS = 'SeeBusinessUnitOrdersPermissionPlugin';

    protected const string DEFAULT_ORDER_BY = 'spy_recurring_schedule.next_trigger_date';

    protected const string DEFAULT_ORDER_DIRECTION = 'ASC';

    public function buildRecurringScheduleCriteriaTransfer(
        Request $request,
        FormInterface $form,
        CustomerTransfer $customerTransfer
    ): RecurringScheduleCriteriaTransfer {
        $recurringScheduleConditionsTransfer = new RecurringScheduleConditionsTransfer();
        $recurringScheduleCriteriaTransfer = new RecurringScheduleCriteriaTransfer();

        $rawFormData = $request->query->all()[RecurringOrderSearchForm::FORM_NAME] ?? [];

        if (!empty($rawFormData[RecurringOrderSearchForm::FIELD_RESET])) {
            $this->applyScope($recurringScheduleConditionsTransfer, static::SCOPE_MY_SCHEDULES, $customerTransfer);

            return $recurringScheduleCriteriaTransfer
                ->setRecurringScheduleConditions($recurringScheduleConditionsTransfer)
                ->addSort($this->buildDefaultSort());
        }

        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->applyScope($recurringScheduleConditionsTransfer, static::SCOPE_MY_SCHEDULES, $customerTransfer);

            return $recurringScheduleCriteriaTransfer
                ->setRecurringScheduleConditions($recurringScheduleConditionsTransfer)
                ->addSort($this->buildDefaultSort());
        }

        $filtersForm = $form->get(RecurringOrderSearchForm::FIELD_FILTERS);
        $scope = $filtersForm->has(RecurringOrderSearchFilterSubForm::FIELD_SCOPE)
            ? (string)($filtersForm->get(RecurringOrderSearchFilterSubForm::FIELD_SCOPE)->getData() ?? static::SCOPE_MY_SCHEDULES)
            : static::SCOPE_MY_SCHEDULES;
        $status = (string)($filtersForm->get(RecurringOrderSearchFilterSubForm::FIELD_STATUS)->getData() ?? '');
        $search = (string)($filtersForm->get(RecurringOrderSearchFilterSubForm::FIELD_SEARCH)->getData() ?? '');

        $this->applyScope($recurringScheduleConditionsTransfer, $scope, $customerTransfer);

        if ($status !== '') {
            $recurringScheduleConditionsTransfer->addStatus($status);
        }

        if ($search !== '') {
            $recurringScheduleConditionsTransfer->addName($search);

            if (is_numeric($search)) {
                $recurringScheduleConditionsTransfer->addIdRecurringSchedule((int)$search);
            }
        }

        return $recurringScheduleCriteriaTransfer
            ->setRecurringScheduleConditions($recurringScheduleConditionsTransfer)
            ->addSort($this->buildSort($form));
    }

    protected function applyScope(
        RecurringScheduleConditionsTransfer $recurringScheduleConditionsTransfer,
        string $scope,
        CustomerTransfer $customerTransfer
    ): void {
        $companyUserTransfer = $customerTransfer->getCompanyUserTransfer();

        if ($scope === static::SCOPE_COMPANY && $companyUserTransfer !== null && $this->can(static::PERMISSION_SEE_COMPANY_ORDERS)) {
            $recurringScheduleConditionsTransfer->addCompanyId($companyUserTransfer->getFkCompanyOrFail());

            return;
        }

        if ($scope !== static::SCOPE_MY_SCHEDULES && is_numeric($scope) && $companyUserTransfer !== null && $this->can(static::PERMISSION_SEE_BUSINESS_UNIT_ORDERS)) {
            $recurringScheduleConditionsTransfer->addCompanyBusinessUnitId((int)$scope);

            return;
        }

        $recurringScheduleConditionsTransfer->addCustomerId($customerTransfer->getIdCustomerOrFail());
    }

    protected function buildDefaultSort(): SortTransfer
    {
        return (new SortTransfer())
            ->setField(static::DEFAULT_ORDER_BY)
            ->setIsAscending(true);
    }

    protected function buildSort(FormInterface $form): SortTransfer
    {
        $orderBy = (string)($form->get(RecurringOrderSearchForm::FIELD_ORDER_BY)->getData() ?? '');
        $orderDirection = (string)($form->get(RecurringOrderSearchForm::FIELD_ORDER_DIRECTION)->getData() ?? static::DEFAULT_ORDER_DIRECTION);

        return (new SortTransfer())
            ->setField($orderBy ?: static::DEFAULT_ORDER_BY)
            ->setIsAscending(strtoupper($orderDirection) !== 'DESC');
    }
}
