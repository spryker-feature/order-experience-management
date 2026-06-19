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
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleQuery;
use Spryker\Zed\CompanyMailConnector\Business\CompanyMailConnectorBusinessFactory;
use Spryker\Zed\CompanyMailConnector\CompanyMailConnectorDependencyProvider;
use Spryker\Zed\CompanyMailConnector\Dependency\Facade\CompanyMailConnectorToMailFacadeInterface;
use Spryker\Zed\CompanyRole\Communication\Plugin\PermissionStoragePlugin;
use Spryker\Zed\CompanySalesConnector\Communication\Plugin\Permission\SeeCompanyOrdersPermissionPlugin;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\ScheduleEventTriggerInterface;
use SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Business
 * @group Facade
 * @group ResumeScheduleWithDateTest
 * Add your own group annotations below this line
 */
class ResumeScheduleWithDateTest extends Unit
{
    protected const string FACTORY_METHOD_SCHEDULE_EVENT_TRIGGER = 'createScheduleEventTrigger';

    protected const string NEXT_EXECUTION_DATE = '2026-09-15';

    protected const string DATE_FORMAT = 'Y-m-d';

    protected OrderExperienceManagementBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->ensureRecurringScheduleTablesAreEmpty();
    }

    public function testResumesPausedScheduleUpdatesTriggerDateAndFiresEvent(): void
    {
        // Arrange
        $this->mockScheduleEventTrigger(true);

        $customerTransfer = $this->tester->haveCustomer();
        $idCustomer = (int)$customerTransfer->getIdCustomer();

        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_PAUSED,
        ]);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($recurringScheduleTransfer->getUuidOrFail())
            ->setIdCustomer($idCustomer)
            ->setNextExecutionDate(static::NEXT_EXECUTION_DATE);

        // Act
        $responseTransfer = $this->tester->getFacade()->resumeScheduleWithDate($requestTransfer);

        // Assert
        $this->assertTrue($responseTransfer->getIsSuccessful());

        $scheduleEntity = SpyRecurringScheduleQuery::create()
            ->findOneByIdRecurringSchedule($recurringScheduleTransfer->getIdRecurringScheduleOrFail());

        $this->assertSame(
            static::NEXT_EXECUTION_DATE,
            $scheduleEntity->getNextTriggerDate()->format(static::DATE_FORMAT),
        );
    }

    public function testResumesColleagueScheduleWhenCompanyUserHasSeeCompanyOrdersPermission(): void
    {
        // Arrange
        $this->tester->setDependency(
            CompanyMailConnectorDependencyProvider::FACADE_MAIL,
            $this->createMock(CompanyMailConnectorToMailFacadeInterface::class),
            CompanyMailConnectorBusinessFactory::class,
        );
        $this->tester->preparePermissionStorageDependency(new PermissionStoragePlugin());
        $this->mockScheduleEventTrigger(true);

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

        // Paused schedule owned by a colleague in the same company, not by the acting company user.
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule(
            (int)$colleagueCompanyUserTransfer->getCustomer()->getIdCustomer(),
            [
                RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_PAUSED,
                RecurringScheduleTransfer::ID_COMPANY_USER => $colleagueCompanyUserTransfer->getIdCompanyUser(),
            ],
        );

        $customerTransfer = $companyUserTransfer->getCustomerOrFail()
            ->setCompanyUserTransfer($companyUserTransfer);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($recurringScheduleTransfer->getUuidOrFail())
            ->setIdCustomer($customerTransfer->getIdCustomerOrFail())
            ->setCustomer($customerTransfer)
            ->setNextExecutionDate(static::NEXT_EXECUTION_DATE);

        // Act
        $responseTransfer = $this->tester->getFacade()->resumeScheduleWithDate($requestTransfer);

        // Assert
        $this->assertTrue($responseTransfer->getIsSuccessful());

        $scheduleEntity = SpyRecurringScheduleQuery::create()
            ->findOneByIdRecurringSchedule($recurringScheduleTransfer->getIdRecurringScheduleOrFail());

        $this->assertSame(
            static::NEXT_EXECUTION_DATE,
            $scheduleEntity->getNextTriggerDate()->format(static::DATE_FORMAT),
        );
    }

    public function testReturnsFalseWhenScheduleNotFound(): void
    {
        // Arrange
        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid('00000000-0000-0000-0000-000000000000')
            ->setIdCustomer(1)
            ->setNextExecutionDate(static::NEXT_EXECUTION_DATE);

        // Act
        $responseTransfer = $this->tester->getFacade()->resumeScheduleWithDate($requestTransfer);

        // Assert
        $this->assertFalse($responseTransfer->getIsSuccessful());
    }

    public function testReturnsFalseWhenCustomerDoesNotOwnSchedule(): void
    {
        // Arrange
        $ownerCustomerTransfer = $this->tester->haveCustomer();
        $otherCustomerTransfer = $this->tester->haveCustomer();

        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule((int)$ownerCustomerTransfer->getIdCustomer(), [
            RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_PAUSED,
        ]);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($recurringScheduleTransfer->getUuidOrFail())
            ->setIdCustomer((int)$otherCustomerTransfer->getIdCustomer())
            ->setNextExecutionDate(static::NEXT_EXECUTION_DATE);

        // Act
        $responseTransfer = $this->tester->getFacade()->resumeScheduleWithDate($requestTransfer);

        // Assert
        $this->assertFalse($responseTransfer->getIsSuccessful());
    }

    public function testReturnsFalseWhenScheduleIsNotPaused(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveCustomer();
        $idCustomer = (int)$customerTransfer->getIdCustomer();

        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_ACTIVE,
        ]);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($recurringScheduleTransfer->getUuidOrFail())
            ->setIdCustomer($idCustomer)
            ->setNextExecutionDate(static::NEXT_EXECUTION_DATE);

        // Act
        $responseTransfer = $this->tester->getFacade()->resumeScheduleWithDate($requestTransfer);

        // Assert
        $this->assertFalse($responseTransfer->getIsSuccessful());
    }

    public function testReturnsFalseWhenStateMachineEventIsNotApplied(): void
    {
        // Arrange
        $this->mockScheduleEventTrigger(false);

        $customerTransfer = $this->tester->haveCustomer();
        $idCustomer = (int)$customerTransfer->getIdCustomer();

        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_PAUSED,
        ]);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($recurringScheduleTransfer->getUuidOrFail())
            ->setIdCustomer($idCustomer)
            ->setNextExecutionDate(static::NEXT_EXECUTION_DATE);

        // Act
        $responseTransfer = $this->tester->getFacade()->resumeScheduleWithDate($requestTransfer);

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
