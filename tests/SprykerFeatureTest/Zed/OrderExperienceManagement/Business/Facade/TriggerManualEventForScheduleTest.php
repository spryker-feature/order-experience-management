<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Facade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\PermissionCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleEventRequestTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Spryker\Zed\CompanyMailConnector\Business\CompanyMailConnectorBusinessFactory;
use Spryker\Zed\CompanyMailConnector\CompanyMailConnectorDependencyProvider;
use Spryker\Zed\CompanyMailConnector\Dependency\Facade\CompanyMailConnectorToMailFacadeInterface;
use Spryker\Zed\CompanyRole\Communication\Plugin\PermissionStoragePlugin;
use Spryker\Zed\CompanySalesConnector\Communication\Plugin\Permission\SeeCompanyOrdersPermissionPlugin;
use Spryker\Zed\StateMachine\Business\StateMachineFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\ScheduleEventTriggerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementDependencyProvider;
use SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Business
 * @group Facade
 * @group TriggerManualEventForScheduleTest
 * Add your own group annotations below this line
 */
class TriggerManualEventForScheduleTest extends Unit
{
    protected const string FACTORY_METHOD_SCHEDULE_EVENT_TRIGGER = 'createScheduleEventTrigger';

    protected const string TEST_EVENT = 'test-event';

    protected OrderExperienceManagementBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->ensureRecurringScheduleTablesAreEmpty();
    }

    public function testReturnsTrueWhenEventIsSuccessfullyDispatched(): void
    {
        // Arrange
        $this->mockScheduleEventTrigger(true);

        $customerTransfer = $this->tester->haveCustomer();
        $idCustomer = (int)$customerTransfer->getIdCustomer();

        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($recurringScheduleTransfer->getUuidOrFail())
            ->setEvent(static::TEST_EVENT)
            ->setIdCustomer($idCustomer);

        // Act
        $responseTransfer = $this->tester->getFacade()->triggerManualEventForSchedule($requestTransfer);

        // Assert
        $this->assertTrue($responseTransfer->getIsSuccessful());
    }

    public function testReturnsTrueWhenCompanyUserWithSeeCompanyOrdersPermissionTriggersColleagueSchedule(): void
    {
        // Arrange
        $this->tester->setDependency(
            CompanyMailConnectorDependencyProvider::FACADE_MAIL,
            $this->createMock(CompanyMailConnectorToMailFacadeInterface::class),
            CompanyMailConnectorBusinessFactory::class,
        );
        $this->tester->preparePermissionStorageDependency(new PermissionStoragePlugin());

        $stateMachineFacadeMock = $this->createMock(StateMachineFacadeInterface::class);
        $stateMachineFacadeMock->method('triggerEvent')->willReturn(1);
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_STATE_MACHINE, $stateMachineFacadeMock);

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

        // Schedule is owned by a colleague in the same company, not by the acting company user.
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule(
            (int)$colleagueCompanyUserTransfer->getCustomer()->getIdCustomer(),
            [RecurringScheduleTransfer::ID_COMPANY_USER => $colleagueCompanyUserTransfer->getIdCompanyUser()],
        );

        $customerTransfer = $companyUserTransfer->getCustomerOrFail()
            ->setCompanyUserTransfer($companyUserTransfer);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($recurringScheduleTransfer->getUuidOrFail())
            ->setEvent(static::TEST_EVENT)
            ->setIdCustomer($customerTransfer->getIdCustomerOrFail())
            ->setCustomer($customerTransfer);

        // Act
        $responseTransfer = $this->tester->getFacade()->triggerManualEventForSchedule($requestTransfer);

        // Assert
        $this->assertTrue($responseTransfer->getIsSuccessful());
    }

    public function testReturnsFalseWhenScheduleNotFound(): void
    {
        // Arrange
        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid('00000000-0000-0000-0000-000000000000')
            ->setEvent(static::TEST_EVENT)
            ->setIdCustomer(1);

        // Act
        $responseTransfer = $this->tester->getFacade()->triggerManualEventForSchedule($requestTransfer);

        // Assert
        $this->assertFalse($responseTransfer->getIsSuccessful());
    }

    public function testReturnsFalseWhenCustomerDoesNotOwnSchedule(): void
    {
        // Arrange
        $ownerCustomerTransfer = $this->tester->haveCustomer();
        $otherCustomerTransfer = $this->tester->haveCustomer();

        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule((int)$ownerCustomerTransfer->getIdCustomer());

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($recurringScheduleTransfer->getUuidOrFail())
            ->setEvent(static::TEST_EVENT)
            ->setIdCustomer((int)$otherCustomerTransfer->getIdCustomer());

        // Act
        $responseTransfer = $this->tester->getFacade()->triggerManualEventForSchedule($requestTransfer);

        // Assert
        $this->assertFalse($responseTransfer->getIsSuccessful());
    }

    public function testReturnsFalseWhenStateMachineEventIsNotApplied(): void
    {
        // Arrange
        $this->mockScheduleEventTrigger(false);

        $customerTransfer = $this->tester->haveCustomer();
        $idCustomer = (int)$customerTransfer->getIdCustomer();

        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($recurringScheduleTransfer->getUuidOrFail())
            ->setEvent(static::TEST_EVENT)
            ->setIdCustomer($idCustomer);

        // Act
        $responseTransfer = $this->tester->getFacade()->triggerManualEventForSchedule($requestTransfer);

        // Assert
        $this->assertFalse($responseTransfer->getIsSuccessful());
    }

    protected function mockScheduleEventTrigger(bool $isApplied): void
    {
        $scheduleEventTriggerMock = $this->createMock(ScheduleEventTriggerInterface::class);
        $scheduleEventTriggerMock->method('triggerEvent')->willReturn($isApplied);

        $this->tester->mockFactoryMethod(static::FACTORY_METHOD_SCHEDULE_EVENT_TRIGGER, $scheduleEventTriggerMock);
    }
}
