<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Command;

use Generated\Shared\Transfer\StateMachineItemTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\StateMachine\Dependency\Plugin\CommandPluginInterface;

/**
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementFacadeInterface getFacade()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementBusinessFactory getBusinessFactory()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig getConfig()
 */
class CompletePlacementCommandPlugin extends AbstractPlugin implements CommandPluginInterface
{
    /**
     * {@inheritDoc}
     * Specification:
     * - Runs as the state machine command of the `RecurringOrders/CompletePlacement` transition, after an order is placed.
     * - Loads the recurring schedule by the state machine item identifier.
     * - Advances `next_trigger_date` by one cadence period from its current value, based on `cadence_type` and `cadence_value`.
     * - Persists the updated `next_trigger_date`.
     *
     * @api
     */
    public function run(StateMachineItemTransfer $stateMachineItemTransfer): void
    {
        $this->getBusinessFactory()
            ->createScheduleAdvancer()
            ->advance($stateMachineItemTransfer->getIdentifierOrFail());
    }

    public function getName(): string
    {
        return 'RecurringOrders/CompletePlacement';
    }
}
