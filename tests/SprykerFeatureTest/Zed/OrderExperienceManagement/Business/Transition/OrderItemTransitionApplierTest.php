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
use Generated\Shared\Transfer\MessageTransfer;
use Generated\Shared\Transfer\OmsEventTriggerResponseTransfer;
use Generated\Shared\Transfer\OrderCollectionTransfer;
use Generated\Shared\Transfer\OrderItemTransitionRequestTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use Spryker\Zed\Oms\Business\Exception\LockException;
use Spryker\Zed\Oms\Business\OmsFacadeInterface;
use Spryker\Zed\Oms\OmsConfig;
use Spryker\Zed\Sales\Business\SalesFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Transition\AvailableOrderItemTransitionReaderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Transition\OrderItemTransitionApplier;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Transition\OrderItemTransitionOutcomeMapper;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Transition
 * @group OrderItemTransitionApplierTest
 * Add your own group annotations below this line
 */
class OrderItemTransitionApplierTest extends Unit
{
    protected const string ORDER_REFERENCE = 'DE--1234';

    protected const string EVENT = 'ship';

    protected const string UUID_ONE = '3fa85f64-5717-4562-b3fc-2c963f66afa6';

    protected const string UUID_TWO = '9c1e0b2a-4f3d-4a91-8c77-1d5b6e2f0a34';

    protected const string UUID_FOREIGN = 'ffffffff-ffff-ffff-ffff-ffffffffffff';

    public function testGivenEligibleItemsWhenApplyTransitionThenEveryItemIsReportedAsTransitioned(): void
    {
        // Arrange
        $applier = $this->createApplier(
            [$this->createItem(1, static::UUID_ONE, 'exported'), $this->createItem(2, static::UUID_TWO, 'exported')],
            [$this->createItem(1, static::UUID_ONE, 'shipped'), $this->createItem(2, static::UUID_TWO, 'shipped')],
            [static::EVENT],
        );

        // Act
        $responseTransfer = $applier->applyTransition($this->createRequest([static::UUID_ONE, static::UUID_TWO]));

        // Assert
        $this->assertTrue($responseTransfer->getIsSuccessful());
        $this->assertSame(OrderItemTransitionApplier::RESULT_APPLIED, $responseTransfer->getResult());
        $this->assertCount(2, $responseTransfer->getItemOutcomes());

        foreach ($responseTransfer->getItemOutcomes() as $outcomeTransfer) {
            $this->assertSame(OrderItemTransitionOutcomeMapper::OUTCOME_TRANSITIONED, $outcomeTransfer->getOutcome());
            $this->assertSame('exported', $outcomeTransfer->getStateBefore());
            $this->assertSame('shipped', $outcomeTransfer->getState());
        }
    }

    /**
     * The single most important test here. `triggerEventForOrderItems()` returns NULL on an internal
     * failure, and the shipped Back Office consumer reports "Status change triggered successfully" for
     * exactly this case, because it reads `$data[$key] ?? null` and then fails an `instanceof` guard.
     * A NULL must never become a success.
     */
    public function testGivenOmsReturnsNullWhenApplyTransitionThenInternalFailureIsReported(): void
    {
        // Arrange
        $applier = $this->createApplier(
            [$this->createItem(1, static::UUID_ONE, 'exported')],
            [$this->createItem(1, static::UUID_ONE, 'exported')],
            [static::EVENT],
            null,
        );

        // Act
        $responseTransfer = $applier->applyTransition($this->createRequest([static::UUID_ONE]));

        // Assert
        $this->assertFalse($responseTransfer->getIsSuccessful());
        $this->assertSame(OrderItemTransitionApplier::RESULT_INTERNAL_FAILURE, $responseTransfer->getResult());
        $this->assertCount(1, $responseTransfer->getErrors());
    }

    /**
     * `isSuccessful=false` with nothing advanced must not be a 200. The messages OMS reported are the
     * only explanation the caller gets, so they have to survive onto the response.
     */
    public function testGivenOmsReportsFailureAndNoItemAdvancedWhenApplyTransitionThenNotAppliedIsReportedWithMessages(): void
    {
        // Arrange
        $applier = $this->createApplier(
            [$this->createItem(1, static::UUID_ONE, 'exported')],
            [$this->createItem(1, static::UUID_ONE, 'exported')],
            [static::EVENT],
            [
            OmsConfig::OMS_EVENT_TRIGGER_RESPONSE => (new OmsEventTriggerResponseTransfer())
                ->setIsSuccessful(false)
                ->addMessage((new MessageTransfer())->setValue('Carrier rejected the shipment.'))],
        );

        // Act
        $responseTransfer = $applier->applyTransition($this->createRequest([static::UUID_ONE]));

        // Assert
        $this->assertFalse($responseTransfer->getIsSuccessful());
        $this->assertSame(OrderItemTransitionApplier::RESULT_NOT_APPLIED, $responseTransfer->getResult());
        $this->assertCount(1, $responseTransfer->getMessages());
        $this->assertSame('Carrier rejected the shipment.', $responseTransfer->getMessages()[0]->getValue());
    }

    /**
     * OMS aggregates `isSuccessful` over the whole call, so it can be false while an item still
     * advanced. The observed per-item state is the verdict, not the flag — but the messages still have
     * to reach the caller.
     */
    public function testGivenOmsReportsFailureButAnItemAdvancedWhenApplyTransitionThenPartiallyAppliedIsReportedWithMessages(): void
    {
        // Arrange
        $applier = $this->createApplier(
            [$this->createItem(1, static::UUID_ONE, 'exported'), $this->createItem(2, static::UUID_TWO, 'exported')],
            [$this->createItem(1, static::UUID_ONE, 'shipped'), $this->createItem(2, static::UUID_TWO, 'exported')],
            [static::EVENT],
            [
            OmsConfig::OMS_EVENT_TRIGGER_RESPONSE => (new OmsEventTriggerResponseTransfer())
                ->setIsSuccessful(false)
                ->addMessage((new MessageTransfer())->setValue('One line could not be shipped.'))],
        );

        // Act
        $responseTransfer = $applier->applyTransition($this->createRequest([static::UUID_ONE, static::UUID_TWO]));

        // Assert
        $this->assertTrue($responseTransfer->getIsSuccessful());
        $this->assertSame(OrderItemTransitionApplier::RESULT_PARTIALLY_APPLIED, $responseTransfer->getResult());
        $this->assertCount(1, $responseTransfer->getMessages());

        $outcomes = $responseTransfer->getItemOutcomes();
        $this->assertSame(OrderItemTransitionOutcomeMapper::OUTCOME_TRANSITIONED, $outcomes[0]->getOutcome());
        $this->assertSame(OrderItemTransitionOutcomeMapper::OUTCOME_UNCHANGED, $outcomes[1]->getOutcome());
    }

    /**
     * A warning on a SUCCESSFUL trigger must not be dropped just because the transition worked.
     */
    public function testGivenOmsReportsSuccessWithMessagesWhenApplyTransitionThenMessagesAreStillReported(): void
    {
        // Arrange
        $applier = $this->createApplier(
            [$this->createItem(1, static::UUID_ONE, 'exported')],
            [$this->createItem(1, static::UUID_ONE, 'shipped')],
            [static::EVENT],
            [
            OmsConfig::OMS_EVENT_TRIGGER_RESPONSE => (new OmsEventTriggerResponseTransfer())
                ->setIsSuccessful(true)
                ->addMessage((new MessageTransfer())->setValue('Tracking number pending.'))],
        );

        // Act
        $responseTransfer = $applier->applyTransition($this->createRequest([static::UUID_ONE]));

        // Assert
        $this->assertSame(OrderItemTransitionApplier::RESULT_APPLIED, $responseTransfer->getResult());
        $this->assertCount(1, $responseTransfer->getMessages());
    }

    /**
     * `LockedOrderStateMachine` does not catch, so `LockException` propagates out of the facade. Left
     * unhandled it becomes a 500 with a stack trace for an ordinary, retryable race.
     */
    public function testGivenStateMachineIsLockedWhenApplyTransitionThenLockedIsReported(): void
    {
        // Arrange
        $applier = $this->createApplier(
            [$this->createItem(1, static::UUID_ONE, 'exported')],
            [$this->createItem(1, static::UUID_ONE, 'exported')],
            [static::EVENT],
            null,
            true,
        );

        // Act
        $responseTransfer = $applier->applyTransition($this->createRequest([static::UUID_ONE]));

        // Assert
        $this->assertFalse($responseTransfer->getIsSuccessful());
        $this->assertSame(OrderItemTransitionApplier::RESULT_LOCKED, $responseTransfer->getResult());
    }

    /**
     * Tenancy. The items query is scoped by order reference AND uuid, so an item of another order
     * simply does not come back — and the request must be rejected whole, with nothing fired.
     */
    public function testGivenUuidOfAnotherOrderWhenApplyTransitionThenUnknownItemsIsReportedAndNothingIsTriggered(): void
    {
        // Arrange
        $wasTriggered = false;

        $applier = $this->createApplier(
            [$this->createItem(1, static::UUID_ONE, 'exported')],
            [$this->createItem(1, static::UUID_ONE, 'exported')],
            [static::EVENT],
            [],
            false,
            $wasTriggered,
        );

        // Act
        $responseTransfer = $applier->applyTransition($this->createRequest([static::UUID_ONE, static::UUID_FOREIGN]));

        // Assert
        $this->assertSame(OrderItemTransitionApplier::RESULT_UNKNOWN_ITEMS, $responseTransfer->getResult());
        $this->assertSame([static::UUID_FOREIGN], $responseTransfer->getUnknownItemUuids());
        $this->assertFalse($wasTriggered, 'A rejected request must not reach the state machine.');
    }

    /**
     * D6, half one: an explicit list is an ASSERTION. One ineligible item rejects the whole request
     * and fires nothing, so the caller never has to reconcile a partial write it did not ask for.
     */
    public function testGivenExplicitListWithAnIneligibleItemWhenApplyTransitionThenIneligibleIsReportedAndNothingIsTriggered(): void
    {
        // Arrange
        $wasTriggered = false;

        $applier = $this->createApplier(
            [$this->createItem(1, static::UUID_ONE, 'exported'), $this->createItem(2, static::UUID_TWO, 'new')],
            [$this->createItem(1, static::UUID_ONE, 'exported'), $this->createItem(2, static::UUID_TWO, 'new')],
            [static::EVENT],
            [],
            false,
            $wasTriggered,
        );

        // Act
        $responseTransfer = $applier->applyTransition($this->createRequest([static::UUID_ONE, static::UUID_TWO]));

        // Assert
        $this->assertSame(OrderItemTransitionApplier::RESULT_INELIGIBLE, $responseTransfer->getResult());
        $this->assertFalse($wasTriggered, 'An assertion-style request must fire nothing when any item is ineligible.');
        $this->assertCount(2, $responseTransfer->getItemOutcomes(), 'Both items are still reported, with their states.');
    }

    /**
     * D6, half two: an omitted list is a SCOPE SELECTOR. The eligible subset is triggered and the rest
     * come back as `skipped` — `ship` on a mixed-state order is the ordinary case.
     */
    public function testGivenOmittedListOnMixedStateOrderWhenApplyTransitionThenEligibleItemsAdvanceAndTheRestAreSkipped(): void
    {
        // Arrange
        $applier = $this->createApplier(
            [$this->createItem(1, static::UUID_ONE, 'exported'), $this->createItem(2, static::UUID_TWO, 'new')],
            [$this->createItem(1, static::UUID_ONE, 'shipped'), $this->createItem(2, static::UUID_TWO, 'new')],
            [static::EVENT],
        );

        // Act
        $responseTransfer = $applier->applyTransition($this->createRequest([]));

        // Assert
        $this->assertTrue($responseTransfer->getIsSuccessful());
        $this->assertSame(OrderItemTransitionApplier::RESULT_PARTIALLY_APPLIED, $responseTransfer->getResult());

        $outcomes = $responseTransfer->getItemOutcomes();
        $this->assertSame(OrderItemTransitionOutcomeMapper::OUTCOME_TRANSITIONED, $outcomes[0]->getOutcome());
        $this->assertSame(OrderItemTransitionOutcomeMapper::OUTCOME_SKIPPED, $outcomes[1]->getOutcome());
    }

    /**
     * The omitted form must never degrade into a silent 200 no-op.
     */
    public function testGivenOmittedListAndNoEligibleItemWhenApplyTransitionThenNoEligibleItemsIsReported(): void
    {
        // Arrange
        $applier = $this->createApplier(
            [$this->createItem(1, static::UUID_ONE, 'new')],
            [$this->createItem(1, static::UUID_ONE, 'new')],
            [static::EVENT],
        );

        // Act
        $responseTransfer = $applier->applyTransition($this->createRequest([]));

        // Assert
        $this->assertFalse($responseTransfer->getIsSuccessful());
        $this->assertSame(OrderItemTransitionApplier::RESULT_NO_ELIGIBLE_ITEMS, $responseTransfer->getResult());
    }

    public function testGivenUnknownOrderReferenceWhenApplyTransitionThenOrderNotFoundIsReported(): void
    {
        // Arrange
        $applier = $this->createApplier([], [], [static::EVENT], [], false, $unused, false);

        // Act
        $responseTransfer = $applier->applyTransition($this->createRequest([static::UUID_ONE]));

        // Assert
        $this->assertSame(OrderItemTransitionApplier::RESULT_ORDER_NOT_FOUND, $responseTransfer->getResult());
    }

    /**
     * The facade is public API, so it validates its own input rather than trusting the HTTP layer's
     * constraints — Back Office automation and EDI adapters reach it without those.
     */
    public function testGivenBlankEventWhenApplyTransitionThenInvalidRequestIsReported(): void
    {
        // Arrange
        $applier = $this->createApplier([], [], []);

        // Act
        $responseTransfer = $applier->applyTransition(
            (new OrderItemTransitionRequestTransfer())->setOrderReference(static::ORDER_REFERENCE)->setEvent(''),
        );

        // Assert
        $this->assertSame(OrderItemTransitionApplier::RESULT_INVALID_REQUEST, $responseTransfer->getResult());
    }

    /**
     * @param array<int, string> $itemUuids
     */
    protected function createRequest(array $itemUuids): OrderItemTransitionRequestTransfer
    {
        return (new OrderItemTransitionRequestTransfer())
            ->setOrderReference(static::ORDER_REFERENCE)
            ->setEvent(static::EVENT)
            ->setItemUuids($itemUuids);
    }

    protected function createItem(int $idSalesOrderItem, string $uuid, string $stateName): ItemTransfer
    {
        return (new ItemTransfer())
            ->setIdSalesOrderItem($idSalesOrderItem)
            ->setUuid($uuid)
            ->setProcess('Test01')
            ->setState((new ItemStateTransfer())->setName($stateName));
    }

    /**
     * `$itemsBefore` answers the admission reads and `$itemsAfter` the phase-5 re-read, so a test can
     * express "the state actually changed" — which is the only way an outcome of `transitioned` can be
     * distinguished from `unchanged`.
     *
     * `$availableTransitionsFor` is what the shared reader reports for an `exported` item; every other
     * state resolves to an empty set, which is what makes a `new` item ineligible.
     *
     * @param array<int, \Generated\Shared\Transfer\ItemTransfer> $itemsBefore
     * @param array<int, \Generated\Shared\Transfer\ItemTransfer> $itemsAfter
     * @param array<int, string> $availableTransitionsFor
     * @param array<mixed>|null $triggerReturnData
     */
    protected function createApplier(
        array $itemsBefore,
        array $itemsAfter,
        array $availableTransitionsFor,
        ?array $triggerReturnData = [],
        bool $throwsLockException = false,
        ?bool &$wasTriggered = null,
        bool $hasOrder = true
    ): OrderItemTransitionApplier {
        $readCount = 0;

        $salesFacadeStub = Stub::makeEmpty(SalesFacadeInterface::class, [
            'getOrderCollection' => function () use ($hasOrder): OrderCollectionTransfer {
                $orderCollectionTransfer = new OrderCollectionTransfer();

                return $hasOrder
                    ? $orderCollectionTransfer->addOrder((new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE))
                    : $orderCollectionTransfer;
            },
            'getOrderItems' => function () use (&$readCount, $itemsBefore, $itemsAfter): ItemCollectionTransfer {
                $readCount++;
                $itemTransfers = $readCount === 1 ? $itemsBefore : $itemsAfter;

                $itemCollectionTransfer = new ItemCollectionTransfer();

                foreach ($itemTransfers as $itemTransfer) {
                    $itemCollectionTransfer->addItem($itemTransfer);
                }

                return $itemCollectionTransfer;
            },
        ]);

        $omsFacadeStub = Stub::makeEmpty(OmsFacadeInterface::class, [
            'triggerEventForOrderItems' => function () use ($triggerReturnData, $throwsLockException, &$wasTriggered) {
                $wasTriggered = true;

                if ($throwsLockException) {
                    throw new LockException('locked');
                }

                return $triggerReturnData;
            },
        ]);

        $readerStub = Stub::makeEmpty(AvailableOrderItemTransitionReaderInterface::class, [
            'getAvailableTransitions' => fn (ItemTransfer $itemTransfer): array => $itemTransfer->getState()?->getName() === 'exported'
                ? $availableTransitionsFor
                : [],
        ]);

        return new OrderItemTransitionApplier($salesFacadeStub, $omsFacadeStub, $readerStub, new OrderItemTransitionOutcomeMapper());
    }
}
