<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Recovery;

use Exception;
use Generated\Shared\Transfer\RecurringScheduleRecoveryResultTransfer;
use Generated\Shared\Transfer\RecurringScheduleStuckItemTransfer;
use Generated\Shared\Transfer\StateMachineItemTransfer;
use PDOException;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\StateMachine\Business\Exception\LockException;
use Spryker\Zed\StateMachine\Business\StateMachineFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementEntityManagerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface;

class StuckScheduleRecoverer implements StuckScheduleRecovererInterface
{
    use LoggerTrait;

    public const int MINIMUM_AGE_IN_SECONDS = 900;

    public const int MAXIMUM_AGE_IN_SECONDS = 21600;

    public const int BATCH_SIZE = 50;

    public const int MAXIMUM_CONSECUTIVE_FAILURES = 3;

    protected const float COOLDOWN_AGE_RATIO = 0.5;

    protected const string SQLSTATE_CLASS_INTEGRITY_CONSTRAINT_VIOLATION = '23';

    public function __construct(
        protected readonly OrderExperienceManagementRepositoryInterface $repository,
        protected readonly OrderExperienceManagementEntityManagerInterface $entityManager,
        protected readonly StateMachineFacadeInterface $stateMachineFacade,
        protected readonly OrderExperienceManagementConfig $config,
    ) {
    }

    public function recoverStuckSchedules(): RecurringScheduleRecoveryResultTransfer
    {
        $recoveryResultTransfer = (new RecurringScheduleRecoveryResultTransfer())
            ->setRecoveredCount(0)
            ->setSkippedCount(0)
            ->setFailedCount(0)
            ->setReportedCount(0)
            ->setIsAborted(false);

        $attemptCount = 0;
        $consecutiveFailureCount = 0;
        $now = time();

        foreach ($this->getStuckItems() as $stuckItemTransfer) {
            $ageInSeconds = $now - (int)strtotime($stuckItemTransfer->getStateEnteredAtOrFail());

            if ($ageInSeconds < static::MINIMUM_AGE_IN_SECONDS) {
                continue;
            }

            if ($this->isCoolingDown($stuckItemTransfer, $ageInSeconds, $now)) {
                $recoveryResultTransfer->setSkippedCount($recoveryResultTransfer->getSkippedCountOrFail() + 1);

                continue;
            }

            if (!$this->isRecoverable($stuckItemTransfer, $ageInSeconds)) {
                $this->reportStuckItem($stuckItemTransfer, $ageInSeconds);
                $recoveryResultTransfer->setReportedCount($recoveryResultTransfer->getReportedCountOrFail() + 1);

                continue;
            }

            if ($attemptCount >= static::BATCH_SIZE) {
                $recoveryResultTransfer->setSkippedCount($recoveryResultTransfer->getSkippedCountOrFail() + 1);

                continue;
            }

            $attemptCount++;

            $consecutiveFailureCount = $this->recoverStuckItem($stuckItemTransfer, $ageInSeconds, $recoveryResultTransfer)
                ? 0
                : $consecutiveFailureCount + 1;

            if ($consecutiveFailureCount >= static::MAXIMUM_CONSECUTIVE_FAILURES) {
                return $this->abort($recoveryResultTransfer);
            }
        }

        return $recoveryResultTransfer;
    }

    /**
     * @return array<\Generated\Shared\Transfer\RecurringScheduleStuckItemTransfer>
     */
    protected function getStuckItems(): array
    {
        return $this->repository->getRecurringScheduleStuckItems(
            $this->config->getStateMachineName(),
            array_merge(
                array_keys($this->config->getRecoverableStuckScheduleEvents()),
                $this->config->getReportOnlyStuckScheduleStates(),
            ),
        );
    }

    protected function isRecoverable(RecurringScheduleStuckItemTransfer $stuckItemTransfer, int $ageInSeconds): bool
    {
        return isset($this->config->getRecoverableStuckScheduleEvents()[$stuckItemTransfer->getStateNameOrFail()])
            && $ageInSeconds <= static::MAXIMUM_AGE_IN_SECONDS;
    }

    protected function isCoolingDown(RecurringScheduleStuckItemTransfer $stuckItemTransfer, int $ageInSeconds, int $now): bool
    {
        $cooldownInSeconds = max(
            static::MINIMUM_AGE_IN_SECONDS,
            (int)($ageInSeconds * static::COOLDOWN_AGE_RATIO),
        );

        return $now - (int)strtotime($stuckItemTransfer->getLastTouchedAtOrFail()) < $cooldownInSeconds;
    }

    protected function recoverStuckItem(
        RecurringScheduleStuckItemTransfer $stuckItemTransfer,
        int $ageInSeconds,
        RecurringScheduleRecoveryResultTransfer $recoveryResultTransfer,
    ): bool {
        $idRecurringSchedule = $stuckItemTransfer->getIdRecurringScheduleOrFail();
        $event = $this->config->getRecoverableStuckScheduleEvents()[$stuckItemTransfer->getStateNameOrFail()];

        // Recorded before the attempt: if the attempt dies without a trace, the cooldown still applies.
        $this->entityManager->updateRecurringScheduleUpdatedAt($idRecurringSchedule);

        try {
            $affectedCount = $this->stateMachineFacade->triggerEvent(
                $event,
                (new StateMachineItemTransfer())
                    ->setIdentifier($idRecurringSchedule)
                    ->setIdItemState($stuckItemTransfer->getIdStateMachineItemStateOrFail()),
            );
        } catch (LockException $lockException) {
            if (!$this->isLockHeldByAnotherProcess($lockException)) {
                return $this->reportFailedAttempt($stuckItemTransfer, $event, $lockException, $recoveryResultTransfer);
            }

            $recoveryResultTransfer->setSkippedCount($recoveryResultTransfer->getSkippedCountOrFail() + 1);

            return true;
        } catch (Exception $exception) {
            return $this->reportFailedAttempt($stuckItemTransfer, $event, $exception, $recoveryResultTransfer);
        }

        if ($affectedCount === 0) {
            $recoveryResultTransfer->setSkippedCount($recoveryResultTransfer->getSkippedCountOrFail() + 1);

            return true;
        }

        $this->getLogger()->warning(
            sprintf(
                'Recovered recurring schedule ID %d stuck in "%s" for %d seconds by triggering "%s".',
                $idRecurringSchedule,
                $stuckItemTransfer->getStateNameOrFail(),
                $ageInSeconds,
                $event,
            ),
        );
        $recoveryResultTransfer->setRecoveredCount($recoveryResultTransfer->getRecoveredCountOrFail() + 1);

        return true;
    }

    protected function isLockHeldByAnotherProcess(LockException $lockException): bool
    {
        for ($exception = $lockException->getPrevious(); $exception !== null; $exception = $exception->getPrevious()) {
            if ($exception instanceof PDOException) {
                return str_starts_with((string)($exception->errorInfo[0] ?? ''), static::SQLSTATE_CLASS_INTEGRITY_CONSTRAINT_VIOLATION);
            }
        }

        return false;
    }

    protected function reportFailedAttempt(
        RecurringScheduleStuckItemTransfer $stuckItemTransfer,
        string $event,
        Exception $exception,
        RecurringScheduleRecoveryResultTransfer $recoveryResultTransfer,
    ): bool {
        $this->getLogger()->error(
            sprintf(
                'Recovering recurring schedule ID %d stuck in "%s" by triggering "%s" failed: %s',
                $stuckItemTransfer->getIdRecurringScheduleOrFail(),
                $stuckItemTransfer->getStateNameOrFail(),
                $event,
                $exception->getMessage(),
            ),
            ['exception' => $exception],
        );
        $recoveryResultTransfer->setFailedCount($recoveryResultTransfer->getFailedCountOrFail() + 1);

        return false;
    }

    protected function reportStuckItem(RecurringScheduleStuckItemTransfer $stuckItemTransfer, int $ageInSeconds): void
    {
        $this->entityManager->updateRecurringScheduleUpdatedAt($stuckItemTransfer->getIdRecurringScheduleOrFail());

        $this->getLogger()->error(
            sprintf(
                'Recurring schedule ID %d is stuck in "%s" for %d seconds and needs manual attention: it is not recovered automatically.',
                $stuckItemTransfer->getIdRecurringScheduleOrFail(),
                $stuckItemTransfer->getStateNameOrFail(),
                $ageInSeconds,
            ),
            ['isStateHistoryMissing' => $stuckItemTransfer->getIsStateHistoryMissing()],
        );
    }

    protected function abort(RecurringScheduleRecoveryResultTransfer $recoveryResultTransfer): RecurringScheduleRecoveryResultTransfer
    {
        $this->getLogger()->error(
            sprintf(
                'Stuck recurring schedule recovery aborted after %d consecutive failures. The next run starts over.',
                static::MAXIMUM_CONSECUTIVE_FAILURES,
            ),
        );

        return $recoveryResultTransfer->setIsAborted(true);
    }
}
