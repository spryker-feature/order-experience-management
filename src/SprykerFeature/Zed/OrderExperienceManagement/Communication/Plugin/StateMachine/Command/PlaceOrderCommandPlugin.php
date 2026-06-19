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
class PlaceOrderCommandPlugin extends AbstractPlugin implements CommandPluginInterface
{
    /**
     * {@inheritDoc}
     * Specification:
     * - Runs as the state machine command of the `RecurringOrders/PlaceOrder` transition.
     * - Loads the recurring schedule and its items by the state machine item identifier.
     * - Reconstructs `QuoteTransfer` from stored `quote_data` JSON and `ItemTransfer`s from per-item `item_data` JSON; honours `next_delivery_quantity` when set.
     * - Sets `recurringOrderSettings` to null to prevent re-scheduling on post-save (infinite-loop guard).
     * - Calls `CheckoutFacade::placeOrder()` to place the order.
     * - On unsuccessful placement, sends a failure notification email to the schedule's buyer.
     *
     * @api
     */
    public function run(StateMachineItemTransfer $stateMachineItemTransfer): void
    {
        $this->getBusinessFactory()
            ->createRecurringOrderPlacer()
            ->placeOrder($stateMachineItemTransfer->getIdentifierOrFail());
    }

    public function getName(): string
    {
        return 'RecurringOrders/PlaceOrder';
    }
}
