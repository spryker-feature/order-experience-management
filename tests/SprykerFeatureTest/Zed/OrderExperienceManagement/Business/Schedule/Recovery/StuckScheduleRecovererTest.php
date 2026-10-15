<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Schedule\Recovery;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\StateMachineItemTransfer;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleQuery;
use PDOException;
use Propel\Runtime\Exception\PropelException;
use RuntimeException;
use Spryker\Zed\StateMachine\Business\Exception\LockException;
use Spryker\Zed\StateMachine\Business\StateMachineFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Recovery\StuckScheduleRecoverer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Recovery\StuckScheduleRecovererInterface;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementDependencyProvider;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementEntityManager;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepository;
use SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester;
use TypeError;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Business
 * @group Schedule
 * @group Recovery
 * @group StuckScheduleRecovererTest
 * Add your own group annotations below this line
 */
class StuckScheduleRecovererTest extends Unit
{
    protected const string STATE_NOTIFYING = 'notifying';

    protected const string STATE_CONFIRMED = 'confirmed';

    protected const string STATE_ORDER_PLACED = 'order_placed';

    protected const string STATE_COMPLETING = 'completing';

    protected const string STATE_SKIPPED = 'skipped';

    protected const string EVENT_FINALISE_PLACEMENT = 'finalise-placement';

    protected const string EVENT_NOTIFY_BUYER = 'notify-buyer';

    protected const int STUCK_FOR_MINUTES = 30;

    protected const int LONG_STUCK_FOR_MINUTES = 600;

    protected const int COOLDOWN_STUCK_FOR_MINUTES = 120;

    protected const int COOLDOWN_ELAPSED_LAST_TOUCHED_MINUTES_AGO = 100;

    protected const int SECONDS_PER_MINUTE = 60;

    protected const string SQLSTATE_UNIQUE_VIOLATION = '23000';

    protected const string SQLSTATE_GENERAL_ERROR = 'HY000';

    protected OrderExperienceManagementBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->ensureRecurringScheduleTablesAreEmpty();
    }

    /**
     * @dataProvider provideRecoverableStatesWithEvent
     */
    public function testRecoversScheduleStuckInRecoverableState(string $stateName, string $eventName): void
    {
        // Arrange
        $idRecurringSchedule = $this->haveStuckSchedule($stateName, static::STUCK_FOR_MINUTES);
        $stateMachineFacadeMock = $this->mockStateMachineFacade();
        $stateMachineFacadeMock->expects($this->once())
            ->method('triggerEvent')
            ->with(
                $eventName,
                $this->callback(fn (StateMachineItemTransfer $itemTransfer): bool => $itemTransfer->getIdentifier() === $idRecurringSchedule),
            )
            ->willReturn(1);

        // Act
        $recoveryResultTransfer = $this->createRecoverer()->recoverStuckSchedules();

        // Assert
        $this->assertSame(1, $recoveryResultTransfer->getRecoveredCount());
        $this->assertSame(0, $recoveryResultTransfer->getFailedCount());
        $this->assertFalse($recoveryResultTransfer->getIsAborted());
    }

    /**
     * @return array<string, array<string>>
     */
    public function provideRecoverableStatesWithEvent(): array
    {
        return [
            'notifying' => [static::STATE_NOTIFYING, static::EVENT_NOTIFY_BUYER],
            'order_placed' => [static::STATE_ORDER_PLACED, static::EVENT_FINALISE_PLACEMENT],
        ];
    }

    public function testIgnoresScheduleYoungerThanMinimumAge(): void
    {
        // Arrange
        $this->haveStuckSchedule(static::STATE_NOTIFYING, 1);
        $this->mockStateMachineFacade()->expects($this->never())->method('triggerEvent');

        // Act
        $recoveryResultTransfer = $this->createRecoverer()->recoverStuckSchedules();

        // Assert
        $this->assertSame(0, $recoveryResultTransfer->getRecoveredCount());
        $this->assertSame(0, $recoveryResultTransfer->getSkippedCount());
    }

    public function testSkipsScheduleWhoseLastAttemptIsStillCoolingDown(): void
    {
        // Arrange
        $this->haveStuckSchedule(static::STATE_NOTIFYING, static::STUCK_FOR_MINUTES, 1);
        $this->mockStateMachineFacade()->expects($this->never())->method('triggerEvent');

        // Act
        $recoveryResultTransfer = $this->createRecoverer()->recoverStuckSchedules();

        // Assert
        $this->assertSame(1, $recoveryResultTransfer->getSkippedCount());
        $this->assertSame(0, $recoveryResultTransfer->getRecoveredCount());
    }

    /**
     * @dataProvider provideReportOnlyStates
     */
    public function testOnlyReportsScheduleStuckInStateWhoseEventIsNotSafeToRepeat(string $stateName): void
    {
        // Arrange
        $this->haveStuckSchedule($stateName, static::STUCK_FOR_MINUTES);
        $this->mockStateMachineFacade()->expects($this->never())->method('triggerEvent');

        // Act
        $recoveryResultTransfer = $this->createRecoverer()->recoverStuckSchedules();

        // Assert
        $this->assertSame(1, $recoveryResultTransfer->getReportedCount());
        $this->assertSame(0, $recoveryResultTransfer->getRecoveredCount());
    }

    /**
     * @return array<string, array<string>>
     */
    public function provideReportOnlyStates(): array
    {
        return [
            'confirmed' => [static::STATE_CONFIRMED],
            'completing' => [static::STATE_COMPLETING],
            'skipped' => [static::STATE_SKIPPED],
        ];
    }

    public function testOnlyReportsScheduleStuckLongerThanMaximumAge(): void
    {
        // Arrange
        $this->haveStuckSchedule(static::STATE_NOTIFYING, static::LONG_STUCK_FOR_MINUTES);
        $this->mockStateMachineFacade()->expects($this->never())->method('triggerEvent');

        // Act
        $recoveryResultTransfer = $this->createRecoverer()->recoverStuckSchedules();

        // Assert
        $this->assertSame(1, $recoveryResultTransfer->getReportedCount());
        $this->assertSame(0, $recoveryResultTransfer->getRecoveredCount());
    }

    public function testSkipsScheduleWhoseLockIsHeldByAnotherTrigger(): void
    {
        // Arrange
        $this->haveStuckSchedule(static::STATE_NOTIFYING, static::STUCK_FOR_MINUTES);
        $this->mockStateMachineFacade()->method('triggerEvent')->willThrowException($this->createLockException(static::SQLSTATE_UNIQUE_VIOLATION));

        // Act
        $recoveryResultTransfer = $this->createRecoverer()->recoverStuckSchedules();

        // Assert
        $this->assertSame(1, $recoveryResultTransfer->getSkippedCount());
        $this->assertSame(0, $recoveryResultTransfer->getFailedCount());
    }

    public function testSkipsScheduleWhenEventDoesNotApplyAnymore(): void
    {
        // Arrange
        $this->haveStuckSchedule(static::STATE_NOTIFYING, static::STUCK_FOR_MINUTES);
        $this->mockStateMachineFacade()->method('triggerEvent')->willReturn(0);

        // Act
        $recoveryResultTransfer = $this->createRecoverer()->recoverStuckSchedules();

        // Assert
        $this->assertSame(1, $recoveryResultTransfer->getSkippedCount());
        $this->assertSame(0, $recoveryResultTransfer->getRecoveredCount());
    }

    public function testAbortsRunAfterConsecutiveFailuresInsteadOfTryingEverySchedule(): void
    {
        // Arrange
        $maximumConsecutiveFailures = StuckScheduleRecoverer::MAXIMUM_CONSECUTIVE_FAILURES;

        for ($i = 0; $i <= $maximumConsecutiveFailures; $i++) {
            $this->haveStuckSchedule(static::STATE_NOTIFYING, static::STUCK_FOR_MINUTES);
        }

        $stateMachineFacadeMock = $this->mockStateMachineFacade();
        $stateMachineFacadeMock->expects($this->exactly($maximumConsecutiveFailures))
            ->method('triggerEvent')
            ->willThrowException(new RuntimeException('database is restarting'));

        // Act
        $recoveryResultTransfer = $this->createRecoverer()->recoverStuckSchedules();

        // Assert
        $this->assertTrue($recoveryResultTransfer->getIsAborted());
        $this->assertSame($maximumConsecutiveFailures, $recoveryResultTransfer->getFailedCount());
    }

    public function testSchedulesThatAreOnlyReportedOrSkippedDoNotUseBatchSlots(): void
    {
        // Arrange
        $this->haveStuckSchedule(static::STATE_CONFIRMED, static::STUCK_FOR_MINUTES);
        $this->haveStuckSchedule(static::STATE_NOTIFYING, static::LONG_STUCK_FOR_MINUTES);
        $this->haveStuckSchedule(static::STATE_NOTIFYING, static::STUCK_FOR_MINUTES, 1);
        $idRecoverableSchedule = $this->haveStuckSchedule(static::STATE_NOTIFYING, static::STUCK_FOR_MINUTES);

        $stateMachineFacadeMock = $this->mockStateMachineFacade();
        $stateMachineFacadeMock->expects($this->once())
            ->method('triggerEvent')
            ->with(
                static::EVENT_NOTIFY_BUYER,
                $this->callback(fn (StateMachineItemTransfer $itemTransfer): bool => $itemTransfer->getIdentifier() === $idRecoverableSchedule),
            )
            ->willReturn(1);

        // Act
        $recoveryResultTransfer = $this->createRecovererWithBatchSizeOfOne($stateMachineFacadeMock)->recoverStuckSchedules();

        // Assert
        $this->assertSame(1, $recoveryResultTransfer->getRecoveredCount());
        $this->assertSame(2, $recoveryResultTransfer->getReportedCount());
        $this->assertSame(1, $recoveryResultTransfer->getSkippedCount());
    }

    public function testRecoveryAttemptsPerRunAreLimitedByBatchSize(): void
    {
        // Arrange
        $this->haveStuckSchedule(static::STATE_NOTIFYING, static::STUCK_FOR_MINUTES);
        $this->haveStuckSchedule(static::STATE_NOTIFYING, static::STUCK_FOR_MINUTES);

        $stateMachineFacadeMock = $this->mockStateMachineFacade();
        $stateMachineFacadeMock->expects($this->once())->method('triggerEvent')->willReturn(1);

        // Act
        $recoveryResultTransfer = $this->createRecovererWithBatchSizeOfOne($stateMachineFacadeMock)->recoverStuckSchedules();

        // Assert
        $this->assertSame(1, $recoveryResultTransfer->getRecoveredCount());
        $this->assertSame(1, $recoveryResultTransfer->getSkippedCount());
    }

    public function testDoesNotReportStuckScheduleAgainWhileCoolingDown(): void
    {
        // Arrange
        $this->haveStuckSchedule(static::STATE_CONFIRMED, static::STUCK_FOR_MINUTES, 1);
        $this->mockStateMachineFacade()->expects($this->never())->method('triggerEvent');

        // Act
        $recoveryResultTransfer = $this->createRecoverer()->recoverStuckSchedules();

        // Assert
        $this->assertSame(0, $recoveryResultTransfer->getReportedCount());
        $this->assertSame(1, $recoveryResultTransfer->getSkippedCount());
    }

    public function testReportingStuckScheduleStartsCooldownForNextRun(): void
    {
        // Arrange
        $this->haveStuckSchedule(static::STATE_CONFIRMED, static::STUCK_FOR_MINUTES);
        $this->mockStateMachineFacade()->expects($this->never())->method('triggerEvent');
        $this->createRecoverer()->recoverStuckSchedules();

        // Act
        $recoveryResultTransfer = $this->createRecoverer()->recoverStuckSchedules();

        // Assert
        $this->assertSame(0, $recoveryResultTransfer->getReportedCount());
        $this->assertSame(1, $recoveryResultTransfer->getSkippedCount());
    }

    public function testReportsScheduleWithoutStateHistoryUsingItsCreationAsStateEntry(): void
    {
        // Arrange
        $this->haveStuckSchedule(static::STATE_NOTIFYING, static::LONG_STUCK_FOR_MINUTES, null, false);
        $this->mockStateMachineFacade()->expects($this->never())->method('triggerEvent');

        // Act
        $recoveryResultTransfer = $this->createRecoverer()->recoverStuckSchedules();

        // Assert
        $this->assertSame(1, $recoveryResultTransfer->getReportedCount());
    }

    public function testRecoversScheduleOnceItsCooldownHasElapsed(): void
    {
        // Arrange
        $this->haveStuckSchedule(static::STATE_NOTIFYING, static::COOLDOWN_STUCK_FOR_MINUTES, static::COOLDOWN_ELAPSED_LAST_TOUCHED_MINUTES_AGO);
        $this->mockStateMachineFacade()->expects($this->once())->method('triggerEvent')->willReturn(1);

        // Act
        $recoveryResultTransfer = $this->createRecoverer()->recoverStuckSchedules();

        // Assert
        $this->assertSame(1, $recoveryResultTransfer->getRecoveredCount());
    }

    public function testSuccessfulRecoveryResetsTheConsecutiveFailureCounter(): void
    {
        // Arrange
        $maximumConsecutiveFailures = StuckScheduleRecoverer::MAXIMUM_CONSECUTIVE_FAILURES;

        for ($i = 0; $i <= $maximumConsecutiveFailures; $i++) {
            $this->haveStuckSchedule(static::STATE_NOTIFYING, static::STUCK_FOR_MINUTES);
        }

        $outcomes = array_merge(
            [new RuntimeException('database is restarting'), 1],
            array_fill(0, $maximumConsecutiveFailures - 1, new RuntimeException('database is restarting')),
        );
        $this->mockStateMachineFacade()
            ->method('triggerEvent')
            ->willReturnCallback(function () use (&$outcomes): int {
                $outcome = array_shift($outcomes);
                if ($outcome instanceof RuntimeException) {
                    throw $outcome;
                }

                return $outcome;
            });

        // Act
        $recoveryResultTransfer = $this->createRecoverer()->recoverStuckSchedules();

        // Assert
        $this->assertFalse($recoveryResultTransfer->getIsAborted());
        $this->assertSame(1, $recoveryResultTransfer->getRecoveredCount());
        $this->assertSame($maximumConsecutiveFailures, $recoveryResultTransfer->getFailedCount());
    }

    public function testAttemptStartsCooldownEvenWhenTheAttemptFails(): void
    {
        // Arrange
        $idRecurringSchedule = $this->haveStuckSchedule(static::STATE_NOTIFYING, static::STUCK_FOR_MINUTES);
        $this->mockStateMachineFacade()
            ->expects($this->once())
            ->method('triggerEvent')
            ->willThrowException(new RuntimeException('process died'));
        $this->createRecoverer()->recoverStuckSchedules();

        // Act
        $recoveryResultTransfer = $this->createRecoverer()->recoverStuckSchedules();

        // Assert
        $updatedAt = SpyRecurringScheduleQuery::create()->findOneByIdRecurringSchedule($idRecurringSchedule)->getUpdatedAt();
        $this->assertGreaterThan(time() - static::SECONDS_PER_MINUTE, $updatedAt->getTimestamp());
        $this->assertSame(1, $recoveryResultTransfer->getSkippedCount());
        $this->assertSame(0, $recoveryResultTransfer->getFailedCount());
    }

    public function testProgrammingErrorIsNotTurnedIntoFailureCount(): void
    {
        // Arrange
        $this->haveStuckSchedule(static::STATE_NOTIFYING, static::STUCK_FOR_MINUTES);
        $this->mockStateMachineFacade()->method('triggerEvent')->willThrowException(new TypeError('broken command plugin'));

        // Expect
        $this->expectException(TypeError::class);

        // Act
        $this->createRecoverer()->recoverStuckSchedules();
    }

    public function testCountsDatabaseErrorDuringLockAcquisitionAsFailure(): void
    {
        // Arrange
        $this->haveStuckSchedule(static::STATE_NOTIFYING, static::STUCK_FOR_MINUTES);
        $this->mockStateMachineFacade()->method('triggerEvent')->willThrowException($this->createLockException(static::SQLSTATE_GENERAL_ERROR));

        // Act
        $recoveryResultTransfer = $this->createRecoverer()->recoverStuckSchedules();

        // Assert
        $this->assertSame(1, $recoveryResultTransfer->getFailedCount());
        $this->assertSame(0, $recoveryResultTransfer->getSkippedCount());
    }

    public function testAbortsRunAfterConsecutiveDatabaseErrorsDuringLockAcquisition(): void
    {
        // Arrange
        $maximumConsecutiveFailures = StuckScheduleRecoverer::MAXIMUM_CONSECUTIVE_FAILURES;

        for ($i = 0; $i <= $maximumConsecutiveFailures; $i++) {
            $this->haveStuckSchedule(static::STATE_NOTIFYING, static::STUCK_FOR_MINUTES);
        }

        $stateMachineFacadeMock = $this->mockStateMachineFacade();
        $stateMachineFacadeMock->expects($this->exactly($maximumConsecutiveFailures))
            ->method('triggerEvent')
            ->willThrowException($this->createLockException(static::SQLSTATE_GENERAL_ERROR));

        // Act
        $recoveryResultTransfer = $this->createRecoverer()->recoverStuckSchedules();

        // Assert
        $this->assertTrue($recoveryResultTransfer->getIsAborted());
    }

    protected function createLockException(string $sqlState): LockException
    {
        $pdoException = new PDOException('SQLSTATE[' . $sqlState . ']');
        $pdoException->errorInfo = [$sqlState];

        return new LockException('State machine trigger is locked.', 0, new PropelException('Unable to execute INSERT statement', 0, $pdoException));
    }

    protected function haveStuckSchedule(
        string $stateName,
        int $stuckForMinutes,
        ?int $lastTouchedMinutesAgo = null,
        bool $hasStateHistory = true
    ): int {
        return $this->tester->haveStuckRecurringSchedule(
            (int)$this->tester->haveCustomer()->getIdCustomer(),
            $stateName,
            $stuckForMinutes,
            $lastTouchedMinutesAgo,
            $hasStateHistory,
        );
    }

    protected function mockStateMachineFacade(): StateMachineFacadeInterface
    {
        $stateMachineFacadeMock = $this->createMock(StateMachineFacadeInterface::class);
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_STATE_MACHINE, $stateMachineFacadeMock);

        return $stateMachineFacadeMock;
    }

    protected function createRecoverer(): StuckScheduleRecovererInterface
    {
        return $this->tester->getFactory()->createStuckScheduleRecoverer();
    }

    protected function createRecovererWithBatchSizeOfOne(StateMachineFacadeInterface $stateMachineFacade): StuckScheduleRecovererInterface
    {
        return new class (
            new OrderExperienceManagementRepository(),
            new OrderExperienceManagementEntityManager(),
            $stateMachineFacade,
            new OrderExperienceManagementConfig(),
        ) extends StuckScheduleRecoverer {
            public const int BATCH_SIZE = 1;
        };
    }
}
