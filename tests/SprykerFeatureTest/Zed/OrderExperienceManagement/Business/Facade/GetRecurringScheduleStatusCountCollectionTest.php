<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Facade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\RecurringScheduleConditionsTransfer;
use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Business
 * @group Facade
 * @group GetRecurringScheduleStatusCountCollectionTest
 * Add your own group annotations below this line
 */
class GetRecurringScheduleStatusCountCollectionTest extends Unit
{
    protected OrderExperienceManagementBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->ensureRecurringScheduleTablesAreEmpty();
    }

    public function testReturnsCountsGroupedByStatus(): void
    {
        // Arrange
        $customer = $this->tester->haveCustomer();
        $idCustomer = (int)$customer->getIdCustomer();

        $this->tester->haveRecurringSchedule($idCustomer, [RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_ACTIVE]);
        $this->tester->haveRecurringSchedule($idCustomer, [RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_ACTIVE]);
        $this->tester->haveRecurringSchedule($idCustomer, [RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_PAUSED]);

        $criteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addCustomerId($idCustomer)
                    ->addStatus(SharedOrderExperienceManagementConfig::STATUS_ACTIVE)
                    ->addStatus(SharedOrderExperienceManagementConfig::STATUS_PAUSED),
            );

        // Act
        $statusCountCollectionTransfer = $this->tester->getFacade()->getRecurringScheduleStatusCountCollection($criteriaTransfer);

        // Assert
        $countByStatus = [];
        foreach ($statusCountCollectionTransfer->getStatusCounts() as $statusCountTransfer) {
            $countByStatus[$statusCountTransfer->getStatusOrFail()] = $statusCountTransfer->getCountOrFail();
        }

        $this->assertSame(2, $countByStatus[SharedOrderExperienceManagementConfig::STATUS_ACTIVE]);
        $this->assertSame(1, $countByStatus[SharedOrderExperienceManagementConfig::STATUS_PAUSED]);
    }

    public function testReturnsEmptyCollectionWhenNoMatchingSchedulesExist(): void
    {
        // Arrange
        $criteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addCustomerId(PHP_INT_MAX)
                    ->addStatus(SharedOrderExperienceManagementConfig::STATUS_ACTIVE),
            );

        // Act
        $statusCountCollectionTransfer = $this->tester->getFacade()->getRecurringScheduleStatusCountCollection($criteriaTransfer);

        // Assert
        $this->assertCount(0, $statusCountCollectionTransfer->getStatusCounts());
    }

    public function testFiltersCountsByCustomerId(): void
    {
        // Arrange
        $customerA = $this->tester->haveCustomer();
        $customerB = $this->tester->haveCustomer();

        $this->tester->haveRecurringSchedule((int)$customerA->getIdCustomer());
        $this->tester->haveRecurringSchedule((int)$customerA->getIdCustomer());
        $this->tester->haveRecurringSchedule((int)$customerB->getIdCustomer());
        $this->tester->haveRecurringSchedule((int)$customerB->getIdCustomer());
        $this->tester->haveRecurringSchedule((int)$customerB->getIdCustomer());

        $criteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addCustomerId((int)$customerA->getIdCustomer())
                    ->addStatus(SharedOrderExperienceManagementConfig::STATUS_ACTIVE),
            );

        // Act
        $statusCountCollectionTransfer = $this->tester->getFacade()->getRecurringScheduleStatusCountCollection($criteriaTransfer);

        // Assert
        $this->assertCount(1, $statusCountCollectionTransfer->getStatusCounts());
        $this->assertSame(2, $statusCountCollectionTransfer->getStatusCounts()->offsetGet(0)->getCountOrFail());
    }

    public function testExecutesSingleQueryForMultipleStatuses(): void
    {
        // Arrange
        $customer = $this->tester->haveCustomer();
        $idCustomer = (int)$customer->getIdCustomer();

        $this->tester->haveRecurringSchedule($idCustomer, [RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_ACTIVE]);
        $this->tester->haveRecurringSchedule($idCustomer, [RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_PAUSED]);
        $this->tester->haveRecurringSchedule($idCustomer, [RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_CANCELLED]);

        $criteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addCustomerId($idCustomer)
                    ->addStatus(SharedOrderExperienceManagementConfig::STATUS_ACTIVE)
                    ->addStatus(SharedOrderExperienceManagementConfig::STATUS_PAUSED)
                    ->addStatus(SharedOrderExperienceManagementConfig::STATUS_CANCELLED),
            );

        // Act
        $statusCountCollectionTransfer = $this->tester->getFacade()->getRecurringScheduleStatusCountCollection($criteriaTransfer);

        // Assert — one status count entry per status
        $this->assertCount(3, $statusCountCollectionTransfer->getStatusCounts());
    }
}
