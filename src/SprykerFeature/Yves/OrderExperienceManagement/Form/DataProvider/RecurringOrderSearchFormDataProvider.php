<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\OrderExperienceManagement\Form\DataProvider;

use Generated\Shared\Transfer\CompanyBusinessUnitCriteriaFilterTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Spryker\Client\CompanyBusinessUnit\CompanyBusinessUnitClientInterface;
use Spryker\Yves\Kernel\PermissionAwareTrait;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderSearchForm;
use SprykerFeature\Yves\OrderExperienceManagement\FormHandler\RecurringOrderSearchFormHandler;
use SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig;

class RecurringOrderSearchFormDataProvider
{
    use PermissionAwareTrait;

    protected const string GLOSSARY_KEY_SCOPE_MY_SCHEDULES = 'recurring_orders.list.scope.my_schedules';

    protected const string GLOSSARY_KEY_SCOPE_COMPANY = 'recurring_orders.list.scope.company';

    protected const string GLOSSARY_KEY_SCOPE_MY_BUSINESS_UNIT = 'recurring_orders.list.scope.my_business_unit';

    public function __construct(
        protected readonly OrderExperienceManagementConfig $subscriptionConfig,
        protected readonly CompanyBusinessUnitClientInterface $companyBusinessUnitClient,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(CustomerTransfer $customerTransfer): array
    {
        return [
            RecurringOrderSearchForm::OPTION_SCOPE_CHOICES => $this->buildScopeChoices($customerTransfer),
            RecurringOrderSearchForm::OPTION_STATUS_CHOICES => $this->subscriptionConfig->getRecurringScheduleStatusChoices(),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function buildScopeChoices(CustomerTransfer $customerTransfer): array
    {
        $choices = [
            static::GLOSSARY_KEY_SCOPE_MY_SCHEDULES => RecurringOrderSearchFormHandler::SCOPE_MY_SCHEDULES,
        ];

        $companyUserTransfer = $customerTransfer->getCompanyUserTransfer();
        if ($companyUserTransfer === null) {
            return $choices;
        }

        if ($this->can(RecurringOrderSearchFormHandler::PERMISSION_SEE_COMPANY_ORDERS)) {
            $choices[static::GLOSSARY_KEY_SCOPE_COMPANY] = RecurringOrderSearchFormHandler::SCOPE_COMPANY;

            return $this->addAllCompanyBusinessUnitChoices($choices, $companyUserTransfer->getFkCompanyOrFail());
        }

        if ($this->can(RecurringOrderSearchFormHandler::PERMISSION_SEE_BUSINESS_UNIT_ORDERS)) {
            $idCompanyBusinessUnit = $companyUserTransfer->getFkCompanyBusinessUnit();

            if ($idCompanyBusinessUnit !== null) {
                $buName = $companyUserTransfer->getCompanyBusinessUnit()?->getName()
                    ?? static::GLOSSARY_KEY_SCOPE_MY_BUSINESS_UNIT;
                $choices[$buName] = (string)$idCompanyBusinessUnit;
            }
        }

        return $choices;
    }

    /**
     * @param array<string, string> $choices
     *
     * @return array<string, string>
     */
    protected function addAllCompanyBusinessUnitChoices(array $choices, int $idCompany): array
    {
        $companyBusinessUnitCriteriaFilterTransfer = (new CompanyBusinessUnitCriteriaFilterTransfer())
            ->setIdCompany($idCompany);

        $companyBusinessUnitCollectionTransfer = $this->companyBusinessUnitClient
            ->getCompanyBusinessUnitCollection($companyBusinessUnitCriteriaFilterTransfer);

        foreach ($companyBusinessUnitCollectionTransfer->getCompanyBusinessUnits() as $companyBusinessUnitTransfer) {
            $idBusinessUnit = $companyBusinessUnitTransfer->getIdCompanyBusinessUnit();
            if ($idBusinessUnit === null || in_array((string)$idBusinessUnit, $choices, true)) {
                continue;
            }

            $choices[$companyBusinessUnitTransfer->getNameOrFail()] = (string)$idBusinessUnit;
        }

        return $choices;
    }
}
