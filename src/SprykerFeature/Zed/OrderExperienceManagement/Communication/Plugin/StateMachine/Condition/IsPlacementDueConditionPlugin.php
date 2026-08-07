<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Condition;

use Generated\Shared\Transfer\StateMachineItemTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\StateMachine\Dependency\Plugin\ConditionPluginInterface;

/**
 * {@inheritDoc}
 *
 * @api
 *
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementBusinessFactory getBusinessFactory()
 */
class IsPlacementDueConditionPlugin extends AbstractPlugin implements ConditionPluginInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function check(StateMachineItemTransfer $stateMachineItemTransfer): bool
    {
        return $this->getBusinessFactory()
            ->createRecurringScheduleDueChecker()
            ->isPlacementDue($stateMachineItemTransfer->getIdentifierOrFail());
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getName(): string
    {
        return 'RecurringOrders/IsPlacementDue';
    }
}
