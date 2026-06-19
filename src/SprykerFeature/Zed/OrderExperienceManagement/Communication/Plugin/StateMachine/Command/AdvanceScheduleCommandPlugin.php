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
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Communication\OrderExperienceManagementCommunicationFactory getFactory()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig getConfig()
 */
class AdvanceScheduleCommandPlugin extends AbstractPlugin implements CommandPluginInterface
{
    /**
     * {@inheritDoc}
     * Specification:
     * - Runs as the state machine command of the `RecurringOrders/AdvanceSchedule` transition (skipped → active).
     * - Loads the recurring schedule by the state machine item identifier.
     * - Records a "skipped" history entry timestamped at the occurrence being skipped (the current `next_trigger_date`), without placing an order.
     * - Advances `next_trigger_date` by one cadence period to the following occurrence.
     *
     * @api
     */
    public function run(StateMachineItemTransfer $stateMachineItemTransfer): void
    {
        $this->getBusinessFactory()
            ->createScheduleSkipper()
            ->skip($stateMachineItemTransfer->getIdentifierOrFail());
    }

    public function getName(): string
    {
        return 'RecurringOrders/AdvanceSchedule';
    }
}
