<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Communication\Console;

use Generated\Shared\Transfer\PaginationTransfer;
use Generated\Shared\Transfer\RecurringScheduleConditionsTransfer;
use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use Spryker\Zed\Kernel\Communication\Console\Console;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementFacadeInterface getFacade()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementBusinessFactory getBusinessFactory()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Communication\OrderExperienceManagementCommunicationFactory getFactory()
 */
class RecurringOrderTriggerConsole extends Console
{
    protected const string NAME = 'recurring-orders:trigger-placement';

    protected const string DESCRIPTION = '(Development only) Manually triggers order placement for a recurring schedule. The same logic runs from the StateMachine PlaceOrderCommand.';

    protected const string ARGUMENT_ID = 'id-recurring-schedule';

    protected const string OPTION_VALIDATE = 'validate';

    protected function configure(): void
    {
        $this->setName(static::NAME)
            ->setDescription(static::DESCRIPTION)
            ->addArgument(static::ARGUMENT_ID, InputArgument::REQUIRED, 'ID/UUID of the recurring schedule to trigger')
            ->addOption(static::OPTION_VALIDATE, null, InputOption::VALUE_NONE, 'Run pre-placement validation before placing the order. Aborts on failure.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $idOrUuid = $input->getArgument(static::ARGUMENT_ID);
        $idRecurringSchedule = is_numeric($idOrUuid) ? (int)$idOrUuid : $this->resolveIdByUuid($idOrUuid);

        if ($idRecurringSchedule === null) {
            $this->error(sprintf('Recurring schedule not found for: %s', $idOrUuid));

            return static::CODE_ERROR;
        }

        if ($input->getOption(static::OPTION_VALIDATE)) {
            $this->info(sprintf('Validating schedule #%d before placement...', $idRecurringSchedule));

            if (!$this->getFacade()->isRecurringScheduleValid($idRecurringSchedule)) {
                $this->error('Validation failed. Placement aborted.');

                return static::CODE_ERROR;
            }

            $this->info('Validation passed.');
        }

        $this->info(sprintf('Placing order for recurring schedule #%d...', $idRecurringSchedule));

        $checkoutResponseTransfer = $this->getBusinessFactory()
            ->createRecurringOrderPlacer()
            ->placeOrder($idRecurringSchedule);

        if (!$checkoutResponseTransfer->getIsSuccess()) {
            foreach ($checkoutResponseTransfer->getErrors() as $error) {
                $this->error($error->getMessage() ?? 'Unknown error');
            }

            return static::CODE_ERROR;
        }

        $orderId = $checkoutResponseTransfer->getSaveOrder()?->getIdSalesOrder();
        $this->info(sprintf('Order placed successfully. Sales order ID: %d', $orderId));

        return static::CODE_SUCCESS;
    }

    protected function resolveIdByUuid(string $uuid): ?int
    {
        $criteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setPagination((new PaginationTransfer())->setLimit(1)->setOffset(0))
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())->addUuid($uuid),
            );

        $schedules = $this->getFacade()
            ->getRecurringScheduleCollection($criteriaTransfer)
            ->getRecurringSchedules()
            ->getArrayCopy();

        return $schedules !== [] ? $schedules[0]->getIdRecurringSchedule() : null;
    }
}
