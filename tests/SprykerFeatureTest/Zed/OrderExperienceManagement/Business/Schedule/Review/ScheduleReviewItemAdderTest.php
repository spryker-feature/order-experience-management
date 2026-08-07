<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Schedule\Review;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Mapper\RecurringScheduleItemMapperInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\ScheduleReviewItemAdder;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Plan\ScheduleReviewItemUpdatePlanMergerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\ScheduleReviewItemRemoverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\ScheduleReviewPriceApplierInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\ScheduleReviewQuantityApplierInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Scope\OccurrenceScheduleReviewScopeStrategy;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Scope\StandingScheduleReviewScopeStrategy;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementEntityManagerInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Schedule
 * @group Review
 * @group ScheduleReviewItemAdderTest
 * Add your own group annotations below this line
 */
class ScheduleReviewItemAdderTest extends Unit
{
    protected const int ID_RECURRING_SCHEDULE = 7;

    protected const string SKU = 'sku-added';

    protected const int QUANTITY = 3;

    protected const string SKU_SECOND = 'sku-added-2';

    protected const int QUANTITY_SECOND = 5;

    /**
     * @var array<\Generated\Shared\Transfer\RecurringScheduleItemTransfer>|null
     */
    protected ?array $persistedRecurringScheduleItemTransfers = null;

    public function testStandingAddPersistsFullQuantityWithoutNextDeliveryQuantity(): void
    {
        // Arrange
        $adder = $this->createAdder();

        // Act
        $adder->addItems($this->createResolvedItemsByIndex(), $this->createSchedule(), $this->createStandingScopeStrategy());

        // Assert
        $recurringScheduleItemTransfer = $this->getSinglePersistedRecurringScheduleItemTransfer();

        $this->assertSame(static::QUANTITY, $recurringScheduleItemTransfer->getQuantity());
        $this->assertNull($recurringScheduleItemTransfer->getNextDeliveryQuantity());
    }

    public function testOccurrenceAddPersistsZeroBaseQuantityAndNextDeliveryQuantity(): void
    {
        // Arrange
        $adder = $this->createAdder();

        // Act
        $adder->addItems($this->createResolvedItemsByIndex(), $this->createSchedule(), $this->createOccurrenceScopeStrategy());

        // Assert
        $recurringScheduleItemTransfer = $this->getSinglePersistedRecurringScheduleItemTransfer();

        $this->assertSame(0, $recurringScheduleItemTransfer->getQuantity());
        $this->assertSame(static::QUANTITY, $recurringScheduleItemTransfer->getNextDeliveryQuantity());
    }

    public function testAddItemsPersistsAllResolvedItemsInSingleCall(): void
    {
        // Arrange
        $adder = $this->createAdder();
        $itemTransfersByAdditionIndex = [
            [(new ItemTransfer())->setSku(static::SKU)->setQuantity(static::QUANTITY)],
            [(new ItemTransfer())->setSku(static::SKU_SECOND)->setQuantity(static::QUANTITY_SECOND)],
        ];

        // Act
        $adder->addItems($itemTransfersByAdditionIndex, $this->createSchedule(), $this->createStandingScopeStrategy());

        // Assert
        $this->assertNotNull($this->persistedRecurringScheduleItemTransfers);
        $this->assertCount(2, $this->persistedRecurringScheduleItemTransfers);
        $this->assertSame(static::SKU, $this->persistedRecurringScheduleItemTransfers[0]->getSku());
        $this->assertSame(static::QUANTITY, $this->persistedRecurringScheduleItemTransfers[0]->getQuantity());
        $this->assertSame(static::SKU_SECOND, $this->persistedRecurringScheduleItemTransfers[1]->getSku());
        $this->assertSame(static::QUANTITY_SECOND, $this->persistedRecurringScheduleItemTransfers[1]->getQuantity());
    }

    public function testAddItemsPersistsNothingWhenThereAreNoResolvedItems(): void
    {
        // Arrange
        $adder = $this->createAdder($this->never());

        // Act
        $adder->addItems([], $this->createSchedule(), $this->createStandingScopeStrategy());

        // Assert
        $this->assertNull($this->persistedRecurringScheduleItemTransfers);
    }

    protected function getSinglePersistedRecurringScheduleItemTransfer(): RecurringScheduleItemTransfer
    {
        $this->assertNotNull($this->persistedRecurringScheduleItemTransfers);
        $this->assertCount(1, $this->persistedRecurringScheduleItemTransfers);

        return $this->persistedRecurringScheduleItemTransfers[0];
    }

    protected function createStandingScopeStrategy(): StandingScheduleReviewScopeStrategy
    {
        return new StandingScheduleReviewScopeStrategy(
            $this->createMock(ScheduleReviewItemRemoverInterface::class),
            $this->createMock(ScheduleReviewPriceApplierInterface::class),
            $this->createMock(ScheduleReviewQuantityApplierInterface::class),
            $this->createMock(ScheduleReviewItemUpdatePlanMergerInterface::class),
            $this->createMock(OrderExperienceManagementEntityManagerInterface::class),
        );
    }

    protected function createOccurrenceScopeStrategy(): OccurrenceScheduleReviewScopeStrategy
    {
        return new OccurrenceScheduleReviewScopeStrategy(
            $this->createMock(ScheduleReviewItemRemoverInterface::class),
            $this->createMock(ScheduleReviewPriceApplierInterface::class),
            $this->createMock(ScheduleReviewQuantityApplierInterface::class),
            $this->createMock(ScheduleReviewItemUpdatePlanMergerInterface::class),
            $this->createMock(OrderExperienceManagementEntityManagerInterface::class),
        );
    }

    protected function createAdder(?object $createInvocationRule = null): ScheduleReviewItemAdder
    {
        $recurringScheduleItemMapperMock = $this->createMock(RecurringScheduleItemMapperInterface::class);
        $recurringScheduleItemMapperMock->method('mapItemToRecurringScheduleItem')->willReturnCallback(
            static fn (ItemTransfer $itemTransfer): RecurringScheduleItemTransfer => (new RecurringScheduleItemTransfer())
                ->setSku($itemTransfer->getSkuOrFail())
                ->setQuantity($itemTransfer->getQuantityOrFail()),
        );

        $entityManagerMock = $this->createMock(OrderExperienceManagementEntityManagerInterface::class);
        $entityManagerMock->expects($createInvocationRule ?? $this->once())
            ->method('createRecurringScheduleItemCollection')
            ->willReturnCallback(function (array $recurringScheduleItemTransfers): void {
                $this->persistedRecurringScheduleItemTransfers = $recurringScheduleItemTransfers;
            });

        return new ScheduleReviewItemAdder($recurringScheduleItemMapperMock, $entityManagerMock);
    }

    /**
     * @return array<int, array<\Generated\Shared\Transfer\ItemTransfer>>
     */
    protected function createResolvedItemsByIndex(): array
    {
        return [
            [(new ItemTransfer())->setSku(static::SKU)->setQuantity(static::QUANTITY)],
        ];
    }

    protected function createSchedule(): RecurringScheduleTransfer
    {
        return (new RecurringScheduleTransfer())
            ->setIdRecurringSchedule(static::ID_RECURRING_SCHEDULE)
            ->setQuoteData('{}');
    }
}
