<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Persistence;

use Codeception\Test\Unit;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleQuery;
use Orm\Zed\StateMachine\Persistence\SpyStateMachineItemStateHistory;
use Orm\Zed\StateMachine\Persistence\SpyStateMachineItemStateQuery;
use Orm\Zed\StateMachine\Persistence\SpyStateMachineProcessQuery;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementPersistenceFactory;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepository;
use SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Persistence
 * @group GetRecurringScheduleStuckItemsTest
 */
class GetRecurringScheduleStuckItemsTest extends Unit
{
    protected const string STATE_NOTIFYING = 'notifying';

    protected const string STATE_ACTIVE = 'active';

    protected const string OTHER_STATE_MACHINE_NAME = 'OtherStateMachine';

    protected const string OTHER_PROCESS_NAME = 'OtherProcess';

    protected const int STUCK_FOR_MINUTES = 30;

    protected const int FIRST_ENTERED_MINUTES_AGO = 300;

    protected const int TIMESTAMP_TOLERANCE_IN_SECONDS = 5;

    protected const int SCHEDULE_COUNT_ABOVE_CHUNK_SIZE = 3;

    protected OrderExperienceManagementBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->ensureRecurringScheduleTablesAreEmpty();
    }

    public function testReturnsScheduleInRequestedStateWithTheTimeItEnteredIt(): void
    {
        // Arrange
        $idRecurringSchedule = $this->haveStuckSchedule(static::STATE_NOTIFYING, static::STUCK_FOR_MINUTES);

        // Act
        $stuckItemTransfers = $this->getStuckItems();

        // Assert
        $this->assertCount(1, $stuckItemTransfers);
        $this->assertSame($idRecurringSchedule, $stuckItemTransfers[0]->getIdRecurringSchedule());
        $this->assertSame(static::STATE_NOTIFYING, $stuckItemTransfers[0]->getStateName());
        $this->assertStateEnteredMinutesAgo(static::STUCK_FOR_MINUTES, $stuckItemTransfers[0]->getStateEnteredAt());
        $this->assertFalse($stuckItemTransfers[0]->getIsStateHistoryMissing());
    }

    public function testIgnoresSchedulesInStatesThatWereNotRequested(): void
    {
        // Arrange
        $this->haveStuckSchedule(static::STATE_ACTIVE, static::STUCK_FOR_MINUTES);

        // Act
        $stuckItemTransfers = $this->getStuckItems();

        // Assert
        $this->assertSame([], $stuckItemTransfers);
    }

    public function testIgnoresStateWithTheSameNameInAnotherStateMachine(): void
    {
        // Arrange
        $idRecurringSchedule = $this->tester->haveRecurringSchedule((int)$this->tester->haveCustomer()->getIdCustomer())->getIdRecurringScheduleOrFail();
        $stateMachineProcessEntity = SpyStateMachineProcessQuery::create()
            ->filterByStateMachineName(static::OTHER_STATE_MACHINE_NAME)
            ->filterByName(static::OTHER_PROCESS_NAME)
            ->findOneOrCreate();
        $stateMachineProcessEntity->save();
        $stateMachineItemStateEntity = SpyStateMachineItemStateQuery::create()
            ->filterByFkStateMachineProcess($stateMachineProcessEntity->getIdStateMachineProcess())
            ->filterByName(static::STATE_NOTIFYING)
            ->findOneOrCreate();
        $stateMachineItemStateEntity->save();
        SpyRecurringScheduleQuery::create()
            ->filterByIdRecurringSchedule($idRecurringSchedule)
            ->update(['FkStateMachineItemState' => $stateMachineItemStateEntity->getIdStateMachineItemState()]);

        // Act
        $stuckItemTransfers = $this->getStuckItems();

        // Assert
        $this->assertSame([], $stuckItemTransfers);
    }

    public function testUsesTheLatestEntryWhenTheScheduleReEnteredTheState(): void
    {
        // Arrange
        $idRecurringSchedule = $this->haveStuckSchedule(static::STATE_NOTIFYING, static::STUCK_FOR_MINUTES);
        (new SpyStateMachineItemStateHistory())
            ->setFkStateMachineItemState((int)SpyRecurringScheduleQuery::create()->findOneByIdRecurringSchedule($idRecurringSchedule)->getFkStateMachineItemState())
            ->setIdentifier($idRecurringSchedule)
            ->setCreatedAt($this->tester->createTimestampMinutesAgo(static::FIRST_ENTERED_MINUTES_AGO))
            ->save();

        // Act
        $stuckItemTransfers = $this->getStuckItems();

        // Assert
        $this->assertCount(1, $stuckItemTransfers);
        $this->assertStateEnteredMinutesAgo(static::STUCK_FOR_MINUTES, $stuckItemTransfers[0]->getStateEnteredAt());
    }

    public function testFallsBackToScheduleCreationWhenTheStateHistoryRowIsMissing(): void
    {
        // Arrange
        $this->tester->haveStuckRecurringSchedule((int)$this->tester->haveCustomer()->getIdCustomer(), static::STATE_NOTIFYING, static::STUCK_FOR_MINUTES, null, false);

        // Act
        $stuckItemTransfers = $this->getStuckItems();

        // Assert
        $this->assertCount(1, $stuckItemTransfers);
        $this->assertTrue($stuckItemTransfers[0]->getIsStateHistoryMissing());
        $this->assertStateEnteredMinutesAgo(static::STUCK_FOR_MINUTES, $stuckItemTransfers[0]->getStateEnteredAt());
    }

    public function testResolvesStateEntryForEverySchedulePastTheChunkSize(): void
    {
        // Arrange
        for ($i = 0; $i < static::SCHEDULE_COUNT_ABOVE_CHUNK_SIZE; $i++) {
            $this->haveStuckSchedule(static::STATE_NOTIFYING, static::STUCK_FOR_MINUTES);
        }

        $repository = new class extends OrderExperienceManagementRepository {
            protected const int STATE_HISTORY_LOOKUP_CHUNK_SIZE = 1;
        };
        $repository->setFactory(new OrderExperienceManagementPersistenceFactory());

        // Act
        $stuckItemTransfers = $repository->getRecurringScheduleStuckItems(
            OrderExperienceManagementConfig::STATE_MACHINE_NAME,
            [static::STATE_NOTIFYING],
        );

        // Assert
        $this->assertCount(static::SCHEDULE_COUNT_ABOVE_CHUNK_SIZE, $stuckItemTransfers);
        foreach ($stuckItemTransfers as $stuckItemTransfer) {
            $this->assertFalse($stuckItemTransfer->getIsStateHistoryMissing());
        }
    }

    /**
     * @return array<\Generated\Shared\Transfer\RecurringScheduleStuckItemTransfer>
     */
    protected function getStuckItems(): array
    {
        return (new OrderExperienceManagementRepository())->getRecurringScheduleStuckItems(
            OrderExperienceManagementConfig::STATE_MACHINE_NAME,
            [static::STATE_NOTIFYING],
        );
    }

    protected function haveStuckSchedule(string $stateName, int $stuckForMinutes): int
    {
        return $this->tester->haveStuckRecurringSchedule((int)$this->tester->haveCustomer()->getIdCustomer(), $stateName, $stuckForMinutes);
    }

    protected function assertStateEnteredMinutesAgo(int $minutesAgo, ?string $stateEnteredAt): void
    {
        $this->assertEqualsWithDelta(
            strtotime($this->tester->createTimestampMinutesAgo($minutesAgo)),
            strtotime((string)$stateEnteredAt),
            static::TIMESTAMP_TOLERANCE_IN_SECONDS,
        );
    }
}
