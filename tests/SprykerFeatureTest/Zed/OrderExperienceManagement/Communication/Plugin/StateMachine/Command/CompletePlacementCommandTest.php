<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Command;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\StateMachineItemTransfer;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleItemQuery;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleQuery;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\WeeklyCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Command\CompletePlacementCommandPlugin;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementDependencyProvider;
use SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester;

/**
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Communication
 * @group Plugin
 * @group StateMachine
 * @group Command
 * @group CompletePlacementCommandPluginTest
 */
class CompletePlacementCommandTest extends Unit
{
    protected const string NEXT_TRIGGER_DATE = '2026-01-01';

    protected const string EXPECTED_NEXT_TRIGGER_DATE = '2026-01-08';

    protected const string SKU_ZERO_QUANTITY = 'sku-zero';

    protected const string SKU_KEPT = 'sku-kept';

    protected OrderExperienceManagementBusinessTester $tester;

    public function testGetNameReturnsExpectedCommandName(): void
    {
        $this->assertSame('RecurringOrders/CompletePlacement', (new CompletePlacementCommandPlugin())->getName());
    }

    public function testRunAdvancesNextTriggerDateByOneCadencePeriod(): void
    {
        // Arrange
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::PLUGINS_CADENCE_TYPE, [new WeeklyCadenceTypePlugin()]);
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::CADENCE_TYPE => SharedOrderExperienceManagementConfig::CADENCE_TYPE_WEEKLY,
            RecurringScheduleTransfer::NEXT_TRIGGER_DATE => static::NEXT_TRIGGER_DATE,
        ]);

        // Act
        $this->createCommand()->run(
            (new StateMachineItemTransfer())->setIdentifier($recurringScheduleTransfer->getIdRecurringScheduleOrFail()),
        );

        // Assert
        $recurringScheduleEntity = SpyRecurringScheduleQuery::create()
            ->findOneByIdRecurringSchedule($recurringScheduleTransfer->getIdRecurringScheduleOrFail());

        $this->assertSame(static::EXPECTED_NEXT_TRIGGER_DATE, $recurringScheduleEntity->getNextTriggerDate()->format('Y-m-d'));
    }

    public function testRunResetsNextDeliveryQuantityAfterPlacement(): void
    {
        // Arrange
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::PLUGINS_CADENCE_TYPE, [new WeeklyCadenceTypePlugin()]);
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::CADENCE_TYPE => SharedOrderExperienceManagementConfig::CADENCE_TYPE_WEEKLY,
            RecurringScheduleTransfer::NEXT_TRIGGER_DATE => static::NEXT_TRIGGER_DATE,
        ]);
        $idRecurringSchedule = $recurringScheduleTransfer->getIdRecurringScheduleOrFail();

        $this->tester->haveRecurringScheduleItem($idRecurringSchedule, [
            RecurringScheduleItemTransfer::SKU => 'sku-a',
            RecurringScheduleItemTransfer::QUANTITY => 1,
            RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500,
            RecurringScheduleItemTransfer::NEXT_DELIVERY_QUANTITY => 5,
        ]);

        // Act
        $this->createCommand()->run(
            (new StateMachineItemTransfer())->setIdentifier($idRecurringSchedule),
        );

        // Assert
        $recurringScheduleItemEntity = SpyRecurringScheduleItemQuery::create()
            ->filterByFkRecurringSchedule($idRecurringSchedule)
            ->findOne();

        $this->assertNull($recurringScheduleItemEntity->getNextDeliveryQuantity());
    }

    public function testRunDeletesScheduleItemsWithZeroQuantity(): void
    {
        // Arrange
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::PLUGINS_CADENCE_TYPE, [new WeeklyCadenceTypePlugin()]);
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::CADENCE_TYPE => SharedOrderExperienceManagementConfig::CADENCE_TYPE_WEEKLY,
            RecurringScheduleTransfer::NEXT_TRIGGER_DATE => static::NEXT_TRIGGER_DATE,
        ]);
        $idRecurringSchedule = $recurringScheduleTransfer->getIdRecurringScheduleOrFail();

        $this->tester->haveRecurringScheduleItem($idRecurringSchedule, [
            RecurringScheduleItemTransfer::SKU => static::SKU_ZERO_QUANTITY,
            RecurringScheduleItemTransfer::QUANTITY => 0,
            RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500,
        ]);
        $this->tester->haveRecurringScheduleItem($idRecurringSchedule, [
            RecurringScheduleItemTransfer::SKU => static::SKU_KEPT,
            RecurringScheduleItemTransfer::QUANTITY => 2,
            RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500,
        ]);

        // Act
        $this->createCommand()->run(
            (new StateMachineItemTransfer())->setIdentifier($idRecurringSchedule),
        );

        // Assert
        $this->assertSame(
            0,
            SpyRecurringScheduleItemQuery::create()
                ->filterByFkRecurringSchedule($idRecurringSchedule)
                ->filterBySku(static::SKU_ZERO_QUANTITY)
                ->count(),
            'Zero-quantity schedule item should be deleted after placement.',
        );
        $this->assertSame(
            1,
            SpyRecurringScheduleItemQuery::create()
                ->filterByFkRecurringSchedule($idRecurringSchedule)
                ->filterBySku(static::SKU_KEPT)
                ->count(),
            'Non-zero-quantity schedule item should remain after placement.',
        );
    }

    protected function createCommand(): CompletePlacementCommandPlugin
    {
        $command = new CompletePlacementCommandPlugin();
        $command->setBusinessFactory($this->tester->getFactory());

        return $command;
    }
}
