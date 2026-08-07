<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Facade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\PermissionCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleConditionsTransfer;
use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Spryker\Zed\CompanyMailConnector\Business\CompanyMailConnectorBusinessFactory;
use Spryker\Zed\CompanyMailConnector\CompanyMailConnectorDependencyProvider;
use Spryker\Zed\CompanyMailConnector\Dependency\Facade\CompanyMailConnectorToMailFacadeInterface;
use Spryker\Zed\CompanyRole\Communication\Plugin\PermissionStoragePlugin;
use SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Facade
 * @group GetRecurringScheduleCollectionCompanyDataTest
 * Add your own group annotations below this line
 */
class GetRecurringScheduleCollectionCompanyDataTest extends Unit
{
    protected OrderExperienceManagementBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->setDependency(
            CompanyMailConnectorDependencyProvider::FACADE_MAIL,
            $this->createMock(CompanyMailConnectorToMailFacadeInterface::class),
            CompanyMailConnectorBusinessFactory::class,
        );

        $this->tester->preparePermissionStorageDependency(new PermissionStoragePlugin());
        $this->tester->ensureRecurringScheduleTablesAreEmpty();
    }

    public function testLoadsCompanyAndBusinessUnitNamesWhenIsWithCompany(): void
    {
        // Arrange
        $companyUserTransfer = $this->tester->haveCompanyUserWithPermissions(
            $this->tester->haveCompany(),
            new PermissionCollectionTransfer(),
        );

        $this->tester->haveRecurringSchedule(
            (int)$companyUserTransfer->getCustomerOrFail()->getIdCustomer(),
            [RecurringScheduleTransfer::ID_COMPANY_USER => $companyUserTransfer->getIdCompanyUser()],
        );

        $criteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addCompanyId($companyUserTransfer->getFkCompanyOrFail())
                    ->setIsWithCompany(true),
            );

        // Act
        $collectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($criteriaTransfer);

        // Assert
        $this->assertCount(1, $collectionTransfer->getRecurringSchedules());
        $scheduleTransfer = $collectionTransfer->getRecurringSchedules()->offsetGet(0);
        $this->assertSame(
            $companyUserTransfer->getCompanyOrFail()->getName(),
            $scheduleTransfer->getCompanyName(),
        );
        $this->assertSame(
            $companyUserTransfer->getCompanyBusinessUnitOrFail()->getName(),
            $scheduleTransfer->getBusinessUnitName(),
        );
    }

    public function testExcludesSchedulesWithoutCompanyUserWhenIsWithCompany(): void
    {
        // Arrange
        $companyUserTransfer = $this->tester->haveCompanyUserWithPermissions(
            $this->tester->haveCompany(),
            new PermissionCollectionTransfer(),
        );

        $this->tester->haveRecurringSchedule(
            (int)$companyUserTransfer->getCustomerOrFail()->getIdCustomer(),
            [RecurringScheduleTransfer::ID_COMPANY_USER => $companyUserTransfer->getIdCompanyUser()],
        );

        $this->tester->haveRecurringSchedule((int)$this->tester->haveCustomer()->getIdCustomer());

        $criteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->setIsWithCompany(true),
            );

        // Act
        $collectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($criteriaTransfer);

        // Assert
        $this->assertCount(1, $collectionTransfer->getRecurringSchedules());
        $this->assertNotNull($collectionTransfer->getRecurringSchedules()->offsetGet(0)->getCompanyName());
    }

    /**
     * @see \SprykerFeature\Zed\OrderExperienceManagement\Communication\Table\RecurringScheduleTable::applyFilters()
     */
    public function testFiltersByEstimatedTotalRangeWhenIsWithCompany(): void
    {
        // Arrange
        $companyUserTransfer = $this->tester->haveCompanyUserWithPermissions(
            $this->tester->haveCompany(),
            new PermissionCollectionTransfer(),
        );
        $idCustomer = (int)$companyUserTransfer->getCustomerOrFail()->getIdCustomer();

        $cheapScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::NAME => 'cheap',
            RecurringScheduleTransfer::PRICE_MODE => 'GROSS_MODE',
            RecurringScheduleTransfer::ID_COMPANY_USER => $companyUserTransfer->getIdCompanyUser(),
        ]);
        $this->tester->haveRecurringScheduleItem($cheapScheduleTransfer->getIdRecurringScheduleOrFail(), [
            RecurringScheduleItemTransfer::QUANTITY => 1,
            RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 300,
        ]);

        $expensiveScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::NAME => 'expensive',
            RecurringScheduleTransfer::PRICE_MODE => 'GROSS_MODE',
            RecurringScheduleTransfer::ID_COMPANY_USER => $companyUserTransfer->getIdCompanyUser(),
        ]);
        $this->tester->haveRecurringScheduleItem($expensiveScheduleTransfer->getIdRecurringScheduleOrFail(), [
            RecurringScheduleItemTransfer::QUANTITY => 2,
            RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500,
        ]);

        $criteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addCustomerId($idCustomer)
                    ->setIsWithCompany(true)
                    ->setEstimatedTotalMin(500)
                    ->setEstimatedTotalMax(2000),
            );

        // Act
        $collectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($criteriaTransfer);

        // Assert
        $this->assertCount(1, $collectionTransfer->getRecurringSchedules());
        $scheduleTransfer = $collectionTransfer->getRecurringSchedules()->offsetGet(0);
        $this->assertSame('expensive', $scheduleTransfer->getName());
        $this->assertSame(
            $companyUserTransfer->getCompanyOrFail()->getName(),
            $scheduleTransfer->getCompanyName(),
        );
        $this->assertSame(
            $companyUserTransfer->getCompanyBusinessUnitOrFail()->getName(),
            $scheduleTransfer->getBusinessUnitName(),
        );
    }

    public function testFiltersByCompanyId(): void
    {
        // Arrange
        $companyUserTransfer = $this->tester->haveCompanyUserWithPermissions(
            $this->tester->haveCompany(),
            new PermissionCollectionTransfer(),
        );
        $otherCompanyUserTransfer = $this->tester->haveCompanyUserWithPermissions(
            $this->tester->haveCompany(),
            new PermissionCollectionTransfer(),
        );

        $this->tester->haveRecurringSchedule(
            (int)$companyUserTransfer->getCustomerOrFail()->getIdCustomer(),
            [RecurringScheduleTransfer::ID_COMPANY_USER => $companyUserTransfer->getIdCompanyUser()],
        );
        $this->tester->haveRecurringSchedule(
            (int)$otherCompanyUserTransfer->getCustomerOrFail()->getIdCustomer(),
            [RecurringScheduleTransfer::ID_COMPANY_USER => $otherCompanyUserTransfer->getIdCompanyUser()],
        );

        $criteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addCompanyId($companyUserTransfer->getFkCompanyOrFail()),
            );

        // Act
        $collectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($criteriaTransfer);

        // Assert
        $this->assertCount(1, $collectionTransfer->getRecurringSchedules());
        $this->assertSame(
            $companyUserTransfer->getIdCompanyUser(),
            $collectionTransfer->getRecurringSchedules()->offsetGet(0)->getIdCompanyUser(),
        );
    }

    public function testFiltersByCompanyBusinessUnitId(): void
    {
        // Arrange
        $companyUserTransfer = $this->tester->haveCompanyUserWithPermissions(
            $this->tester->haveCompany(),
            new PermissionCollectionTransfer(),
        );
        $otherCompanyUserTransfer = $this->tester->haveCompanyUserWithPermissions(
            $this->tester->haveCompany(),
            new PermissionCollectionTransfer(),
        );

        $this->tester->haveRecurringSchedule(
            (int)$companyUserTransfer->getCustomerOrFail()->getIdCustomer(),
            [RecurringScheduleTransfer::ID_COMPANY_USER => $companyUserTransfer->getIdCompanyUser()],
        );
        $this->tester->haveRecurringSchedule(
            (int)$otherCompanyUserTransfer->getCustomerOrFail()->getIdCustomer(),
            [RecurringScheduleTransfer::ID_COMPANY_USER => $otherCompanyUserTransfer->getIdCompanyUser()],
        );

        $criteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addCompanyBusinessUnitId($companyUserTransfer->getFkCompanyBusinessUnitOrFail()),
            );

        // Act
        $collectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($criteriaTransfer);

        // Assert
        $this->assertCount(1, $collectionTransfer->getRecurringSchedules());
        $this->assertSame(
            $companyUserTransfer->getIdCompanyUser(),
            $collectionTransfer->getRecurringSchedules()->offsetGet(0)->getIdCompanyUser(),
        );
    }
}
