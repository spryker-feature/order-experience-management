<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Intake\Resolver;

use ArrayObject;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\CompanyBusinessUnitTransfer;
use Generated\Shared\Transfer\CompanyUserCollectionTransfer;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\CustomerCriteriaTransfer;
use Generated\Shared\Transfer\CustomerResponseTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\OrderIntakeRequestTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Spryker\Zed\CompanyUser\Business\CompanyUserFacadeInterface;
use Spryker\Zed\Customer\Business\CustomerFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Resolver\OrderIntakeCustomerResolver;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Business
 * @group Intake
 * @group Resolver
 * @group OrderIntakeCustomerResolverTest
 * Add your own group annotations below this line
 */
class OrderIntakeCustomerResolverTest extends Unit
{
    protected const int ID_CUSTOMER = 21;

    protected const string EXISTING_CUSTOMER_REFERENCE = 'DE--21';

    protected const string EXISTING_CUSTOMER_EMAIL = 'sonia@acme.com';

    protected const string UNKNOWN_CUSTOMER_REFERENCE = 'TOTALLY-MADE-UP-REF';

    protected const string BUSINESS_UNIT_UUID = '9c1e0b2a-4f3d-4a91-8c77-1d5b6e2f0a34';

    protected const string OTHER_BUSINESS_UNIT_UUID = 'b7c3f1d8-2e94-4a0b-8f61-3c5d9e7a2b40';

    protected const string COMPANY_USER_UUID = '0d1a1ac4-7d3e-4c8c-9a4f-2b6b7c1d5e10';

    /**
     * The STORED customer replaces the payload's copy: `CustomerOrderSaver` writes the transfer back
     * over the customer row, so a partial payload would blank out fields the caller never sent.
     */
    public function testResolveCustomerReplacesRequestCustomerWithTheStoredOne(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = $this->createRequest(static::EXISTING_CUSTOMER_REFERENCE);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $orderIntakeRequestTransfer = $this->createResolver($this->createStoredCustomer())
            ->resolveCustomer($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);

        // Assert
        $customerTransfer = $orderIntakeRequestTransfer->getCustomerOrFail();

        $this->assertSame(static::ID_CUSTOMER, $customerTransfer->getIdCustomer());
        $this->assertSame(static::EXISTING_CUSTOMER_EMAIL, $customerTransfer->getEmail());
        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
    }

    /**
     * The read has to be the expanding one. `findCustomerByReference` attaches addresses and nothing
     * else, so neither the company user — which decides whether contract prices apply — nor the
     * address book the uuid form is matched against would reach the rest of the pipeline.
     */
    public function testResolveCustomerReadsTheStoredCustomerThroughTheExpanderPluginStack(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = $this->createRequest(static::EXISTING_CUSTOMER_REFERENCE);

        $customerFacadeMock = $this->createMock(CustomerFacadeInterface::class);
        $customerFacadeMock->expects($this->once())
            ->method('getCustomerByCriteria')
            ->with($this->callback(function (CustomerCriteriaTransfer $customerCriteriaTransfer): bool {
                return $customerCriteriaTransfer->getCustomerReference() === static::EXISTING_CUSTOMER_REFERENCE
                    && $customerCriteriaTransfer->getWithExpanders() === true;
            }))
            ->willReturn(
                (new CustomerResponseTransfer())->setHasCustomer(true)->setCustomerTransfer($this->createStoredCustomer()),
            );

        // Act
        $orderIntakeRequestTransfer = (new OrderIntakeCustomerResolver(
            $customerFacadeMock,
            $this->createMock(CompanyUserFacadeInterface::class),
        ))->resolveCustomer($orderIntakeRequestTransfer, new OrderIntakeResponseTransfer());

        // Assert
        $this->assertSame(static::ID_CUSTOMER, $orderIntakeRequestTransfer->getCustomerOrFail()->getIdCustomer());
    }

    /**
     * The surface places orders for customers that already exist. Registering one from an order
     * payload is what makes a typo turn into a duplicate account nobody notices.
     *
     * @dataProvider providePayloadsWithoutACustomerReference
     */
    public function testResolveCustomerRejectsAPayloadThatNamesNoCustomer(?CustomerTransfer $customerTransfer): void
    {
        // Arrange
        $orderIntakeRequestTransfer = (new OrderIntakeRequestTransfer())->setCustomer($customerTransfer);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createResolver(null)->resolveCustomer($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertCount(1, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertSame(
            'customerReference',
            $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0)->getField(),
        );
    }

    /**
     * @return array<string, array<int, \Generated\Shared\Transfer\CustomerTransfer|null>>
     */
    public function providePayloadsWithoutACustomerReference(): array
    {
        return [
            'no customer at all' => [null],

            'customer without a reference' => [new CustomerTransfer()],

            'empty reference' => [(new CustomerTransfer())->setCustomerReference('')],

            // The guest case: contact details but no identity the platform already knows.
            'email only' => [(new CustomerTransfer())->setEmail('guest@example.com')],
        ];
    }

    public function testResolveCustomerRejectsAnUnknownCustomerReference(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = $this->createRequest(static::UNKNOWN_CUSTOMER_REFERENCE);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createResolver(null)->resolveCustomer($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);

        // Assert
        $validationIssueTransfer = $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0);

        $this->assertCount(1, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertSame('customerReference', $validationIssueTransfer->getField());
        $this->assertSame(static::UNKNOWN_CUSTOMER_REFERENCE, $validationIssueTransfer->getParameters()['%customerReference%']);
    }

    /**
     * The point of the field: a buyer belonging to several business units sits on several contracts,
     * and the expander stack refuses to guess between them. Naming the unit picks the company user
     * inside it, which is what carries the merchant relationships pricing reads.
     */
    public function testResolveCustomerAttachesTheCompanyUserInTheNamedBusinessUnit(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = $this->createRequest(
            static::EXISTING_CUSTOMER_REFERENCE,
            static::BUSINESS_UNIT_UUID,
        );
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $orderIntakeRequestTransfer = $this->createResolver($this->createStoredCustomer(), [
            $this->createCompanyUser(static::OTHER_BUSINESS_UNIT_UUID, 'a-different-company-user'),
            $this->createCompanyUser(static::BUSINESS_UNIT_UUID, static::COMPANY_USER_UUID),
        ])->resolveCustomer($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);

        // Assert
        $companyUserTransfer = $orderIntakeRequestTransfer->getCustomerOrFail()->getCompanyUserTransfer();

        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertSame(static::COMPANY_USER_UUID, $companyUserTransfer?->getUuid());
        $this->assertSame(
            static::BUSINESS_UNIT_UUID,
            $companyUserTransfer?->getCompanyBusinessUnit()?->getUuid(),
        );
    }

    /**
     * The stored customer replaces the payload's copy wholesale. Carrying the selector on the request
     * root rather than inside `customer` is what makes that harmless — there is nothing on the
     * customer transfer left to lose — and the company user is attached to the replacement, not to
     * the copy that was thrown away.
     */
    public function testResolveCustomerAttachesTheCompanyUserToTheStoredCustomer(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = $this->createRequest(
            static::EXISTING_CUSTOMER_REFERENCE,
            static::BUSINESS_UNIT_UUID,
        );

        // Act
        $orderIntakeRequestTransfer = $this->createResolver($this->createStoredCustomer(), [
            $this->createCompanyUser(static::BUSINESS_UNIT_UUID, static::COMPANY_USER_UUID),
        ])->resolveCustomer($orderIntakeRequestTransfer, new OrderIntakeResponseTransfer());

        // Assert
        $customerTransfer = $orderIntakeRequestTransfer->getCustomerOrFail();

        $this->assertSame(static::EXISTING_CUSTOMER_EMAIL, $customerTransfer->getEmail());
        $this->assertSame(static::COMPANY_USER_UUID, $customerTransfer->getCompanyUserTransfer()?->getUuid());
    }

    /**
     * Reading the customer's OWN active company users is the ownership guard: a business unit they
     * have no membership in simply produces no match, so a caller cannot price this order against
     * another company's contract by naming a business unit uuid they happen to know. Ignoring the
     * field instead would place the order at the operator's list price while the caller believes a
     * contract applied.
     *
     * @dataProvider provideCompanyUsersOutsideTheNamedBusinessUnit
     *
     * @param array<int, \Generated\Shared\Transfer\CompanyUserTransfer> $companyUserTransfers
     */
    public function testResolveCustomerRejectsABusinessUnitTheCustomerDoesNotBelongTo(array $companyUserTransfers): void
    {
        // Arrange
        $orderIntakeRequestTransfer = $this->createRequest(
            static::EXISTING_CUSTOMER_REFERENCE,
            static::BUSINESS_UNIT_UUID,
        );
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $orderIntakeRequestTransfer = $this->createResolver($this->createStoredCustomer(), $companyUserTransfers)
            ->resolveCustomer($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);

        // Assert
        $validationIssueTransfer = $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0);

        $this->assertCount(1, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertSame('companyBusinessUnitUuid', $validationIssueTransfer->getField());
        $this->assertSame(static::BUSINESS_UNIT_UUID, $validationIssueTransfer->getParameters()['%companyBusinessUnitUuid%']);
        $this->assertNull($orderIntakeRequestTransfer->getCustomerOrFail()->getCompanyUserTransfer());
    }

    /**
     * @return array<string, array<int, array<int, \Generated\Shared\Transfer\CompanyUserTransfer>>>
     */
    public function provideCompanyUsersOutsideTheNamedBusinessUnit(): array
    {
        return [
            'customer has no company users at all' => [[]],

            'customer belongs only to another business unit' => [[
                $this->createCompanyUser(static::OTHER_BUSINESS_UNIT_UUID, static::COMPANY_USER_UUID),
            ]],

            // A company user whose business unit the hydration stack did not attach must not match the
            // named unit by virtue of both being absent.
            'company user without a business unit' => [[
                (new CompanyUserTransfer())->setUuid(static::COMPANY_USER_UUID),
            ]],
        ];
    }

    /**
     * `CheckCompanyUserUniquenessCompanyUserSavePreCheckPlugin` makes this unreachable through the
     * save path, but it is a plugin and not a database constraint, so imported or legacy rows can
     * still violate it. Choosing one at random would make acceptance nondeterministic: the company
     * user decides whose `PlaceOrderPermissionPlugin` grant the checkout pre-conditions check.
     */
    public function testResolveCustomerRejectsABusinessUnitHoldingSeveralOfTheCustomersCompanyUsers(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = $this->createRequest(
            static::EXISTING_CUSTOMER_REFERENCE,
            static::BUSINESS_UNIT_UUID,
        );
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $orderIntakeRequestTransfer = $this->createResolver($this->createStoredCustomer(), [
            $this->createCompanyUser(static::BUSINESS_UNIT_UUID, static::COMPANY_USER_UUID),
            $this->createCompanyUser(static::BUSINESS_UNIT_UUID, 'a-second-membership'),
        ])->resolveCustomer($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);

        // Assert
        $validationIssueTransfer = $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0);

        $this->assertCount(1, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertSame('companyBusinessUnitUuid', $validationIssueTransfer->getField());
        $this->assertNull($orderIntakeRequestTransfer->getCustomerOrFail()->getCompanyUserTransfer());
    }

    /**
     * The overwhelmingly common payload names no business unit at all, and must not pay for a lookup.
     */
    public function testResolveCustomerDoesNotLookUpCompanyUsersWhenNoBusinessUnitIsNamed(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = $this->createRequest(static::EXISTING_CUSTOMER_REFERENCE);

        $customerFacadeMock = $this->createMock(CustomerFacadeInterface::class);
        $customerFacadeMock->method('getCustomerByCriteria')->willReturn(
            (new CustomerResponseTransfer())->setHasCustomer(true)->setCustomerTransfer($this->createStoredCustomer()),
        );

        $companyUserFacadeMock = $this->createMock(CompanyUserFacadeInterface::class);
        $companyUserFacadeMock->expects($this->never())->method('getActiveCompanyUsersByCustomerReference');

        // Act
        (new OrderIntakeCustomerResolver($customerFacadeMock, $companyUserFacadeMock))
            ->resolveCustomer($orderIntakeRequestTransfer, new OrderIntakeResponseTransfer());

        // Assert
        $this->assertSame(static::ID_CUSTOMER, $orderIntakeRequestTransfer->getCustomerOrFail()->getIdCustomer());
    }

    protected function createRequest(
        string $customerReference,
        ?string $companyBusinessUnitUuid = null,
    ): OrderIntakeRequestTransfer {
        return (new OrderIntakeRequestTransfer())
            ->setCustomer((new CustomerTransfer())->setCustomerReference($customerReference))
            ->setCompanyBusinessUnitUuid($companyBusinessUnitUuid);
    }

    protected function createStoredCustomer(): CustomerTransfer
    {
        return (new CustomerTransfer())
            ->setIdCustomer(static::ID_CUSTOMER)
            ->setCustomerReference(static::EXISTING_CUSTOMER_REFERENCE)
            ->setEmail(static::EXISTING_CUSTOMER_EMAIL);
    }

    protected function createCompanyUser(string $companyBusinessUnitUuid, string $uuid): CompanyUserTransfer
    {
        return (new CompanyUserTransfer())
            ->setUuid($uuid)
            ->setFkCustomer(static::ID_CUSTOMER)
            ->setCompanyBusinessUnit(
                (new CompanyBusinessUnitTransfer())->setUuid($companyBusinessUnitUuid),
            );
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\CompanyUserTransfer> $companyUserTransfers
     */
    protected function createResolver(
        ?CustomerTransfer $storedCustomerTransfer,
        array $companyUserTransfers = [],
    ): OrderIntakeCustomerResolver {
        $customerFacadeMock = $this->createMock(CustomerFacadeInterface::class);
        $customerFacadeMock->method('getCustomerByCriteria')->willReturn(
            (new CustomerResponseTransfer())
                ->setHasCustomer($storedCustomerTransfer !== null)
                ->setCustomerTransfer($storedCustomerTransfer),
        );

        $companyUserFacadeMock = $this->createMock(CompanyUserFacadeInterface::class);
        $companyUserFacadeMock->method('getActiveCompanyUsersByCustomerReference')->willReturn(
            (new CompanyUserCollectionTransfer())->setCompanyUsers(new ArrayObject($companyUserTransfers)),
        );

        return new OrderIntakeCustomerResolver($customerFacadeMock, $companyUserFacadeMock);
    }
}
