<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Transition;

use Codeception\Stub;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\ItemCollectionTransfer;
use Generated\Shared\Transfer\ItemStateTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Spryker\Zed\Oms\Business\OmsFacadeInterface;
use Spryker\Zed\Oms\Business\Process\EventInterface;
use Spryker\Zed\Oms\Business\Process\ProcessInterface;
use Spryker\Zed\Oms\Business\Process\StateInterface;
use Spryker\Zed\Oms\Business\Process\TransitionInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Transition\AvailableOrderItemTransitionReader;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Transition
 * @group AvailableOrderItemTransitionReaderTest
 * Add your own group annotations below this line
 */
class AvailableOrderItemTransitionReaderTest extends Unit
{
    protected const string PROCESS_NAME = 'Test01';

    /**
     * The reason this class exists. `OmsFacade::getOrderItemManualEvents()` looks like the right
     * primitive but is not: it derives from `Process::getManualEvents()`, which keeps an event when
     * `isManual() || isOnEnter()`. On-enter events are raised by OMS when a state is entered and no
     * client can fire one, so reporting one would advertise an action that does not exist for the
     * caller — and admitting one on the write side would accept a request OMS then discards.
     */
    public function testGivenStateWithManualAndOnEnterEventsWhenGetAvailableTransitionsThenOnlyManualEventsAreReturned(): void
    {
        // Arrange
        $reader = $this->createReader([
            static::PROCESS_NAME => [
                'paid' => [
                    'ship' => true,
                    'authorize-payment' => false,
                ],
            ],
        ]);

        // Act
        $availableTransitions = $reader->getAvailableTransitions($this->createItemTransfer(1, 'paid'));

        // Assert
        $this->assertSame(['ship'], $availableTransitions);
    }

    public function testGivenStateWithOnlyOnEnterEventsWhenGetAvailableTransitionsThenNoTransitionsAreReturned(): void
    {
        // Arrange
        $reader = $this->createReader([
            static::PROCESS_NAME => ['paid' => ['authorize-payment' => false]],
        ]);

        // Act
        $availableTransitions = $reader->getAvailableTransitions($this->createItemTransfer(1, 'paid'));

        // Assert
        $this->assertSame([], $availableTransitions);
    }

    /**
     * An event is legal only from the states it transitions out of, so the answer must depend on the
     * item's CURRENT state — a whitelist keyed by process alone could not express this.
     */
    public function testGivenEventManualFromAnotherStateWhenGetAvailableTransitionsThenItIsNotReturned(): void
    {
        // Arrange
        $reader = $this->createReader([
            static::PROCESS_NAME => [
                'paid' => ['ship' => true],
                'shipped' => ['refund' => true],
            ],
        ]);

        // Act
        $availableTransitions = $reader->getAvailableTransitions($this->createItemTransfer(1, 'shipped'));

        // Assert
        $this->assertSame(['refund'], $availableTransitions);
    }

    public function testGivenTerminalStateWhenGetAvailableTransitionsThenNoTransitionsAreReturned(): void
    {
        // Arrange
        $reader = $this->createReader([static::PROCESS_NAME => ['paid' => ['ship' => true]]]);

        // Act
        $availableTransitions = $reader->getAvailableTransitions($this->createItemTransfer(1, 'closed'));

        // Assert
        $this->assertSame([], $availableTransitions);
    }

    /**
     * The same event may be reachable from one state through several transitions. It must be reported
     * once, or a caller reading the field sees a duplicate.
     */
    public function testGivenEventOnSeveralTransitionsFromOneStateWhenGetAvailableTransitionsThenItIsReportedOnce(): void
    {
        // Arrange
        $reader = new AvailableOrderItemTransitionReader(
            Stub::makeEmpty(OmsFacadeInterface::class, [
                'getProcesses' => [
                    static::PROCESS_NAME => Stub::makeEmpty(ProcessInterface::class, [
                        'getAllTransitions' => [
                            $this->createTransitionStub('paid', 'ship', true),
                            $this->createTransitionStub('paid', 'ship', true),
                        ],
                    ]),
                ],
            ]),
        );

        // Act
        $availableTransitions = $reader->getAvailableTransitions($this->createItemTransfer(1, 'paid'));

        // Assert
        $this->assertSame(['ship'], $availableTransitions);
    }

    /**
     * Callers index the result by item id, so every item passed in must be present — otherwise a
     * missing key is indistinguishable from "no transitions" at the call site.
     */
    public function testGivenItemsInDifferentStatesWhenGetAvailableTransitionsByOrderItemIdThenEachItemIsKeyedWithItsOwnSet(): void
    {
        // Arrange
        $reader = $this->createReader([
            static::PROCESS_NAME => [
                'paid' => ['ship' => true, 'authorize-payment' => false],
                'shipped' => ['refund' => true],
            ],
        ]);

        $itemCollectionTransfer = (new ItemCollectionTransfer())
            ->addItem($this->createItemTransfer(1, 'paid'))
            ->addItem($this->createItemTransfer(2, 'shipped'))
            ->addItem($this->createItemTransfer(3, 'closed'));

        // Act
        $availableTransitionsByOrderItemId = $reader->getAvailableTransitionsByOrderItemId($itemCollectionTransfer);

        // Assert
        $this->assertSame([1 => ['ship'], 2 => ['refund'], 3 => []], $availableTransitionsByOrderItemId);
    }

    public function testGivenItemWithoutStateWhenGetAvailableTransitionsThenNoTransitionsAreReturned(): void
    {
        // Arrange
        $reader = $this->createReader([static::PROCESS_NAME => ['paid' => ['ship' => true]]]);

        // Act
        $availableTransitions = $reader->getAvailableTransitions(
            (new ItemTransfer())->setIdSalesOrderItem(1)->setProcess(static::PROCESS_NAME),
        );

        // Assert
        $this->assertSame([], $availableTransitions);
    }

    /**
     * An item whose process is not among the active ones resolves to an empty set rather than
     * throwing — a stale process name must not take down a whole order read.
     */
    public function testGivenItemWithUnknownProcessWhenGetAvailableTransitionsThenNoTransitionsAreReturned(): void
    {
        // Arrange
        $reader = $this->createReader([static::PROCESS_NAME => ['paid' => ['ship' => true]]]);

        // Act
        $availableTransitions = $reader->getAvailableTransitions(
            (new ItemTransfer())
                ->setIdSalesOrderItem(1)
                ->setProcess('DecommissionedProcess')
                ->setState((new ItemStateTransfer())->setName('paid')),
        );

        // Assert
        $this->assertSame([], $availableTransitions);
    }

    protected function createItemTransfer(int $idSalesOrderItem, string $stateName): ItemTransfer
    {
        return (new ItemTransfer())
            ->setIdSalesOrderItem($idSalesOrderItem)
            ->setProcess(static::PROCESS_NAME)
            ->setState((new ItemStateTransfer())->setName($stateName));
    }

    /**
     * `$eventSpec` is `processName => stateName => [eventName => isManual]`, so a test declares the
     * manual/on-enter distinction the production code branches on rather than assuming it.
     *
     * @param array<string, array<string, array<string, bool>>> $eventSpec
     */
    protected function createReader(array $eventSpec): AvailableOrderItemTransitionReader
    {
        $processes = [];

        foreach ($eventSpec as $processName => $eventsByStateName) {
            $transitions = [];

            foreach ($eventsByStateName as $stateName => $isManualByEventName) {
                foreach ($isManualByEventName as $eventName => $isManual) {
                    $transitions[] = $this->createTransitionStub($stateName, (string)$eventName, $isManual);
                }
            }

            $processes[$processName] = Stub::makeEmpty(ProcessInterface::class, ['getAllTransitions' => $transitions]);
        }

        return new AvailableOrderItemTransitionReader(
            Stub::makeEmpty(OmsFacadeInterface::class, ['getProcesses' => $processes]),
        );
    }

    /**
     * Stubbed rather than built from real `Process`/`Transition` objects: `Process::__construct()`
     * requires a `DrawerInterface` that has nothing to do with event resolution.
     */
    protected function createTransitionStub(string $stateName, string $eventName, bool $isManual): TransitionInterface
    {
        return Stub::makeEmpty(TransitionInterface::class, [
            'hasEvent' => true,
            'getEvent' => Stub::makeEmpty(EventInterface::class, [
                'isManual' => $isManual,
                'getName' => $eventName,
            ]),
            'getSource' => Stub::makeEmpty(StateInterface::class, ['getName' => $stateName]),
        ]);
    }
}
