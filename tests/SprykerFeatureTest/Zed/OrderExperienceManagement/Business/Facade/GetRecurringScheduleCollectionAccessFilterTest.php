<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Facade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\PermissionCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Spryker\Zed\CompanyBusinessUnitSalesConnector\Communication\Plugin\Permission\SeeBusinessUnitOrdersPermissionPlugin;
use Spryker\Zed\CompanyMailConnector\Business\CompanyMailConnectorBusinessFactory;
use Spryker\Zed\CompanyMailConnector\CompanyMailConnectorDependencyProvider;
use Spryker\Zed\CompanyMailConnector\Dependency\Facade\CompanyMailConnectorToMailFacadeInterface;
use Spryker\Zed\CompanyRole\Communication\Plugin\PermissionStoragePlugin;
use Spryker\Zed\CompanySalesConnector\Communication\Plugin\Permission\SeeCompanyOrdersPermissionPlugin;
use SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Business
 * @group Facade
 * @group GetRecurringScheduleCollectionAccessFilterTest
 * Add your own group annotations below this line
 */
class GetRecurringScheduleCollectionAccessFilterTest extends Unit
{
    protected OrderExperienceManagementBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        // CompanyMailConnector and Customer both register 'FACADE_MAIL' — scope this override to the
        // CompanyMailConnector factory so it doesn't bleed into Customer's dependency.
        $this->tester->setDependency(
            CompanyMailConnectorDependencyProvider::FACADE_MAIL,
            $this->createMock(CompanyMailConnectorToMailFacadeInterface::class),
            CompanyMailConnectorBusinessFactory::class,
        );

        $this->tester->preparePermissionStorageDependency(new PermissionStoragePlugin());
        $this->tester->ensureRecurringScheduleTablesAreEmpty();
    }

    public function testAccessFilterScopesSchedulesByCustomerIdWhenNoCompanyUser(): void
    {
        // Arrange
        $ownerCustomer = $this->tester->haveCustomer();
        $otherCustomer = $this->tester->haveCustomer();

        $this->tester->haveRecurringSchedule((int)$ownerCustomer->getIdCustomer());
        $this->tester->haveRecurringSchedule((int)$otherCustomer->getIdCustomer());

        $criteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setCustomer($ownerCustomer);

        // Act
        $collectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($criteriaTransfer);

        $this->assertCount(1, $collectionTransfer->getRecurringSchedules());
        $this->assertSame(
            (int)$ownerCustomer->getIdCustomer(),
            $collectionTransfer->getRecurringSchedules()->offsetGet(0)->getIdCustomer(),
        );
    }

    public function testAccessFilterScopesSchedulesByBusinessUnitWhenPermissionGranted(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $permissionTransfer = $this->tester->havePermission(new SeeBusinessUnitOrdersPermissionPlugin());

        $companyUserTransfer = $this->tester->haveCompanyUserWithPermissions(
            $companyTransfer,
            (new PermissionCollectionTransfer())->addPermission($permissionTransfer),
        );

        $sameBusinessUnitCompanyUserTransfer = $this->tester->haveCompanyUserWithPermissions(
            $companyTransfer,
            new PermissionCollectionTransfer(),
        );

        $idBusinessUnit = $companyUserTransfer->getFkCompanyBusinessUnit();
        $sameBusinessUnitCompanyUserTransfer->setFkCompanyBusinessUnit($idBusinessUnit);

        $otherCompanyUserTransfer = $this->tester->haveCompanyUserWithPermissions(
            $this->tester->haveCompany(),
            new PermissionCollectionTransfer(),
        );

        $idOwnerCustomer = (int)$companyUserTransfer->getCustomer()->getIdCustomer();

        $this->tester->haveRecurringSchedule($idOwnerCustomer, [
            RecurringScheduleTransfer::ID_COMPANY_USER => $companyUserTransfer->getIdCompanyUser(),
        ]);
        $this->tester->haveRecurringSchedule(
            (int)$sameBusinessUnitCompanyUserTransfer->getCustomer()->getIdCustomer(),
            [RecurringScheduleTransfer::ID_COMPANY_USER => $sameBusinessUnitCompanyUserTransfer->getIdCompanyUser()],
        );
        $this->tester->haveRecurringSchedule(
            (int)$otherCompanyUserTransfer->getCustomer()->getIdCustomer(),
            [RecurringScheduleTransfer::ID_COMPANY_USER => $otherCompanyUserTransfer->getIdCompanyUser()],
        );

        $customerTransfer = $companyUserTransfer->getCustomerOrFail()
            ->setCompanyUserTransfer($companyUserTransfer);

        $criteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setCustomer($customerTransfer);

        // Act
        $collectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($criteriaTransfer);

        $this->assertCount(2, $collectionTransfer->getRecurringSchedules());
    }

    public function testAccessFilterScopesSchedulesByCompanyWhenPermissionGranted(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $permissionTransfer = $this->tester->havePermission(new SeeCompanyOrdersPermissionPlugin());

        $companyUserTransfer = $this->tester->haveCompanyUserWithPermissions(
            $companyTransfer,
            (new PermissionCollectionTransfer())->addPermission($permissionTransfer),
        );

        $colleagueCompanyUserTransfer = $this->tester->haveCompanyUserWithPermissions(
            $companyTransfer,
            new PermissionCollectionTransfer(),
        );

        $otherCompanyUserTransfer = $this->tester->haveCompanyUserWithPermissions(
            $this->tester->haveCompany(),
            new PermissionCollectionTransfer(),
        );

        $this->tester->haveRecurringSchedule(
            (int)$companyUserTransfer->getCustomer()->getIdCustomer(),
            [RecurringScheduleTransfer::ID_COMPANY_USER => $companyUserTransfer->getIdCompanyUser()],
        );
        $this->tester->haveRecurringSchedule(
            (int)$colleagueCompanyUserTransfer->getCustomer()->getIdCustomer(),
            [RecurringScheduleTransfer::ID_COMPANY_USER => $colleagueCompanyUserTransfer->getIdCompanyUser()],
        );
        $this->tester->haveRecurringSchedule(
            (int)$otherCompanyUserTransfer->getCustomer()->getIdCustomer(),
            [RecurringScheduleTransfer::ID_COMPANY_USER => $otherCompanyUserTransfer->getIdCompanyUser()],
        );

        $customerTransfer = $companyUserTransfer->getCustomerOrFail()
            ->setCompanyUserTransfer($companyUserTransfer);

        $criteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setCustomer($customerTransfer);

        // Act
        $collectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($criteriaTransfer);

        // Assert
        $this->assertCount(2, $collectionTransfer->getRecurringSchedules());
    }

    public function testAccessFilterIsSkippedWhenNoCriteriaCustomerIsSet(): void
    {
        // Arrange
        $customerA = $this->tester->haveCustomer();
        $customerB = $this->tester->haveCustomer();

        $this->tester->haveRecurringSchedule((int)$customerA->getIdCustomer());
        $this->tester->haveRecurringSchedule((int)$customerB->getIdCustomer());

        $criteriaTransfer = new RecurringScheduleCriteriaTransfer();

        // Act
        $collectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($criteriaTransfer);

        // Assert
        $this->assertGreaterThanOrEqual(2, $collectionTransfer->getRecurringSchedules()->count());
    }
}
