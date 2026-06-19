<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Command;

use Generated\Shared\Transfer\StateMachineItemTransfer;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\StateMachine\Dependency\Plugin\CommandPluginInterface;
use Throwable;

/**
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementFacadeInterface getFacade()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementBusinessFactory getBusinessFactory()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Communication\OrderExperienceManagementCommunicationFactory getFactory()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig getConfig()
 */
class NotifyBuyerCommandPlugin extends AbstractPlugin implements CommandPluginInterface
{
    use LoggerTrait;

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
        try {
            $this->getBusinessFactory()
                ->createRecurringOrderBuyerMailNotificationSender()
                ->notifyUpcomingOrder($stateMachineItemTransfer->getIdentifierOrFail());
        } catch (Throwable $throwable) {
            $this->getLogger()->error(
                sprintf('Upcoming notification failed for schedule ID %d: %s', $stateMachineItemTransfer->getIdentifierOrFail(), $throwable->getMessage()),
                ['exception' => $throwable],
            );
        }
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
