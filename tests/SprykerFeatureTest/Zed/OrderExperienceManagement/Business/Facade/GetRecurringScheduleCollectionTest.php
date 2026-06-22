<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Facade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\PaginationTransfer;
use Generated\Shared\Transfer\RecurringScheduleConditionsTransfer;
use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\SortTransfer;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\MonthlyCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementDependencyProvider;
use SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Facade
 * @group GetRecurringScheduleCollectionTest
 * Add your own group annotations below this line
 */
class GetRecurringScheduleCollectionTest extends Unit
{
    protected const int NON_EXISTENT_CUSTOMER_ID = 0;

    protected OrderExperienceManagementBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->ensureRecurringScheduleTablesAreEmpty();
    }

    public function testReturnsCollectionFilteredByCustomerId(): void
    {
        // Arrange
        $customerA = $this->tester->haveCustomer();
        $customerB = $this->tester->haveCustomer();

        $this->tester->haveRecurringSchedule((int)$customerA->getIdCustomer());
        $this->tester->haveRecurringSchedule((int)$customerA->getIdCustomer());
        $this->tester->haveRecurringSchedule((int)$customerB->getIdCustomer());

        $criteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addCustomerId((int)$customerA->getIdCustomer()),
            );

        // Act
        $collectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($criteriaTransfer);

        // Assert
        $this->assertCount(2, $collectionTransfer->getRecurringSchedules());
    }

    public function testExpandsScheduleWithNextTriggerDateAfterSkipForMonthlyCadence(): void
    {
        // Arrange
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::PLUGINS_CADENCE_TYPE, [new MonthlyCadenceTypePlugin()]);

        $customer = $this->tester->haveCustomer();

        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule((int)$customer->getIdCustomer(), [
            RecurringScheduleTransfer::CADENCE_TYPE => SharedOrderExperienceManagementConfig::CADENCE_TYPE_MONTHLY,
            RecurringScheduleTransfer::CADENCE_VALUE => 1,
            RecurringScheduleTransfer::NEXT_TRIGGER_DATE => '2026-07-12',
        ]);

        $criteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addUuid($recurringScheduleTransfer->getUuidOrFail())
                    ->setIsWithSkipPreview(true),
            );

        // Act
        $collectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($criteriaTransfer);

        // Assert
        $this->assertSame(
            '2026-08-12',
            $collectionTransfer->getRecurringSchedules()->offsetGet(0)->getNextTriggerDateAfterSkip(),
        );
    }

    public function testDoesNotExpandSkipPreviewWhenConditionDisabled(): void
    {
        // Arrange
        $customer = $this->tester->haveCustomer();

        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule((int)$customer->getIdCustomer(), [
            RecurringScheduleTransfer::CADENCE_TYPE => SharedOrderExperienceManagementConfig::CADENCE_TYPE_MONTHLY,
            RecurringScheduleTransfer::NEXT_TRIGGER_DATE => '2026-07-12',
        ]);

        $criteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addUuid($recurringScheduleTransfer->getUuidOrFail()),
            );

        // Act
        $collectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($criteriaTransfer);

        // Assert
        $this->assertNull($collectionTransfer->getRecurringSchedules()->offsetGet(0)->getNextTriggerDateAfterSkip());
    }

    public function testReturnsCollectionFilteredByStatus(): void
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
                    ->addStatus(SharedOrderExperienceManagementConfig::STATUS_ACTIVE),
            );

        // Act
        $collectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($criteriaTransfer);

        // Assert
        $this->assertCount(2, $collectionTransfer->getRecurringSchedules());
        foreach ($collectionTransfer->getRecurringSchedules() as $scheduleTransfer) {
            $this->assertSame(SharedOrderExperienceManagementConfig::STATUS_ACTIVE, $scheduleTransfer->getStatus());
        }
    }

    public function testAppliesPaginationAndReturnsNbResults(): void
    {
        // Arrange
        $customer = $this->tester->haveCustomer();
        $idCustomer = (int)$customer->getIdCustomer();

        $this->tester->haveRecurringSchedule($idCustomer);
        $this->tester->haveRecurringSchedule($idCustomer);
        $this->tester->haveRecurringSchedule($idCustomer);

        $criteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addCustomerId($idCustomer),
            )
            ->setPagination(
                (new PaginationTransfer())->setPage(1)->setMaxPerPage(2),
            );

        // Act
        $collectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($criteriaTransfer);

        // Assert
        $this->assertCount(2, $collectionTransfer->getRecurringSchedules());
        $this->assertSame(3, $collectionTransfer->getPaginationOrFail()->getNbResults());
    }

    public function testReturnsEmptyCollectionWhenNoMatchingSchedulesExist(): void
    {
        // Arrange
        $criteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addCustomerId(static::NON_EXISTENT_CUSTOMER_ID),
            );

        // Act
        $collectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($criteriaTransfer);

        // Assert
        $this->assertCount(0, $collectionTransfer->getRecurringSchedules());
    }

    public function testReturnsCollectionFilteredByUuid(): void
    {
        // Arrange
        $customer = $this->tester->haveCustomer();
        $idCustomer = (int)$customer->getIdCustomer();

        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer);
        $this->tester->haveRecurringSchedule($idCustomer);

        $criteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addUuid($recurringScheduleTransfer->getUuidOrFail()),
            );

        // Act
        $collectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($criteriaTransfer);

        // Assert
        $this->assertCount(1, $collectionTransfer->getRecurringSchedules());
        $this->assertSame(
            $recurringScheduleTransfer->getUuid(),
            $collectionTransfer->getRecurringSchedules()->offsetGet(0)->getUuid(),
        );
    }

    public function testReturnsCollectionFilteredByIdRecurringSchedule(): void
    {
        // Arrange
        $customer = $this->tester->haveCustomer();
        $idCustomer = (int)$customer->getIdCustomer();

        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer);
        $this->tester->haveRecurringSchedule($idCustomer);

        $criteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addIdRecurringSchedule($recurringScheduleTransfer->getIdRecurringScheduleOrFail()),
            );

        // Act
        $collectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($criteriaTransfer);

        // Assert
        $this->assertCount(1, $collectionTransfer->getRecurringSchedules());
        $this->assertSame(
            $recurringScheduleTransfer->getIdRecurringSchedule(),
            $collectionTransfer->getRecurringSchedules()->offsetGet(0)->getIdRecurringSchedule(),
        );
    }

    public function testFiltersByNameSearchUsingPartialMatch(): void
    {
        // Arrange
        $customer = $this->tester->haveCustomer();
        $idCustomer = (int)$customer->getIdCustomer();

        $this->tester->haveRecurringSchedule($idCustomer, [RecurringScheduleTransfer::NAME => 'Steel coils Werk 3']);
        $this->tester->haveRecurringSchedule($idCustomer, [RecurringScheduleTransfer::NAME => 'Office supplies']);

        $criteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addCustomerId($idCustomer)
                    ->addName('coils'),
            );

        // Act
        $collectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($criteriaTransfer);

        // Assert
        $this->assertCount(1, $collectionTransfer->getRecurringSchedules());
        $this->assertSame('Steel coils Werk 3', $collectionTransfer->getRecurringSchedules()->offsetGet(0)->getName());
    }

    public function testSortsByNextTriggerDateAscendingByDefault(): void
    {
        // Arrange
        $customer = $this->tester->haveCustomer();
        $idCustomer = (int)$customer->getIdCustomer();

        $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::NAME => 'middle',
            RecurringScheduleTransfer::NEXT_TRIGGER_DATE => '2026-07-10',
        ]);
        $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::NAME => 'earliest',
            RecurringScheduleTransfer::NEXT_TRIGGER_DATE => '2026-07-01',
        ]);
        $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::NAME => 'latest',
            RecurringScheduleTransfer::NEXT_TRIGGER_DATE => '2026-07-20',
        ]);

        $criteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())->addCustomerId($idCustomer),
            );

        // Act
        $collectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($criteriaTransfer);

        // Assert — no sort provided, so the default next-trigger-date ascending order applies
        $names = [];
        foreach ($collectionTransfer->getRecurringSchedules() as $scheduleTransfer) {
            $names[] = $scheduleTransfer->getName();
        }

        $this->assertSame(['earliest', 'middle', 'latest'], $names);
    }

    public function testSortsByNameDescendingWhenSortProvided(): void
    {
        // Arrange
        $customer = $this->tester->haveCustomer();
        $idCustomer = (int)$customer->getIdCustomer();

        $this->tester->haveRecurringSchedule($idCustomer, [RecurringScheduleTransfer::NAME => 'Alpha']);
        $this->tester->haveRecurringSchedule($idCustomer, [RecurringScheduleTransfer::NAME => 'Charlie']);
        $this->tester->haveRecurringSchedule($idCustomer, [RecurringScheduleTransfer::NAME => 'Bravo']);

        $criteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())->addCustomerId($idCustomer),
            )
            ->addSort(
                (new SortTransfer())->setField('spy_recurring_schedule.name')->setIsAscending(false),
            );

        // Act
        $collectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($criteriaTransfer);

        // Assert
        $names = [];
        foreach ($collectionTransfer->getRecurringSchedules() as $scheduleTransfer) {
            $names[] = $scheduleTransfer->getName();
        }

        $this->assertSame(['Charlie', 'Bravo', 'Alpha'], $names);
    }

    public function testAppliesOffsetAndLimitPagination(): void
    {
        // Arrange
        $customer = $this->tester->haveCustomer();
        $idCustomer = (int)$customer->getIdCustomer();

        $this->tester->haveRecurringSchedule($idCustomer);
        $this->tester->haveRecurringSchedule($idCustomer);
        $this->tester->haveRecurringSchedule($idCustomer);

        $criteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())->addCustomerId($idCustomer),
            )
            ->setPagination(
                (new PaginationTransfer())->setOffset(1)->setLimit(1),
            );

        // Act
        $collectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($criteriaTransfer);

        // Assert
        $this->assertCount(1, $collectionTransfer->getRecurringSchedules());
        $this->assertSame(3, $collectionTransfer->getPaginationOrFail()->getNbResults());
    }

    public function testLoadsItemsAndEstimatedTotalWhenIsWithItems(): void
    {
        // Arrange
        $customer = $this->tester->haveCustomer();
        $idCustomer = (int)$customer->getIdCustomer();

        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::PRICE_MODE => 'GROSS_MODE',
        ]);
        $idRecurringSchedule = $recurringScheduleTransfer->getIdRecurringScheduleOrFail();

        $this->tester->haveRecurringScheduleItem($idRecurringSchedule, [
            RecurringScheduleItemTransfer::QUANTITY => 2,
            RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500,
        ]);
        $this->tester->haveRecurringScheduleItem($idRecurringSchedule, [
            RecurringScheduleItemTransfer::QUANTITY => 1,
            RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 300,
        ]);

        $criteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addCustomerId($idCustomer)
                    ->setIsWithItems(true),
            );

        // Act
        $collectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($criteriaTransfer);

        // Assert
        $scheduleTransfer = $collectionTransfer->getRecurringSchedules()->offsetGet(0);
        $this->assertCount(2, $scheduleTransfer->getItems());
        // estimatedTotal = (2 * 500) + (1 * 300)
        $this->assertSame(1300, $scheduleTransfer->getEstimatedTotal());
    }

    public function testLoadsHistoryWhenIsWithHistory(): void
    {
        // Arrange
        $customer = $this->tester->haveCustomer();
        $idCustomer = (int)$customer->getIdCustomer();

        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer);
        $idRecurringSchedule = $recurringScheduleTransfer->getIdRecurringScheduleOrFail();

        $this->tester->haveRecurringScheduleHistory($idRecurringSchedule);
        $this->tester->haveRecurringScheduleHistory($idRecurringSchedule);

        $criteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addCustomerId($idCustomer)
                    ->setIsWithHistory(true),
            );

        // Act
        $collectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($criteriaTransfer);

        // Assert
        $this->assertCount(2, $collectionTransfer->getRecurringSchedules()->offsetGet(0)->getHistory());
    }

    public function testGroupsItemsByGroupKeyAndSumsQuantities(): void
    {
        // Arrange
        $customer = $this->tester->haveCustomer();
        $idCustomer = (int)$customer->getIdCustomer();

        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::PRICE_MODE => 'GROSS_MODE',
        ]);
        $idRecurringSchedule = $recurringScheduleTransfer->getIdRecurringScheduleOrFail();

        // Two items share the same groupKey — their quantities should be merged
        $this->tester->haveRecurringScheduleItem($idRecurringSchedule, [
            RecurringScheduleItemTransfer::GROUP_KEY => 'group-a',
            RecurringScheduleItemTransfer::QUANTITY => 3,
            RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 200,
        ]);
        $this->tester->haveRecurringScheduleItem($idRecurringSchedule, [
            RecurringScheduleItemTransfer::GROUP_KEY => 'group-a',
            RecurringScheduleItemTransfer::QUANTITY => 2,
            RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 200,
        ]);
        // One item has no groupKey — must not be merged
        $this->tester->haveRecurringScheduleItem($idRecurringSchedule, [
            RecurringScheduleItemTransfer::QUANTITY => 1,
            RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500,
        ]);

        $criteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addCustomerId($idCustomer)
                    ->setIsWithItems(true)
                    ->setGroupItemsByGroupKey(true),
            );

        // Act
        $collectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($criteriaTransfer);

        // Assert
        $scheduleTransfer = $collectionTransfer->getRecurringSchedules()->offsetGet(0);

        // 3 DB items collapsed to 2: one grouped + one ungrouped
        $this->assertCount(2, $scheduleTransfer->getItems());

        $quantities = [];
        foreach ($scheduleTransfer->getItems() as $itemTransfer) {
            $quantities[] = $itemTransfer->getQuantity();
        }
        sort($quantities);

        // Ungrouped item: qty 1; grouped item: qty 3+2=5
        $this->assertSame([1, 5], $quantities);
        // estimatedTotal = (5*200) + (1*500) = 1500
        $this->assertSame(1500, $scheduleTransfer->getEstimatedTotal());
    }

    public function testDoesNotGroupItemsWhenGroupItemsByGroupKeyIsNotSet(): void
    {
        // Arrange
        $customer = $this->tester->haveCustomer();
        $idCustomer = (int)$customer->getIdCustomer();

        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::PRICE_MODE => 'GROSS_MODE',
        ]);
        $idRecurringSchedule = $recurringScheduleTransfer->getIdRecurringScheduleOrFail();

        $this->tester->haveRecurringScheduleItem($idRecurringSchedule, [
            RecurringScheduleItemTransfer::GROUP_KEY => 'group-a',
            RecurringScheduleItemTransfer::QUANTITY => 3,
            RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 200,
        ]);
        $this->tester->haveRecurringScheduleItem($idRecurringSchedule, [
            RecurringScheduleItemTransfer::GROUP_KEY => 'group-a',
            RecurringScheduleItemTransfer::QUANTITY => 2,
            RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 200,
        ]);

        $criteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addCustomerId($idCustomer)
                    ->setIsWithItems(true),
            );

        // Act
        $collectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($criteriaTransfer);

        // Assert
        $this->assertCount(2, $collectionTransfer->getRecurringSchedules()->offsetGet(0)->getItems());
    }
}
