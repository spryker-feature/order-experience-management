<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Transition;

use ArrayObject;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\ItemStateTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\OrderItemTransitionOutcomeTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Transition\OrderItemTransitionOutcomeMapper;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Transition
 * @group OrderItemTransitionOutcomeMapperTest
 * Add your own group annotations below this line
 */
class OrderItemTransitionOutcomeMapperTest extends Unit
{
    protected const string UUID_ONE = '3fa85f64-5717-4562-b3fc-2c963f66afa6';

    protected const string UUID_TWO = '9c1e0b2a-4f3d-4a91-8c77-1d5b6e2f0a34';

    protected OrderItemTransitionOutcomeMapper $orderItemTransitionOutcomeMapper;

    protected function _before(): void
    {
        $this->orderItemTransitionOutcomeMapper = new OrderItemTransitionOutcomeMapper();
    }

    public function testCreateOutcomesReportsTransitionedWhenTheAdmittedStateChanged(): void
    {
        // Arrange
        $itemTransfer = $this->createItem(1, static::UUID_ONE, 'shipped');

        // Act
        $outcomeTransfers = $this->orderItemTransitionOutcomeMapper->createOutcomes(
            [$itemTransfer],
            [1 => 'exported'],
            [1 => 'exported'],
        );

        // Assert
        $outcomeTransfer = $this->getOutcome($outcomeTransfers, 0);
        $this->assertSame(OrderItemTransitionOutcomeMapper::OUTCOME_TRANSITIONED, $outcomeTransfer->getOutcome());
        $this->assertSame('exported', $outcomeTransfer->getStateBefore());
        $this->assertSame('shipped', $outcomeTransfer->getState());
    }

    public function testCreateOutcomesReportsUnchangedWhenTheAdmittedStateDidNotChange(): void
    {
        // Arrange
        $itemTransfer = $this->createItem(1, static::UUID_ONE, 'exported');

        // Act
        $outcomeTransfers = $this->orderItemTransitionOutcomeMapper->createOutcomes(
            [$itemTransfer],
            [1 => 'exported'],
            [1 => 'exported'],
        );

        // Assert
        $this->assertSame(OrderItemTransitionOutcomeMapper::OUTCOME_UNCHANGED, $this->getOutcome($outcomeTransfers, 0)->getOutcome());
    }

    /**
     * An item absent from the admitted map was never triggered — the omitted-list scope selector
     * case — and must be reported as skipped rather than transitioned or unchanged.
     */
    public function testCreateOutcomesReportsSkippedWhenTheItemWasNotAdmitted(): void
    {
        // Arrange
        $itemTransfer = $this->createItem(1, static::UUID_ONE, 'new');

        // Act
        $outcomeTransfers = $this->orderItemTransitionOutcomeMapper->createOutcomes(
            [$itemTransfer],
            [],
            [],
        );

        // Assert
        $this->assertSame(OrderItemTransitionOutcomeMapper::OUTCOME_SKIPPED, $this->getOutcome($outcomeTransfers, 0)->getOutcome());
    }

    /**
     * An item with no id cannot have been triggered — the trigger takes ids — so it is reported as
     * skipped rather than dropped, keeping the "every item in scope appears" guarantee intact.
     */
    public function testCreateOutcomesReportsSkippedForAnItemWithNoId(): void
    {
        // Arrange
        $itemTransfer = (new ItemTransfer())
            ->setUuid(static::UUID_ONE)
            ->setState((new ItemStateTransfer())->setName('new'));

        // Act
        $outcomeTransfers = $this->orderItemTransitionOutcomeMapper->createOutcomes([$itemTransfer], [], []);

        // Assert
        $outcomeTransfer = $this->getOutcome($outcomeTransfers, 0);
        $this->assertSame(OrderItemTransitionOutcomeMapper::OUTCOME_SKIPPED, $outcomeTransfer->getOutcome());
        $this->assertNull($outcomeTransfer->getIdSalesOrderItem());
    }

    public function testCreateOutcomesReportsOneOutcomePerItemInScope(): void
    {
        // Arrange
        $itemTransfers = [
            $this->createItem(1, static::UUID_ONE, 'exported'),
            $this->createItem(2, static::UUID_TWO, 'exported'),
        ];

        // Act
        $outcomeTransfers = $this->orderItemTransitionOutcomeMapper->createOutcomes($itemTransfers, [], []);

        // Assert
        $this->assertCount(2, $outcomeTransfers);
        $this->assertSame(static::UUID_ONE, $this->getOutcome($outcomeTransfers, 0)->getUuid());
        $this->assertSame(static::UUID_TWO, $this->getOutcome($outcomeTransfers, 1)->getUuid());
    }

    public function testExtractStateNamesByOrderItemIdKeysStateNamesByOrderItemId(): void
    {
        // Arrange
        $itemTransfers = [
            $this->createItem(1, static::UUID_ONE, 'exported'),
            $this->createItem(2, static::UUID_TWO, 'shipped'),
        ];

        // Act
        $stateNamesByOrderItemId = $this->orderItemTransitionOutcomeMapper->extractStateNamesByOrderItemId($itemTransfers);

        // Assert
        $this->assertSame(['exported', 'shipped'], array_values($stateNamesByOrderItemId));
        $this->assertSame([1, 2], array_keys($stateNamesByOrderItemId));
    }

    public function testExtractStateNamesByOrderItemIdOmitsAnItemWithNoIdOrNoState(): void
    {
        // Arrange
        $itemTransfers = [
            (new ItemTransfer())->setUuid(static::UUID_ONE)->setState((new ItemStateTransfer())->setName('exported')),
            (new ItemTransfer())->setIdSalesOrderItem(2)->setUuid(static::UUID_TWO),
        ];

        // Act
        $stateNamesByOrderItemId = $this->orderItemTransitionOutcomeMapper->extractStateNamesByOrderItemId($itemTransfers);

        // Assert
        $this->assertSame([], $stateNamesByOrderItemId);
    }

    public function testExtractUuidsReturnsTheUuidOfEveryItem(): void
    {
        // Arrange
        $itemTransfers = [
            $this->createItem(1, static::UUID_ONE, 'exported'),
            $this->createItem(2, static::UUID_TWO, 'exported'),
        ];

        // Act
        $uuids = $this->orderItemTransitionOutcomeMapper->extractUuids($itemTransfers);

        // Assert
        $this->assertSame([static::UUID_ONE, static::UUID_TWO], $uuids);
    }

    public function testExtractUuidsSkipsAnItemWithNoUuid(): void
    {
        // Arrange
        $itemTransfers = [(new ItemTransfer())->setIdSalesOrderItem(1)];

        // Act
        $uuids = $this->orderItemTransitionOutcomeMapper->extractUuids($itemTransfers);

        // Assert
        $this->assertSame([], $uuids);
    }

    /**
     * An omitted list (empty requested uuids) targets the whole order, so there is nothing to diff —
     * every returned item is, by definition, known.
     */
    public function testFindUnknownUuidsReturnsEmptyWhenTheRequestedListWasOmitted(): void
    {
        // Act
        $unknownUuids = $this->orderItemTransitionOutcomeMapper->findUnknownUuids([], []);

        // Assert
        $this->assertSame([], $unknownUuids);
    }

    public function testFindUnknownUuidsReturnsARequestedUuidThatTheItemQueryDidNotReturn(): void
    {
        // Arrange
        $itemTransfers = [$this->createItem(1, static::UUID_ONE, 'exported')];

        // Act
        $unknownUuids = $this->orderItemTransitionOutcomeMapper->findUnknownUuids([static::UUID_ONE, static::UUID_TWO], $itemTransfers);

        // Assert
        $this->assertSame([static::UUID_TWO], $unknownUuids);
    }

    public function testFindUnknownUuidsReturnsEmptyWhenEveryRequestedUuidWasFound(): void
    {
        // Arrange
        $itemTransfers = [$this->createItem(1, static::UUID_ONE, 'exported')];

        // Act
        $unknownUuids = $this->orderItemTransitionOutcomeMapper->findUnknownUuids([static::UUID_ONE], $itemTransfers);

        // Assert
        $this->assertSame([], $unknownUuids);
    }

    protected function createItem(int $idSalesOrderItem, string $uuid, string $stateName): ItemTransfer
    {
        return (new ItemTransfer())
            ->setIdSalesOrderItem($idSalesOrderItem)
            ->setUuid($uuid)
            ->setState((new ItemStateTransfer())->setName($stateName));
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\OrderItemTransitionOutcomeTransfer> $outcomeTransfers
     */
    protected function getOutcome(ArrayObject $outcomeTransfers, int $index): OrderItemTransitionOutcomeTransfer
    {
        $outcomeTransfer = $outcomeTransfers[$index] ?? null;
        $this->assertInstanceOf(OrderItemTransitionOutcomeTransfer::class, $outcomeTransfer);

        return $outcomeTransfer;
    }
}
