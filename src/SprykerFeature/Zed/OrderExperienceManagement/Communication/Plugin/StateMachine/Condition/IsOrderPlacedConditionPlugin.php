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
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;

/**
 * {@inheritDoc}
 *
 * @api
 *
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface getRepository()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementFacadeInterface getFacade()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig getConfig()
 */
class IsOrderPlacedConditionPlugin extends AbstractPlugin implements ConditionPluginInterface
{
    public function check(StateMachineItemTransfer $stateMachineItemTransfer): bool
    {
        $historyTransfer = $this->getRepository()->findLatestHistoryByScheduleId($stateMachineItemTransfer->getIdentifierOrFail());

        if ($historyTransfer === null) {
            return false;
        }

        return $historyTransfer->getEventType() === SharedOrderExperienceManagementConfig::HISTORY_EVENT_TYPE_PLACED;
    }

    public function getName(): string
    {
        return 'RecurringOrders/IsOrderPlaced';
    }
}
