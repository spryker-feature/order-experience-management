<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Condition;

use DateTimeImmutable;
use Generated\Shared\Transfer\StateMachineItemTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\StateMachine\Dependency\Plugin\ConditionPluginInterface;

/**
 * {@inheritDoc}
 *
 * @api
 *
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface getRepository()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementFacadeInterface getFacade()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig getConfig()
 */
class IsPlacementDueConditionPlugin extends AbstractPlugin implements ConditionPluginInterface
{
    public function check(StateMachineItemTransfer $stateMachineItemTransfer): bool
    {
        $dueData = $this->getRepository()->findRecurringScheduleDueData($stateMachineItemTransfer->getIdentifierOrFail());

        if ($dueData === null) {
            return false;
        }

        $triggerDate = new DateTimeImmutable($dueData->getNextTriggerDateOrFail());

        return $triggerDate <= new DateTimeImmutable('now');
    }

    public function getName(): string
    {
        return 'RecurringOrders/IsPlacementDue';
    }
}
