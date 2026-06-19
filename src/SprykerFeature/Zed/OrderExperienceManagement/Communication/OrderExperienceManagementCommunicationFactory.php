<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Communication;

use Spryker\Zed\Kernel\Communication\AbstractCommunicationFactory;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Command\AdvanceScheduleCommandPlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Command\CompletePlacementCommandPlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Command\NotifyBuyerCommandPlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Command\PlaceOrderCommandPlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Condition\IsOrderPlacedConditionPlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Condition\IsPlacementDueConditionPlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Condition\IsScheduleDueConditionPlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Condition\IsScheduleValidConditionPlugin;

/**
 * @method \SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig getConfig()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementFacadeInterface getFacade()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface getRepository()
 */
class OrderExperienceManagementCommunicationFactory extends AbstractCommunicationFactory
{
    /**
     * @return array<string, \Spryker\Zed\StateMachine\Dependency\Plugin\CommandPluginInterface>
     */
    public function getStateMachineCommandPlugins(): array
    {
        $placeOrderCommandPlugin = $this->createPlaceOrderCommandPlugin();
        $advanceScheduleCommandPlugin = $this->createAdvanceScheduleCommandPlugin();
        $completePlacementCommandPlugin = $this->createCompletePlacementCommandPlugin();
        $notifyBuyerCommandPlugin = $this->createNotifyBuyerCommandPlugin();

        return [
            $placeOrderCommandPlugin->getName() => $placeOrderCommandPlugin,
            $advanceScheduleCommandPlugin->getName() => $advanceScheduleCommandPlugin,
            $completePlacementCommandPlugin->getName() => $completePlacementCommandPlugin,
            $notifyBuyerCommandPlugin->getName() => $notifyBuyerCommandPlugin,
        ];
    }

    public function createPlaceOrderCommandPlugin(): PlaceOrderCommandPlugin
    {
        return new PlaceOrderCommandPlugin();
    }

    public function createAdvanceScheduleCommandPlugin(): AdvanceScheduleCommandPlugin
    {
        return new AdvanceScheduleCommandPlugin();
    }

    public function createCompletePlacementCommandPlugin(): CompletePlacementCommandPlugin
    {
        return new CompletePlacementCommandPlugin();
    }

    public function createNotifyBuyerCommandPlugin(): NotifyBuyerCommandPlugin
    {
        return new NotifyBuyerCommandPlugin();
    }

    /**
     * @return array<string, \Spryker\Zed\StateMachine\Dependency\Plugin\ConditionPluginInterface>
     */
    public function getStateMachineConditionPlugins(): array
    {
        $isScheduleDueConditionPlugin = $this->createIsScheduleDueConditionPlugin();
        $isPlacementDueConditionPlugin = $this->createIsPlacementDueConditionPlugin();
        $isScheduleValidConditionPlugin = $this->createIsScheduleValidConditionPlugin();
        $isOrderPlacedConditionPlugin = $this->createIsOrderPlacedConditionPlugin();

        return [
            $isScheduleDueConditionPlugin->getName() => $isScheduleDueConditionPlugin,
            $isPlacementDueConditionPlugin->getName() => $isPlacementDueConditionPlugin,
            $isScheduleValidConditionPlugin->getName() => $isScheduleValidConditionPlugin,
            $isOrderPlacedConditionPlugin->getName() => $isOrderPlacedConditionPlugin,
        ];
    }

    public function createIsScheduleDueConditionPlugin(): IsScheduleDueConditionPlugin
    {
        return new IsScheduleDueConditionPlugin();
    }

    public function createIsPlacementDueConditionPlugin(): IsPlacementDueConditionPlugin
    {
        return new IsPlacementDueConditionPlugin();
    }

    public function createIsScheduleValidConditionPlugin(): IsScheduleValidConditionPlugin
    {
        return new IsScheduleValidConditionPlugin();
    }

    public function createIsOrderPlacedConditionPlugin(): IsOrderPlacedConditionPlugin
    {
        return new IsOrderPlacedConditionPlugin();
    }
}
