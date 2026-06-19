<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Writer;

use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\ExpenseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\ShipmentMethodTransfer;
use Generated\Shared\Transfer\StateMachineItemTransfer;
use Generated\Shared\Transfer\StateMachineProcessTransfer;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use Spryker\Zed\StateMachine\Business\StateMachineFacadeInterface;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Mapper\RecurringScheduleItemMapperInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Mapper\RecurringScheduleMapperInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Sanitizer\QuoteSanitizerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\RecurringScheduleCheckoutValidatorInterface;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementEntityManagerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface;

class ScheduleWriter implements ScheduleWriterInterface
{
    use TransactionTrait;

    public function __construct(
        protected OrderExperienceManagementEntityManagerInterface $entityManager,
        protected StateMachineFacadeInterface $stateMachineFacade,
        protected OrderExperienceManagementConfig $subscriptionConfig,
        protected RecurringScheduleCheckoutValidatorInterface $validator,
        protected RecurringScheduleMapperInterface $scheduleMapper,
        protected RecurringScheduleItemMapperInterface $scheduleItemMapper,
        protected OrderExperienceManagementRepositoryInterface $subscriptionRepository,
        protected QuoteSanitizerInterface $quoteSanitizer,
    ) {
    }

    public function saveRecurringScheduleFromCheckout(
        QuoteTransfer $quoteTransfer,
        CheckoutResponseTransfer $checkoutResponseTransfer,
    ): void {
        if (!$checkoutResponseTransfer->getIsSuccess()) {
            return;
        }

        if (!$this->validator->canCreateFromCheckout($quoteTransfer)) {
            return;
        }

        $quoteTransfer = $this->quoteSanitizer->sanitize($quoteTransfer);
        $recurringScheduleTransfer = $this->scheduleMapper->mapQuoteToRecurringSchedule($quoteTransfer, $checkoutResponseTransfer);

        $this->getTransactionHandler()->handleTransaction(
            fn () => $this->executeSaveRecurringScheduleTransaction($recurringScheduleTransfer, $quoteTransfer),
        );
    }

    protected function executeSaveRecurringScheduleTransaction(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        QuoteTransfer $quoteTransfer,
    ): void {
        $recurringScheduleTransfer = $this->entityManager->createRecurringSchedule($recurringScheduleTransfer);

        $this->saveRecurringScheduleItems($quoteTransfer, $recurringScheduleTransfer->getIdRecurringScheduleOrFail());

        $stateMachineProcessTransfer = (new StateMachineProcessTransfer())
            ->setStateMachineName($this->subscriptionConfig->getStateMachineName())
            ->setProcessName($this->subscriptionConfig->getProcessName());

        $idRecurringSchedule = $recurringScheduleTransfer->getIdRecurringScheduleOrFail();

        $this->stateMachineFacade->triggerForNewStateMachineItem(
            $stateMachineProcessTransfer,
            $idRecurringSchedule,
        );

        $this->autoActivate($idRecurringSchedule);
    }

    protected function autoActivate(int $idRecurringSchedule): void
    {
        $idDraftState = $this->subscriptionRepository->findSmStateIdByStateMachineAndStateName(
            $this->subscriptionConfig->getStateMachineName(),
            $this->subscriptionConfig->getInitialState(),
        );

        if ($idDraftState === null) {
            return;
        }

        $stateMachineItemTransfer = (new StateMachineItemTransfer())
            ->setIdentifier($idRecurringSchedule)
            ->setIdItemState($idDraftState);

        $this->stateMachineFacade->triggerEvent(
            SharedOrderExperienceManagementConfig::SM_EVENT_ACTIVATE,
            $stateMachineItemTransfer,
        );
    }

    protected function saveRecurringScheduleItems(QuoteTransfer $quoteTransfer, int $idRecurringSchedule): void
    {
        $shipmentDataByShipmentTypeUuid = $this->buildExpenseShipmentMethodMap($quoteTransfer);

        $itemTransfers = array_merge(
            $quoteTransfer->getItems()->getArrayCopy(),
            $quoteTransfer->getBundleItems()->getArrayCopy(),
        );

        foreach ($itemTransfers as $itemTransfer) {
            $recurringScheduleItemTransfer = $this->scheduleItemMapper->mapItemToRecurringScheduleItem(
                $itemTransfer,
                $idRecurringSchedule,
                $shipmentDataByShipmentTypeUuid,
            );

            $this->entityManager->createRecurringScheduleItem($recurringScheduleItemTransfer);
        }
    }

    /**
     * @return array<string, array{ShipmentMethodTransfer::ID_SHIPMENT_METHOD: int, ExpenseTransfer::UNIT_GROSS_PRICE: int, ExpenseTransfer::UNIT_NET_PRICE: int}>
     */
    protected function buildExpenseShipmentMethodMap(QuoteTransfer $quoteTransfer): array
    {
        $shipmentDataByShipmentTypeUuid = [];

        foreach ($quoteTransfer->getExpenses() as $expenseTransfer) {
            $shipmentTransfer = $expenseTransfer->getShipment();

            if ($shipmentTransfer?->getMethod()?->getIdShipmentMethod() === null) {
                continue;
            }

            $shipmentTypeUuid = $shipmentTransfer->getShipmentTypeUuid() ?? '';
            $shipmentDataByShipmentTypeUuid[$shipmentTypeUuid] = [
                ShipmentMethodTransfer::ID_SHIPMENT_METHOD => $shipmentTransfer->getMethod()->getIdShipmentMethod(),
                ExpenseTransfer::UNIT_GROSS_PRICE => $expenseTransfer->getUnitGrossPrice() ?? 0,
                ExpenseTransfer::UNIT_NET_PRICE => $expenseTransfer->getUnitNetPrice() ?? 0,
            ];
        }

        return $shipmentDataByShipmentTypeUuid;
    }
}
