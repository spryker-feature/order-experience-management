<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Communication\Form\DataProvider;

use Generated\Shared\Transfer\RecurringScheduleTableFilterTransfer;
use Spryker\Zed\Company\Business\CompanyFacadeInterface;
use Spryker\Zed\CompanyBusinessUnit\Business\CompanyBusinessUnitFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Form\RecurringScheduleTableFilterForm;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;
use Symfony\Component\HttpFoundation\Request;

class RecurringScheduleTableFilterFormDataProvider
{
    public const string OPTION_STATUSES = 'statuses';

    public const string OPTION_CADENCE_TYPES = 'cadence_types';

    public const string OPTION_COMPANY_CHOICES = 'company_choices';

    public const string OPTION_COMPANY_BUSINESS_UNIT_CHOICES = 'company_business_unit_choices';

    public function __construct(
        protected readonly OrderExperienceManagementConfig $config,
        protected readonly CompanyFacadeInterface $companyFacade,
        protected readonly CompanyBusinessUnitFacadeInterface $companyBusinessUnitFacade
    ) {
    }

    public function getData(): RecurringScheduleTableFilterTransfer
    {
        return new RecurringScheduleTableFilterTransfer();
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(Request $request): array
    {
        $filterData = $request->query->all(RecurringScheduleTableFilterForm::BLOCK_PREFIX);

        $idCompany = isset($filterData[RecurringScheduleTableFilterForm::FIELD_ID_COMPANY])
            ? (int)$filterData[RecurringScheduleTableFilterForm::FIELD_ID_COMPANY]
            : null;
        $idCompanyBusinessUnit = isset($filterData[RecurringScheduleTableFilterForm::FIELD_ID_COMPANY_BUSINESS_UNIT])
            ? (int)$filterData[RecurringScheduleTableFilterForm::FIELD_ID_COMPANY_BUSINESS_UNIT]
            : null;

        return [
            static::OPTION_STATUSES => $this->config->getBackOfficeFilterStatuses(),
            static::OPTION_CADENCE_TYPES => $this->config->getBackOfficeFilterCadenceTypes(),
            static::OPTION_COMPANY_CHOICES => $this->getCompanyChoices($idCompany),
            static::OPTION_COMPANY_BUSINESS_UNIT_CHOICES => $this->getCompanyBusinessUnitChoices($idCompanyBusinessUnit),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function getCompanyChoices(?int $idCompany): array
    {
        if ($idCompany === null) {
            return [];
        }

        $companyTransfer = $this->companyFacade->findCompanyById($idCompany);

        if ($companyTransfer === null) {
            return [];
        }

        return [$companyTransfer->getNameOrFail() => $idCompany];
    }

    /**
     * @return array<string, int>
     */
    public function getCompanyBusinessUnitChoices(?int $idCompanyBusinessUnit): array
    {
        if ($idCompanyBusinessUnit === null) {
            return [];
        }

        $companyBusinessUnitTransfer = $this->companyBusinessUnitFacade->findCompanyBusinessUnitById($idCompanyBusinessUnit);

        if ($companyBusinessUnitTransfer === null) {
            return [];
        }

        return [$companyBusinessUnitTransfer->getNameOrFail() => $idCompanyBusinessUnit];
    }
}
