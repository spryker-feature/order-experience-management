<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Schedule\Review;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\AcceptedItemReviewMapper;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\ScheduleReviewItemAdderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\ScheduleReviewChangeApplier;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Scope\ScheduleReviewScopeStrategyInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Scope\ScheduleReviewScopeStrategyResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Schedule
 * @group Review
 * @group ScheduleReviewChangeApplierTest
 * Add your own group annotations below this line
 */
class ScheduleReviewChangeApplierTest extends Unit
{
    protected const int ID_RECURRING_SCHEDULE = 42;

    protected const int ACCEPTED_ITEM_COUNT = 25;

    protected const string SCOPE_STANDING = 'STANDING';

    protected const string GROUP_KEY_TEMPLATE = 'group-key-%d';

    protected const int ACCEPTED_PRICE = 500;

    protected const int ACCEPTED_QUANTITY = 2;

    public function testAppliesAllAcceptedItemsInSingleStrategyCall(): void
    {
        // Arrange
        $scopeStrategyMock = $this->createMock(ScheduleReviewScopeStrategyInterface::class);
        $scopeStrategyMock->expects($this->once())->method('applyAcceptedItems');

        $repositoryMock = $this->createMock(OrderExperienceManagementRepositoryInterface::class);
        $repositoryMock->expects($this->once())
            ->method('getRecurringScheduleItemGroupKeysByScheduleId')
            ->with(static::ID_RECURRING_SCHEDULE)
            ->willReturn($this->createGroupKeyIndex());

        // Act
        $this->createChangeApplier($scopeStrategyMock, $repositoryMock)->applyApprovedChanges(
            $this->createReviewResponseTransfer(),
            $this->createAcceptedItemReviewTransfers(),
            static::SCOPE_STANDING,
        );
    }

    public function testPassesEveryAcceptedGroupKeyToTheStrategy(): void
    {
        // Arrange
        $repositoryMock = $this->createMock(OrderExperienceManagementRepositoryInterface::class);
        $repositoryMock->method('getRecurringScheduleItemGroupKeysByScheduleId')->willReturn($this->createGroupKeyIndex());

        $scopeStrategyMock = $this->createMock(ScheduleReviewScopeStrategyInterface::class);
        $scopeStrategyMock->expects($this->once())
            ->method('applyAcceptedItems')
            ->willReturnCallback(function (
                array $acceptedPricesByGroupKey,
                array $acceptedQuantitiesByGroupKey,
                array $groupKeysByIdRecurringScheduleItem,
                bool $isNetMode,
            ): void {
                $this->assertCount(static::ACCEPTED_ITEM_COUNT, $acceptedPricesByGroupKey);
                $this->assertCount(static::ACCEPTED_ITEM_COUNT, $acceptedQuantitiesByGroupKey);
                $this->assertCount(static::ACCEPTED_ITEM_COUNT, $groupKeysByIdRecurringScheduleItem);
                $this->assertFalse($isNetMode);
            });

        // Act
        $this->createChangeApplier($scopeStrategyMock, $repositoryMock)->applyApprovedChanges(
            $this->createReviewResponseTransfer(),
            $this->createAcceptedItemReviewTransfers(),
            static::SCOPE_STANDING,
        );
    }

    public function testSkipsTheGroupKeyReadWhenNoAcceptedItemIsRetained(): void
    {
        // Arrange
        $scopeStrategyMock = $this->createMock(ScheduleReviewScopeStrategyInterface::class);
        $scopeStrategyMock->expects($this->never())->method('applyAcceptedItems');
        $scopeStrategyMock->expects($this->once())->method('applyRemoval');

        $repositoryMock = $this->createMock(OrderExperienceManagementRepositoryInterface::class);
        $repositoryMock->expects($this->never())->method('getRecurringScheduleItemGroupKeysByScheduleId');

        $removedItemReviewTransfer = (new RecurringScheduleItemReviewTransfer())
            ->setRecurringScheduleItem((new RecurringScheduleItemTransfer())->setGroupKey(sprintf(static::GROUP_KEY_TEMPLATE, 0)))
            ->setIsRemoved(true);

        // Act
        $this->createChangeApplier($scopeStrategyMock, $repositoryMock)->applyApprovedChanges(
            $this->createReviewResponseTransfer(),
            [$removedItemReviewTransfer],
            static::SCOPE_STANDING,
        );
    }

    protected function createChangeApplier(
        ScheduleReviewScopeStrategyInterface $scheduleReviewScopeStrategy,
        OrderExperienceManagementRepositoryInterface $repository,
    ): ScheduleReviewChangeApplier {
        $scopeStrategyResolverMock = $this->createMock(ScheduleReviewScopeStrategyResolverInterface::class);
        $scopeStrategyResolverMock->method('resolve')->willReturn($scheduleReviewScopeStrategy);

        return new ScheduleReviewChangeApplier(
            $scopeStrategyResolverMock,
            $this->createMock(ScheduleReviewItemAdderInterface::class),
            new AcceptedItemReviewMapper(),
            $repository,
        );
    }

    protected function createReviewResponseTransfer(): RecurringScheduleReviewResponseTransfer
    {
        return (new RecurringScheduleReviewResponseTransfer())
            ->setRecurringSchedule((new RecurringScheduleTransfer())->setIdRecurringSchedule(static::ID_RECURRING_SCHEDULE));
    }

    /**
     * @return array<\Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer>
     */
    protected function createAcceptedItemReviewTransfers(): array
    {
        $acceptedItemReviewTransfers = [];

        for ($i = 0; $i < static::ACCEPTED_ITEM_COUNT; $i++) {
            $acceptedItemReviewTransfers[] = (new RecurringScheduleItemReviewTransfer())
                ->setRecurringScheduleItem((new RecurringScheduleItemTransfer())->setGroupKey(sprintf(static::GROUP_KEY_TEMPLATE, $i)))
                ->setCurrentPrice(static::ACCEPTED_PRICE)
                ->setAcceptedQuantity(static::ACCEPTED_QUANTITY);
        }

        return $acceptedItemReviewTransfers;
    }

    /**
     * @return array<int, string>
     */
    protected function createGroupKeyIndex(): array
    {
        $groupKeysByIdRecurringScheduleItem = [];

        for ($i = 0; $i < static::ACCEPTED_ITEM_COUNT; $i++) {
            $groupKeysByIdRecurringScheduleItem[$i + 1] = sprintf(static::GROUP_KEY_TEMPLATE, $i);
        }

        return $groupKeysByIdRecurringScheduleItem;
    }
}
