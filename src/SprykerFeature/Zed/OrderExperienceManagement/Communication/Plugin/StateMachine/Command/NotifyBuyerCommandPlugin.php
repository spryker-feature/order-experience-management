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
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementBusinessFactory getBusinessFactory()
 */
class NotifyBuyerCommandPlugin extends AbstractPlugin implements CommandPluginInterface
{
    /**
     * {@inheritDoc}
     * - Called when event has a specific command assigned.
     * - Sends an upcoming-order notification email to the buyer.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\StateMachineItemTransfer $stateMachineItemTransfer
     *
     * @return void
     */
    public function run(StateMachineItemTransfer $stateMachineItemTransfer): void
    {
        $this->getBusinessFactory()
            ->createRecurringOrderBuyerMailNotificationSender()
            ->notifyUpcomingOrder($stateMachineItemTransfer->getIdentifierOrFail());
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return string
     */
    public function getName(): string
    {
        return 'RecurringOrders/NotifyBuyer';
    }
}
