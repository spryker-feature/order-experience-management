<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Resolver;

use Generated\Shared\Transfer\CustomerCriteriaTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\OrderIntakeRequestTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\OrderIntakeValidationIssueTransfer;
use Spryker\Zed\CompanyUser\Business\CompanyUserFacadeInterface;
use Spryker\Zed\Customer\Business\CustomerFacadeInterface;

class OrderIntakeCustomerResolver implements OrderIntakeCustomerResolverInterface
{
    protected const string FIELD_CUSTOMER_REFERENCE = 'customerReference';

    protected const string FIELD_COMPANY_BUSINESS_UNIT_UUID = 'companyBusinessUnitUuid';

    protected const string MESSAGE_CUSTOMER_REFERENCE_REQUIRED = 'A customerReference is required. Order intake places orders for existing customers only.';

    protected const string MESSAGE_CUSTOMER_REFERENCE_UNKNOWN = 'Customer "%customerReference%" was not found.';

    protected const string MESSAGE_BUSINESS_UNIT_NOT_OWNED = 'Customer "%customerReference%" has no active company user in company business unit "%companyBusinessUnitUuid%".';

    protected const string MESSAGE_BUSINESS_UNIT_AMBIGUOUS = 'Customer "%customerReference%" has several active company users in company business unit "%companyBusinessUnitUuid%", so the one to buy as cannot be determined.';

    public function __construct(
        protected readonly CustomerFacadeInterface $customerFacade,
        protected readonly CompanyUserFacadeInterface $companyUserFacade,
    ) {
    }

    public function resolveCustomer(
        OrderIntakeRequestTransfer $orderIntakeRequestTransfer,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
    ): OrderIntakeRequestTransfer {
        $customerReference = $orderIntakeRequestTransfer->getCustomer()?->getCustomerReference();

        if ($customerReference === null || $customerReference === '') {
            return $this->reject(
                $orderIntakeRequestTransfer,
                $orderIntakeResponseTransfer,
                static::FIELD_CUSTOMER_REFERENCE,
                static::MESSAGE_CUSTOMER_REFERENCE_REQUIRED,
            );
        }

        $storedCustomerTransfer = $this->findCustomerByReference($customerReference);

        if ($storedCustomerTransfer === null) {
            return $this->reject(
                $orderIntakeRequestTransfer,
                $orderIntakeResponseTransfer,
                static::FIELD_CUSTOMER_REFERENCE,
                static::MESSAGE_CUSTOMER_REFERENCE_UNKNOWN,
                ['%customerReference%' => $customerReference],
            );
        }

        $orderIntakeRequestTransfer->setCustomer($storedCustomerTransfer);

        return $this->resolveCompanyUserByBusinessUnit($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);
    }

    protected function findCustomerByReference(string $customerReference): ?CustomerTransfer
    {
        $customerResponseTransfer = $this->customerFacade->getCustomerByCriteria(
            (new CustomerCriteriaTransfer())
                ->setCustomerReference($customerReference)
                ->setWithExpanders(true),
        );

        if ($customerResponseTransfer->getHasCustomer() !== true) {
            return null;
        }

        return $customerResponseTransfer->getCustomerTransfer();
    }

    protected function resolveCompanyUserByBusinessUnit(
        OrderIntakeRequestTransfer $orderIntakeRequestTransfer,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
    ): OrderIntakeRequestTransfer {
        $companyBusinessUnitUuid = $orderIntakeRequestTransfer->getCompanyBusinessUnitUuid();

        if ($companyBusinessUnitUuid === null || $companyBusinessUnitUuid === '') {
            return $orderIntakeRequestTransfer;
        }

        $customerTransfer = $orderIntakeRequestTransfer->getCustomerOrFail();
        $companyUserTransfers = $this->findActiveCompanyUsersInBusinessUnit(
            $customerTransfer,
            $companyBusinessUnitUuid,
        );

        if ($companyUserTransfers === []) {
            return $this->reject(
                $orderIntakeRequestTransfer,
                $orderIntakeResponseTransfer,
                static::FIELD_COMPANY_BUSINESS_UNIT_UUID,
                static::MESSAGE_BUSINESS_UNIT_NOT_OWNED,
                [
                    '%customerReference%' => (string)$customerTransfer->getCustomerReference(),
                    '%companyBusinessUnitUuid%' => $companyBusinessUnitUuid,
                ],
            );
        }

        if (count($companyUserTransfers) > 1) {
            return $this->reject(
                $orderIntakeRequestTransfer,
                $orderIntakeResponseTransfer,
                static::FIELD_COMPANY_BUSINESS_UNIT_UUID,
                static::MESSAGE_BUSINESS_UNIT_AMBIGUOUS,
                [
                    '%customerReference%' => (string)$customerTransfer->getCustomerReference(),
                    '%companyBusinessUnitUuid%' => $companyBusinessUnitUuid,
                ],
            );
        }

        return $orderIntakeRequestTransfer->setCustomer(
            $customerTransfer->setCompanyUserTransfer($companyUserTransfers[0]),
        );
    }

    /**
     * @return array<int, \Generated\Shared\Transfer\CompanyUserTransfer>
     */
    protected function findActiveCompanyUsersInBusinessUnit(
        CustomerTransfer $customerTransfer,
        string $companyBusinessUnitUuid,
    ): array {
        $companyUserCollectionTransfer = $this->companyUserFacade->getActiveCompanyUsersByCustomerReference(
            (new CustomerTransfer())->setCustomerReference($customerTransfer->getCustomerReference()),
        );

        $matchedCompanyUserTransfers = [];

        foreach ($companyUserCollectionTransfer->getCompanyUsers() as $companyUserTransfer) {
            if ($companyUserTransfer->getCompanyBusinessUnit()?->getUuid() === $companyBusinessUnitUuid) {
                $matchedCompanyUserTransfers[] = $companyUserTransfer;
            }
        }

        return $matchedCompanyUserTransfers;
    }

    /**
     * @param array<string, string> $parameters
     */
    protected function reject(
        OrderIntakeRequestTransfer $orderIntakeRequestTransfer,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        string $field,
        string $message,
        array $parameters = [],
    ): OrderIntakeRequestTransfer {
        $orderIntakeResponseTransfer->addValidationIssue(
            (new OrderIntakeValidationIssueTransfer())
                ->setField($field)
                ->setMessage($message)
                ->setParameters($parameters),
        );

        return $orderIntakeRequestTransfer;
    }
}
