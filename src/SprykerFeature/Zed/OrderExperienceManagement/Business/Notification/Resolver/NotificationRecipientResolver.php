<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\Resolver;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Spryker\Zed\CompanyUser\Business\CompanyUserFacadeInterface;

class NotificationRecipientResolver implements NotificationRecipientResolverInterface
{
    public function __construct(protected readonly CompanyUserFacadeInterface $companyUserFacade)
    {
    }

    public function resolveRecipient(
        CustomerTransfer $buyerCustomerTransfer,
        RecurringScheduleTransfer $recurringScheduleTransfer,
    ): ?CustomerTransfer {
        if ($buyerCustomerTransfer->getAnonymizedAt() === null) {
            return $buyerCustomerTransfer;
        }

        return $this->resolveCompanyAdminCustomer($recurringScheduleTransfer);
    }

    protected function resolveCompanyAdminCustomer(RecurringScheduleTransfer $recurringScheduleTransfer): ?CustomerTransfer
    {
        $idCompanyUser = $recurringScheduleTransfer->getIdCompanyUser();

        if ($idCompanyUser === null) {
            return null;
        }

        $companyUserTransfer = $this->companyUserFacade->getCompanyUserById($idCompanyUser);
        $idCompany = $companyUserTransfer->getFkCompany();

        if ($idCompany === null) {
            return null;
        }

        $adminCompanyUserTransfer = $this->companyUserFacade->findInitialCompanyUserByCompanyId($idCompany);

        if ($adminCompanyUserTransfer === null) {
            return null;
        }

        return $adminCompanyUserTransfer->getCustomer();
    }
}
