<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Communication\Console;

use Codeception\Test\Unit;
use RuntimeException;
use Spryker\Zed\Kernel\Communication\Console\Console;
use Spryker\Zed\StateMachine\Business\StateMachineFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Recovery\StuckScheduleRecoverer;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Console\RecurringOrderRecoverStuckConsole;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementDependencyProvider;
use SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Communication
 * @group Console
 * @group RecurringOrderRecoverStuckConsoleTest
 */
class RecurringOrderRecoverStuckConsoleTest extends Unit
{
    protected const string STATE_NOTIFYING = 'notifying';

    protected const int STUCK_FOR_MINUTES = 30;

    protected OrderExperienceManagementBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->ensureRecurringScheduleTablesAreEmpty();
    }

    public function testExitsWithSuccessWhenEveryAttemptSucceeds(): void
    {
        // Arrange
        $this->haveStuckSchedule();
        $this->mockStateMachineFacade()->method('triggerEvent')->willReturn(1);
        $commandTester = $this->createCommandTester();

        // Act
        $exitCode = $commandTester->execute([]);

        // Assert
        $this->assertSame(Console::CODE_SUCCESS, $exitCode);
        $this->assertStringContainsString('Recovered: 1', $commandTester->getDisplay());
    }

    public function testExitsWithSuccessWhenNothingIsStuck(): void
    {
        // Arrange
        $this->mockStateMachineFacade()->expects($this->never())->method('triggerEvent');

        // Act
        $exitCode = $this->createCommandTester()->execute([]);

        // Assert
        $this->assertSame(Console::CODE_SUCCESS, $exitCode);
    }

    public function testExitsWithErrorWhenAnAttemptFailsWithoutAborting(): void
    {
        // Arrange
        $this->haveStuckSchedule();
        $this->mockStateMachineFacade()->method('triggerEvent')->willThrowException(new RuntimeException('database is restarting'));

        // Act
        $exitCode = $this->createCommandTester()->execute([]);

        // Assert
        $this->assertSame(Console::CODE_ERROR, $exitCode);
    }

    public function testExitsWithErrorWhenTheRunIsAborted(): void
    {
        // Arrange
        $maximumConsecutiveFailures = StuckScheduleRecoverer::MAXIMUM_CONSECUTIVE_FAILURES;

        for ($i = 0; $i < $maximumConsecutiveFailures; $i++) {
            $this->haveStuckSchedule();
        }

        $this->mockStateMachineFacade()->method('triggerEvent')->willThrowException(new RuntimeException('database is restarting'));
        $commandTester = $this->createCommandTester();

        // Act
        $exitCode = $commandTester->execute([]);

        // Assert
        $this->assertSame(Console::CODE_ERROR, $exitCode);
        $this->assertStringContainsString(sprintf('failed: %d', $maximumConsecutiveFailures), $commandTester->getDisplay());
    }

    protected function haveStuckSchedule(): void
    {
        $this->tester->haveStuckRecurringSchedule((int)$this->tester->haveCustomer()->getIdCustomer(), static::STATE_NOTIFYING, static::STUCK_FOR_MINUTES);
    }

    protected function mockStateMachineFacade(): StateMachineFacadeInterface
    {
        $stateMachineFacadeMock = $this->createMock(StateMachineFacadeInterface::class);
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_STATE_MACHINE, $stateMachineFacadeMock);

        return $stateMachineFacadeMock;
    }

    protected function createCommandTester(): CommandTester
    {
        $console = new RecurringOrderRecoverStuckConsole();
        $console->setBusinessFactory($this->tester->getFactory());

        return new CommandTester($console);
    }
}
