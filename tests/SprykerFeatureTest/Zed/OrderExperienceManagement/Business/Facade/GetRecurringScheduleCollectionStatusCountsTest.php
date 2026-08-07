<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Facade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\PaginationTransfer;
use Generated\Shared\Transfer\RecurringScheduleCollectionTransfer;
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
 * @group OrderExperienceManagement
 * @group Business
 * @group Facade
 * @group GetRecurringScheduleCollectionStatusCountsTest
 * Add your own group annotations below this line
 */
class GetRecurringScheduleCollectionStatusCountsTest extends Unit
{
    protected const int SINGLE_ITEM_PER_PAGE = 1;

    protected const int FIRST_PAGE = 1;

    protected OrderExperienceManagementBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->ensureRecurringScheduleTablesAreEmpty();
    }

    public function testReturnsStatusCountsWhenStatusCountConditionsAreSet(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveCustomer();
        $idCustomer = (int)$customerTransfer->getIdCustomer();

        $this->tester->haveRecurringSchedule($idCustomer, [RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_PAUSED]);
        $this->tester->haveRecurringSchedule($idCustomer, [RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_PAUSED]);
        $this->tester->haveRecurringSchedule($idCustomer, [RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_FAILED]);

        $recurringScheduleCriteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())->addCustomerId($idCustomer),
            )
            ->setStatusCountConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addCustomerId($idCustomer)
                    ->addStatus(SharedOrderExperienceManagementConfig::STATUS_PAUSED)
                    ->addStatus(SharedOrderExperienceManagementConfig::STATUS_FAILED),
            );

        // Act
        $recurringScheduleCollectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($recurringScheduleCriteriaTransfer);

        // Assert
        $this->assertSame(
            [
                SharedOrderExperienceManagementConfig::STATUS_FAILED => 1,
                SharedOrderExperienceManagementConfig::STATUS_PAUSED => 2,
            ],
            $this->mapStatusCounts($recurringScheduleCollectionTransfer),
        );
    }

    public function testReturnsNoStatusCountsWhenStatusCountConditionsAreNull(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveCustomer();
        $idCustomer = (int)$customerTransfer->getIdCustomer();

        $this->tester->haveRecurringSchedule($idCustomer, [RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_PAUSED]);

        $recurringScheduleCriteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())->addCustomerId($idCustomer),
            );

        // Act
        $recurringScheduleCollectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($recurringScheduleCriteriaTransfer);

        // Assert
        $this->assertCount(1, $recurringScheduleCollectionTransfer->getRecurringSchedules());
        $this->assertCount(0, $recurringScheduleCollectionTransfer->getStatusCounts());
    }

    /**
     * The attention banner must keep showing the customer's paused/failed totals while the list
     * itself is filtered down to a single status, so the counts must not inherit the collection's
     * own `statuses` condition.
     */
    public function testStatusCountsIgnoreTheCollectionStatusFilter(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveCustomer();
        $idCustomer = (int)$customerTransfer->getIdCustomer();

        $this->tester->haveRecurringSchedule($idCustomer, [RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_ACTIVE]);
        $this->tester->haveRecurringSchedule($idCustomer, [RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_PAUSED]);
        $this->tester->haveRecurringSchedule($idCustomer, [RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_PAUSED]);

        $recurringScheduleCriteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addCustomerId($idCustomer)
                    ->addStatus(SharedOrderExperienceManagementConfig::STATUS_ACTIVE),
            )
            ->setStatusCountConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addCustomerId($idCustomer)
                    ->addStatus(SharedOrderExperienceManagementConfig::STATUS_PAUSED),
            );

        // Act
        $recurringScheduleCollectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($recurringScheduleCriteriaTransfer);

        // Assert
        $this->assertCount(1, $recurringScheduleCollectionTransfer->getRecurringSchedules());
        $this->assertSame(
            SharedOrderExperienceManagementConfig::STATUS_ACTIVE,
            $recurringScheduleCollectionTransfer->getRecurringSchedules()->offsetGet(0)->getStatus(),
        );
        $this->assertSame(
            [SharedOrderExperienceManagementConfig::STATUS_PAUSED => 2],
            $this->mapStatusCounts($recurringScheduleCollectionTransfer),
        );
    }

    public function testStatusCountsIgnoreTheCollectionPagination(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveCustomer();
        $idCustomer = (int)$customerTransfer->getIdCustomer();

        $this->tester->haveRecurringSchedule($idCustomer, [RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_PAUSED]);
        $this->tester->haveRecurringSchedule($idCustomer, [RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_PAUSED]);
        $this->tester->haveRecurringSchedule($idCustomer, [RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_PAUSED]);

        $recurringScheduleCriteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())->addCustomerId($idCustomer),
            )
            ->setPagination(
                (new PaginationTransfer())
                    ->setPage(static::FIRST_PAGE)
                    ->setMaxPerPage(static::SINGLE_ITEM_PER_PAGE),
            )
            ->setStatusCountConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addCustomerId($idCustomer)
                    ->addStatus(SharedOrderExperienceManagementConfig::STATUS_PAUSED),
            );

        // Act
        $recurringScheduleCollectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($recurringScheduleCriteriaTransfer);

        // Assert
        $this->assertCount(static::SINGLE_ITEM_PER_PAGE, $recurringScheduleCollectionTransfer->getRecurringSchedules());
        $this->assertSame(
            [SharedOrderExperienceManagementConfig::STATUS_PAUSED => 3],
            $this->mapStatusCounts($recurringScheduleCollectionTransfer),
        );
    }

    public function testStatusCountsHonourTheirOwnCustomerScope(): void
    {
        // Arrange
        $ownCustomerTransfer = $this->tester->haveCustomer();
        $otherCustomerTransfer = $this->tester->haveCustomer();
        $idOwnCustomer = (int)$ownCustomerTransfer->getIdCustomer();

        $this->tester->haveRecurringSchedule($idOwnCustomer, [RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_PAUSED]);
        $this->tester->haveRecurringSchedule((int)$otherCustomerTransfer->getIdCustomer(), [RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_PAUSED]);
        $this->tester->haveRecurringSchedule((int)$otherCustomerTransfer->getIdCustomer(), [RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_PAUSED]);

        $recurringScheduleCriteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())->addCustomerId($idOwnCustomer),
            )
            ->setStatusCountConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addCustomerId($idOwnCustomer)
                    ->addStatus(SharedOrderExperienceManagementConfig::STATUS_PAUSED),
            );

        // Act
        $recurringScheduleCollectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($recurringScheduleCriteriaTransfer);

        // Assert
        $this->assertSame(
            [SharedOrderExperienceManagementConfig::STATUS_PAUSED => 1],
            $this->mapStatusCounts($recurringScheduleCollectionTransfer),
        );
    }

    /**
     * Sorted by status because the row order of a `GROUP BY status` result is not part of the
     * contract and differs between database engines.
     *
     * @return array<string, int>
     */
    protected function mapStatusCounts(RecurringScheduleCollectionTransfer $recurringScheduleCollectionTransfer): array
    {
        $countByStatus = [];

        foreach ($recurringScheduleCollectionTransfer->getStatusCounts() as $recurringScheduleStatusCountTransfer) {
            $countByStatus[$recurringScheduleStatusCountTransfer->getStatusOrFail()] = $recurringScheduleStatusCountTransfer->getCountOrFail();
        }

        ksort($countByStatus);

        return $countByStatus;
    }
}
