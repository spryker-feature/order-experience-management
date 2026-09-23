<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Intake\Resolver;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CompanyBusinessUnitTransfer;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\MerchantRelationshipTransfer;
use Generated\Shared\Transfer\OrderIntakeRequestTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Spryker\Zed\CompanyBusinessUnit\Communication\Plugin\CompanyUser\CompanyBusinessUnitHydratePlugin;
use Spryker\Zed\CompanyUser\Communication\Plugin\Customer\CustomerTransferCompanyUserExpanderPlugin;
use Spryker\Zed\CompanyUser\CompanyUserDependencyProvider;
use Spryker\Zed\Customer\CustomerDependencyProvider;
use Spryker\Zed\MerchantRelationship\Communication\Plugin\CompanyUser\MerchantRelationshipHydratePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Resolver\OrderIntakeCustomerResolver;
use SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester;

/**
 * Exercises the resolver against the REAL Customer facade and plugin stacks rather than mocks.
 *
 * The unit test proves the resolver's own branching. What it cannot prove is the assumption the
 * whole contract-pricing fix rests on: that reading a customer through `getCustomerByCriteria` with
 * expanders actually produces `companyUser.companyBusinessUnit.merchantRelationships`, because that
 * hydration happens across four modules and two plugin stacks (`CustomerTransferExpanderPluginInterface`
 * in Customer, `CompanyUserHydrationPluginInterface` in CompanyUser). A mock of the facade would
 * assert only that this test agrees with itself.
 *
 * Two cases: a customer holding exactly ONE active company user, which the expander stack settles
 * unaided, and a customer in TWO business units, which it refuses to settle and where
 * `companyBusinessUnitUuid` decides. The second is the one a mock cannot prove — it asserts that
 * filtering `getActiveCompanyUsersByCustomerReference()` on the business unit uuid yields a company
 * user whose relationships are actually hydrated, which is the only reason to select a company user
 * at all.
 *
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Business
 * @group Intake
 * @group Resolver
 * @group OrderIntakeCustomerResolverContractTest
 * Add your own group annotations below this line
 */
class OrderIntakeCustomerResolverContractTest extends Unit
{
    protected OrderExperienceManagementBusinessTester $tester;

    /**
     * The test container resolves core DependencyProviders, whose plugin stacks are empty by default
     * — project wiring lives in `src/Pyz` and is not loaded here. These three are registered to match
     * `Pyz\Zed\Customer\CustomerDependencyProvider::getCustomerTransferExpanderPlugins()` and
     * `Pyz\Zed\CompanyUser\CompanyUserDependencyProvider::getCompanyUserHydrationPlugins()`,
     * including their order: the business unit must be attached before the merchant relationships
     * that hang off it. This test therefore proves the chain works, not that Pyz still wires it —
     * removing a plugin from Pyz would break production and leave this test green.
     */
    protected function _setUp(): void
    {
        parent::_setUp();

        $this->tester->setDependency(CustomerDependencyProvider::PLUGINS_CUSTOMER_TRANSFER_EXPANDER, [
            new CustomerTransferCompanyUserExpanderPlugin(),
        ]);
        $this->tester->setDependency(CompanyUserDependencyProvider::PLUGINS_COMPANY_USER_HYDRATE, [
            new CompanyBusinessUnitHydratePlugin(),
            new MerchantRelationshipHydratePlugin(),
        ]);
    }

    /**
     * The end of the chain `MerchantRelationshipPriceQueryExpander::findMerchantRelationshipIds()`
     * walks. Anything missing along it and the buyer is priced at the operator's list instead of
     * their contract — silently, because a list price is a perfectly valid price.
     */
    public function testResolveCustomerProducesTheMerchantRelationshipsThePriceDimensionNeeds(): void
    {
        // Arrange
        $merchantRelationshipTransfer = $this->haveMerchantRelationshipForNewBusinessUnit();
        $customerTransfer = $this->haveCompanyUserCustomer($merchantRelationshipTransfer);

        $orderIntakeRequestTransfer = (new OrderIntakeRequestTransfer())->setCustomer(
            (new CustomerTransfer())->setCustomerReference($customerTransfer->getCustomerReference()),
        );
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $orderIntakeRequestTransfer = $this->createResolver()->resolveCustomer(
            $orderIntakeRequestTransfer,
            $orderIntakeResponseTransfer,
        );

        // Assert
        $companyBusinessUnitTransfer = $orderIntakeRequestTransfer
            ->getCustomerOrFail()
            ->getCompanyUserTransfer()
            ?->getCompanyBusinessUnit();

        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertNotNull($companyBusinessUnitTransfer, 'The expander stack did not attach the business unit.');
        $this->assertSame(
            [$merchantRelationshipTransfer->getIdMerchantRelationship()],
            $this->extractMerchantRelationshipIds($companyBusinessUnitTransfer),
        );
    }

    /**
     * The case the expander stack cannot settle: two business units, two contracts, and nothing on
     * the customer to say which the order is for. Asserting on the merchant relationships rather
     * than just the business unit uuid is the point — a company user selected but left unhydrated
     * would pass a uuid check and still price the order at the operator's list.
     */
    public function testResolveCustomerSelectsTheCompanyUserInTheNamedBusinessUnit(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveCustomer();

        $unwantedMerchantRelationshipTransfer = $this->haveMerchantRelationshipForNewBusinessUnit();
        $wantedMerchantRelationshipTransfer = $this->haveMerchantRelationshipForNewBusinessUnit();

        $this->haveCompanyUserInBusinessUnit($customerTransfer, $this->extractBusinessUnit($unwantedMerchantRelationshipTransfer));
        $wantedCompanyBusinessUnitTransfer = $this->extractBusinessUnit($wantedMerchantRelationshipTransfer);
        $this->haveCompanyUserInBusinessUnit($customerTransfer, $wantedCompanyBusinessUnitTransfer);

        $orderIntakeRequestTransfer = (new OrderIntakeRequestTransfer())
            ->setCustomer((new CustomerTransfer())->setCustomerReference($customerTransfer->getCustomerReference()))
            ->setCompanyBusinessUnitUuid($this->findPersistedBusinessUnitUuid($wantedCompanyBusinessUnitTransfer));
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $orderIntakeRequestTransfer = $this->createResolver()->resolveCustomer(
            $orderIntakeRequestTransfer,
            $orderIntakeResponseTransfer,
        );

        // Assert
        $companyBusinessUnitTransfer = $orderIntakeRequestTransfer
            ->getCustomerOrFail()
            ->getCompanyUserTransfer()
            ?->getCompanyBusinessUnit();

        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertNotNull($companyBusinessUnitTransfer, 'No company user was selected for the named business unit.');
        $this->assertSame(
            $wantedCompanyBusinessUnitTransfer->getIdCompanyBusinessUnit(),
            $companyBusinessUnitTransfer->getIdCompanyBusinessUnit(),
        );
        $this->assertSame(
            [$wantedMerchantRelationshipTransfer->getIdMerchantRelationship()],
            $this->extractMerchantRelationshipIds($companyBusinessUnitTransfer),
        );
    }

    /**
     * A business unit the customer holds no company user in is rejected outright. This is the
     * ownership guard against pricing an order on a contract belonging to someone else, so it is
     * worth proving against the real read rather than a mocked collection.
     */
    public function testResolveCustomerRejectsABusinessUnitTheCustomerDoesNotBelongTo(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveCustomer();
        $foreignCompanyBusinessUnitTransfer = $this->extractBusinessUnit($this->haveMerchantRelationshipForNewBusinessUnit());

        $orderIntakeRequestTransfer = (new OrderIntakeRequestTransfer())
            ->setCustomer((new CustomerTransfer())->setCustomerReference($customerTransfer->getCustomerReference()))
            ->setCompanyBusinessUnitUuid($this->findPersistedBusinessUnitUuid($foreignCompanyBusinessUnitTransfer));
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $orderIntakeRequestTransfer = $this->createResolver()->resolveCustomer(
            $orderIntakeRequestTransfer,
            $orderIntakeResponseTransfer,
        );

        // Assert
        $this->assertCount(1, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertSame(
            'companyBusinessUnitUuid',
            $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0)->getField(),
        );
        $this->assertNull($orderIntakeRequestTransfer->getCustomerOrFail()->getCompanyUserTransfer());
    }

    protected function createResolver(): OrderIntakeCustomerResolver
    {
        return new OrderIntakeCustomerResolver(
            $this->tester->getLocator()->customer()->facade(),
            $this->tester->getLocator()->companyUser()->facade(),
        );
    }

    protected function haveMerchantRelationshipForNewBusinessUnit(): MerchantRelationshipTransfer
    {
        $companyBusinessUnitTransfer = $this->tester->haveCompanyBusinessUnit([
            CompanyBusinessUnitTransfer::FK_COMPANY => $this->tester->haveCompany([
                'status' => 'approved',
                'isActive' => true,
            ])->getIdCompany(),
        ]);

        return $this->tester->haveMerchantRelationship([
            MerchantRelationshipTransfer::FK_MERCHANT => $this->tester->haveMerchant()->getIdMerchant(),
            MerchantRelationshipTransfer::FK_COMPANY_BUSINESS_UNIT => $companyBusinessUnitTransfer
                ->getIdCompanyBusinessUnit(),
            MerchantRelationshipTransfer::ASSIGNEE_COMPANY_BUSINESS_UNITS => [
                'companyBusinessUnits' => [$companyBusinessUnitTransfer->toArray()],
            ],
        ]);
    }

    protected function haveCompanyUserCustomer(MerchantRelationshipTransfer $merchantRelationshipTransfer): CustomerTransfer
    {
        $customerTransfer = $this->tester->haveCustomer();

        $this->haveCompanyUserInBusinessUnit($customerTransfer, $this->extractBusinessUnit($merchantRelationshipTransfer));

        return $customerTransfer;
    }

    protected function haveCompanyUserInBusinessUnit(
        CustomerTransfer $customerTransfer,
        CompanyBusinessUnitTransfer $companyBusinessUnitTransfer,
    ): void {
        $this->tester->haveCompanyUser([
            CompanyUserTransfer::CUSTOMER => $customerTransfer,
            CompanyUserTransfer::FK_CUSTOMER => $customerTransfer->getIdCustomer(),
            CompanyUserTransfer::FK_COMPANY => $companyBusinessUnitTransfer->getFkCompany(),
            CompanyUserTransfer::FK_COMPANY_BUSINESS_UNIT => $companyBusinessUnitTransfer->getIdCompanyBusinessUnit(),
            CompanyUserTransfer::IS_ACTIVE => true,
        ]);
    }

    protected function extractBusinessUnit(MerchantRelationshipTransfer $merchantRelationshipTransfer): CompanyBusinessUnitTransfer
    {
        return $merchantRelationshipTransfer->getAssigneeCompanyBusinessUnitsOrFail()
            ->getCompanyBusinessUnits()
            ->offsetGet(0);
    }

    /**
     * The uuid is generated by the table's `uuid` behavior on insert, so it is read back rather than
     * taken from the transfer the builder handed to `create()`.
     */
    protected function findPersistedBusinessUnitUuid(CompanyBusinessUnitTransfer $companyBusinessUnitTransfer): string
    {
        $persistedCompanyBusinessUnitTransfer = $this->tester->getLocator()
            ->companyBusinessUnit()
            ->facade()
            ->findCompanyBusinessUnitById((int)$companyBusinessUnitTransfer->getIdCompanyBusinessUnit());

        $this->assertNotNull($persistedCompanyBusinessUnitTransfer, 'The business unit was not persisted.');

        return (string)$persistedCompanyBusinessUnitTransfer->getUuid();
    }

    /**
     * @return array<int, int|null>
     */
    protected function extractMerchantRelationshipIds(CompanyBusinessUnitTransfer $companyBusinessUnitTransfer): array
    {
        $merchantRelationshipIds = [];

        foreach ($companyBusinessUnitTransfer->getMerchantRelationships() as $merchantRelationshipTransfer) {
            $merchantRelationshipIds[] = $merchantRelationshipTransfer->getIdMerchantRelationship();
        }

        return $merchantRelationshipIds;
    }
}
