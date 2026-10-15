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
use SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Facade
 * @group GetRecurringScheduleCollectionPriceToleranceFilterTest
 * Add your own group annotations below this line
 */
class GetRecurringScheduleCollectionPriceToleranceFilterTest extends Unit
{
    protected const string NAME_ORDER_LEVEL = 'order-level';

    protected const string NAME_ITEM_LEVEL = 'item-level';

    protected const string NAME_BOTH_LEVELS = 'both-levels';

    protected const string NAME_NO_TOLERANCE = 'no-tolerance';

    protected const string PRICE_MODE_GROSS = 'GROSS_MODE';

    protected OrderExperienceManagementBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->ensureRecurringScheduleTablesAreEmpty();
    }

    public function testReturnsSchedulesCarryingAToleranceOnEitherLevelAndExcludesTheRest(): void
    {
        // Arrange
        $idCustomer = $this->seedAllSchedulesOfAllTypes();

        $recurringScheduleCriteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addCustomerId($idCustomer)
                    ->setIsWithPriceTolerance(true),
            );

        // Act
        $recurringScheduleCollectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($recurringScheduleCriteriaTransfer);

        // Assert
        $this->assertEqualsCanonicalizing(
            [static::NAME_ORDER_LEVEL, static::NAME_ITEM_LEVEL, static::NAME_BOTH_LEVELS],
            $this->extractNames($recurringScheduleCollectionTransfer->getRecurringSchedules()->getArrayCopy()),
        );
    }

    public function testReturnsOnlySchedulesCarryingNoToleranceOnEitherLevel(): void
    {
        // Arrange
        $idCustomer = $this->seedAllSchedulesOfAllTypes();

        $recurringScheduleCriteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addCustomerId($idCustomer)
                    ->setIsWithPriceTolerance(false),
            );

        // Act
        $recurringScheduleCollectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($recurringScheduleCriteriaTransfer);

        // Assert
        $this->assertSame(
            [static::NAME_NO_TOLERANCE],
            $this->extractNames($recurringScheduleCollectionTransfer->getRecurringSchedules()->getArrayCopy()),
        );
    }

    public function testTreatsAScheduleWithoutItemsAndWithoutAnOrderToleranceAsHavingNone(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $this->haveSchedule($idCustomer, static::NAME_NO_TOLERANCE, '0.00');

        $recurringScheduleCriteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addCustomerId($idCustomer)
                    ->setIsWithPriceTolerance(false),
            );

        // Act
        $recurringScheduleCollectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($recurringScheduleCriteriaTransfer);

        // Assert
        $this->assertCount(1, $recurringScheduleCollectionTransfer->getRecurringSchedules());
    }

    public function testTheOnAndOffResultsPartitionTheWholeSet(): void
    {
        // Arrange
        $idCustomer = $this->seedAllSchedulesOfAllTypes();

        // Act
        $withToleranceNames = $this->extractNames(
            $this->tester->getFacade()->getRecurringScheduleCollection(
                $this->createCriteria($idCustomer, true),
            )->getRecurringSchedules()->getArrayCopy(),
        );
        $withoutToleranceNames = $this->extractNames(
            $this->tester->getFacade()->getRecurringScheduleCollection(
                $this->createCriteria($idCustomer, false),
            )->getRecurringSchedules()->getArrayCopy(),
        );

        // Assert
        $this->assertSame([], array_intersect($withToleranceNames, $withoutToleranceNames));
        $this->assertEqualsCanonicalizing(
            [static::NAME_ORDER_LEVEL, static::NAME_ITEM_LEVEL, static::NAME_BOTH_LEVELS, static::NAME_NO_TOLERANCE],
            array_merge($withToleranceNames, $withoutToleranceNames),
        );
    }

    public function testReturnsEveryScheduleWhenTheFilterIsNotRequested(): void
    {
        // Arrange
        $idCustomer = $this->seedAllSchedulesOfAllTypes();

        $recurringScheduleCriteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())->addCustomerId($idCustomer),
            );

        // Act
        $recurringScheduleCollectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($recurringScheduleCriteriaTransfer);

        // Assert
        $this->assertCount(4, $recurringScheduleCollectionTransfer->getRecurringSchedules());
    }

    public function testMatchesAnOrderLevelToleranceOnAScheduleWithoutItems(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $this->haveSchedule($idCustomer, static::NAME_ORDER_LEVEL, '2.00');

        $recurringScheduleCriteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addCustomerId($idCustomer)
                    ->setIsWithPriceTolerance(true),
            );

        // Act
        $recurringScheduleCollectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($recurringScheduleCriteriaTransfer);

        // Assert
        $this->assertCount(1, $recurringScheduleCollectionTransfer->getRecurringSchedules());
    }

    public function testAppliesTheToleranceFilterTogetherWithTheCycleTotalRange(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();

        $cheapWithToleranceTransfer = $this->haveSchedule($idCustomer, static::NAME_ORDER_LEVEL, '2.00');
        $this->haveItem($cheapWithToleranceTransfer, 1, 500, '0.00');

        $expensiveWithToleranceTransfer = $this->haveSchedule($idCustomer, static::NAME_BOTH_LEVELS, '2.00');
        $this->haveItem($expensiveWithToleranceTransfer, 1, 50000, '0.00');

        $cheapWithoutToleranceTransfer = $this->haveSchedule($idCustomer, static::NAME_NO_TOLERANCE, '0.00');
        $this->haveItem($cheapWithoutToleranceTransfer, 1, 500, '0.00');

        $recurringScheduleCriteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addCustomerId($idCustomer)
                    ->setIsWithPriceTolerance(true)
                    ->setEstimatedTotalMin(100)
                    ->setEstimatedTotalMax(1000),
            );

        // Act
        $recurringScheduleCollectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($recurringScheduleCriteriaTransfer);

        // Assert
        $this->assertSame(
            [static::NAME_ORDER_LEVEL],
            $this->extractNames($recurringScheduleCollectionTransfer->getRecurringSchedules()->getArrayCopy()),
        );
    }

    public function testCountsEachMatchingScheduleOnceDespiteTheItemJoin(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $recurringScheduleTransfer = $this->haveSchedule($idCustomer, static::NAME_ORDER_LEVEL, '2.00');
        $this->haveItem($recurringScheduleTransfer, 1, 500, '0.00');
        $this->haveItem($recurringScheduleTransfer, 1, 500, '0.00');
        $this->haveItem($recurringScheduleTransfer, 1, 500, '0.00');

        $recurringScheduleCriteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addCustomerId($idCustomer)
                    ->setIsWithPriceTolerance(true),
            )
            ->setPagination((new PaginationTransfer())->setOffset(0)->setLimit(10));

        // Act
        $recurringScheduleCollectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($recurringScheduleCriteriaTransfer);

        // Assert
        $this->assertCount(1, $recurringScheduleCollectionTransfer->getRecurringSchedules());
        $this->assertSame(1, $recurringScheduleCollectionTransfer->getPaginationOrFail()->getNbResults());
    }

    protected function createCriteria(int $idCustomer, bool $isWithPriceTolerance): RecurringScheduleCriteriaTransfer
    {
        return (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addCustomerId($idCustomer)
                    ->setIsWithPriceTolerance($isWithPriceTolerance),
            );
    }

    protected function seedAllSchedulesOfAllTypes(): int
    {
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();

        $orderLevelTransfer = $this->haveSchedule($idCustomer, static::NAME_ORDER_LEVEL, '2.00');
        $this->haveItem($orderLevelTransfer, 1, 1000, '0.00');

        $itemLevelTransfer = $this->haveSchedule($idCustomer, static::NAME_ITEM_LEVEL, '0.00');
        $this->haveItem($itemLevelTransfer, 1, 1000, '0.00');
        $this->haveItem($itemLevelTransfer, 1, 1000, '0.25');

        $bothLevelsTransfer = $this->haveSchedule($idCustomer, static::NAME_BOTH_LEVELS, '2.00');
        $this->haveItem($bothLevelsTransfer, 1, 1000, '1.50');

        $noToleranceTransfer = $this->haveSchedule($idCustomer, static::NAME_NO_TOLERANCE, '0.00');
        $this->haveItem($noToleranceTransfer, 1, 1000, '0.00');

        return $idCustomer;
    }

    protected function haveSchedule(int $idCustomer, string $name, string $priceTolerancePercent): RecurringScheduleTransfer
    {
        return $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::NAME => $name,
            RecurringScheduleTransfer::PRICE_MODE => static::PRICE_MODE_GROSS,
            RecurringScheduleTransfer::PRICE_TOLERANCE_PERCENT => $priceTolerancePercent,
        ]);
    }

    protected function haveItem(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        int $quantity,
        int $referenceGrossPrice,
        string $priceTolerancePercent
    ): void {
        $this->tester->haveRecurringScheduleItem($recurringScheduleTransfer->getIdRecurringScheduleOrFail(), [
            RecurringScheduleItemTransfer::QUANTITY => $quantity,
            RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => $referenceGrossPrice,
            RecurringScheduleItemTransfer::PRICE_TOLERANCE_PERCENT => $priceTolerancePercent,
        ]);
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleTransfer> $recurringScheduleTransfers
     *
     * @return array<string>
     */
    protected function extractNames(array $recurringScheduleTransfers): array
    {
        return array_map(
            static fn (RecurringScheduleTransfer $recurringScheduleTransfer): string => (string)$recurringScheduleTransfer->getName(),
            $recurringScheduleTransfers,
        );
    }
}
