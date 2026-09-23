<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Transition;

use Generated\Shared\Transfer\ItemCollectionTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Spryker\Zed\Oms\Business\OmsFacadeInterface;
use Spryker\Zed\Oms\Business\Process\ProcessInterface;

/**
 * Resolves the manual OMS events legal for an order item in its current state.
 *
 * @see \Spryker\Zed\Oms\Business\Process\Process::getManualEvents()
 * @see \Spryker\Zed\Oms\Business\Process\Process::getManualEventsBySource()
 */
class AvailableOrderItemTransitionReader implements AvailableOrderItemTransitionReaderInterface
{
    /**
     * @var array<string, array<string, array<int, string>>>|null
     */
    protected ?array $manualEventNamesByProcessAndState = null;

    public function __construct(protected readonly OmsFacadeInterface $omsFacade)
    {
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function getAvailableTransitionsByOrderItemId(ItemCollectionTransfer $itemCollectionTransfer): array
    {
        $availableTransitionsByOrderItemId = [];

        foreach ($itemCollectionTransfer->getItems() as $itemTransfer) {
            $idSalesOrderItem = $itemTransfer->getIdSalesOrderItem();

            if ($idSalesOrderItem === null) {
                continue;
            }

            $availableTransitionsByOrderItemId[$idSalesOrderItem] = $this->getAvailableTransitions($itemTransfer);
        }

        return $availableTransitionsByOrderItemId;
    }

    /**
     * @return array<int, string>
     */
    public function getAvailableTransitions(ItemTransfer $itemTransfer): array
    {
        $processName = $itemTransfer->getProcess();
        $stateName = $itemTransfer->getState()?->getName();

        if ($processName === null || $stateName === null) {
            return [];
        }

        return $this->getManualEventNamesByProcessAndState()[$processName][$stateName] ?? [];
    }

    /**
     * @return array<string, array<string, array<int, string>>> processName => stateName => event names
     */
    protected function getManualEventNamesByProcessAndState(): array
    {
        if ($this->manualEventNamesByProcessAndState !== null) {
            return $this->manualEventNamesByProcessAndState;
        }

        $manualEventNamesByProcessAndState = [];

        foreach ($this->omsFacade->getProcesses() as $processName => $process) {
            $manualEventNamesByProcessAndState[$processName] = $this->extractManualEventNamesBySourceState($process);
        }

        $this->manualEventNamesByProcessAndState = $manualEventNamesByProcessAndState;

        return $manualEventNamesByProcessAndState;
    }

    /**
     * @return array<string, array<int, string>> Event names keyed by source state name.
     */
    protected function extractManualEventNamesBySourceState(ProcessInterface $process): array
    {
        $eventNamesByStateName = [];

        foreach ($process->getAllTransitions() as $transition) {
            if (!$transition->hasEvent() || !$transition->getEvent()->isManual()) {
                continue;
            }

            $stateName = $transition->getSource()->getName();
            $eventName = $transition->getEvent()->getName();

            if (!in_array($eventName, $eventNamesByStateName[$stateName] ?? [], true)) {
                $eventNamesByStateName[$stateName][] = $eventName;
            }
        }

        return $eventNamesByStateName;
    }
}
