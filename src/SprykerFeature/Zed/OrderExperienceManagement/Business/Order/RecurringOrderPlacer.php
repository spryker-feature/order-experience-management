<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Order;

use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\Checkout\Business\CheckoutFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\RecurringOrderBuyerMailNotificationSenderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\History\RecurringSchedulePlacementHistoryWriterInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface;
use Throwable;

class RecurringOrderPlacer implements RecurringOrderPlacerInterface
{
    use LoggerTrait;

    public function __construct(
        protected OrderExperienceManagementRepositoryInterface $repository,
        protected RecurringOrderQuoteBuilderInterface $quoteBuilder,
        protected StoreContextInitializerInterface $storeContextInitializer,
        protected PlaceableQuoteReloaderInterface $quoteReloader,
        protected PlacementQuotePreparerInterface $quotePreparer,
        protected UnpurchasableItemCheckerInterface $unpurchasableItemChecker,
        protected CheckoutFacadeInterface $checkoutFacade,
        protected PlacementCheckoutResponseBuilderInterface $checkoutResponseBuilder,
        protected RecurringSchedulePlacementHistoryWriterInterface $historyWriter,
        protected RecurringOrderBuyerMailNotificationSenderInterface $mailNotificationSender,
    ) {
    }

    public function placeOrder(int $idRecurringSchedule): CheckoutResponseTransfer
    {
        $recurringScheduleTransfer = $this->repository->findRecurringScheduleById($idRecurringSchedule);

        if ($recurringScheduleTransfer === null) {
            return $this->checkoutResponseBuilder->createScheduleNotFoundResponse();
        }

        $checkoutResponseTransfer = $this->processPlacementSafely($recurringScheduleTransfer);
        $this->historyWriter->writeHistory($recurringScheduleTransfer, $checkoutResponseTransfer);
        $this->notifyOnFailure($recurringScheduleTransfer, $checkoutResponseTransfer);

        return $checkoutResponseTransfer;
    }

    protected function processPlacementSafely(RecurringScheduleTransfer $recurringScheduleTransfer): CheckoutResponseTransfer
    {
        try {
            return $this->processPlacement($recurringScheduleTransfer);
        } catch (Throwable $throwable) {
            $this->getLogger()->error(
                sprintf(
                    'Recurring order placement failed for schedule ID %d: %s',
                    $recurringScheduleTransfer->getIdRecurringScheduleOrFail(),
                    $throwable->getMessage(),
                ),
                ['exception' => $throwable],
            );

            return $this->checkoutResponseBuilder->createPlacementFailureResponse();
        }
    }

    protected function processPlacement(RecurringScheduleTransfer $recurringScheduleTransfer): CheckoutResponseTransfer
    {
        $sourceQuoteTransfer = $this->quoteBuilder->buildPlaceableQuote($recurringScheduleTransfer, true);
        $this->storeContextInitializer->initialize($sourceQuoteTransfer);

        $reloadResultTransfer = $this->quoteReloader->reloadItems($sourceQuoteTransfer);
        $quoteResponseTransfer = $reloadResultTransfer->getQuoteResponseOrFail();

        if (!$quoteResponseTransfer->getIsSuccessful()) {
            return $this->checkoutResponseBuilder->createReloadErrorResponse(
                $quoteResponseTransfer,
                $reloadResultTransfer->getNewErrorMessages(),
            );
        }

        $reloadedQuoteTransfer = $this->quotePreparer->prepareForCheckout(
            $quoteResponseTransfer->getQuoteTransferOrFail(),
            $sourceQuoteTransfer,
            $recurringScheduleTransfer,
        );

        $unpurchasableSkus = $this->unpurchasableItemChecker->getUnpurchasableSkus($sourceQuoteTransfer, $reloadedQuoteTransfer);

        if ($unpurchasableSkus !== []) {
            return $this->checkoutResponseBuilder->createUnpurchasableItemsResponse(
                $unpurchasableSkus,
                array_merge($reloadResultTransfer->getNewErrorMessages(), $reloadResultTransfer->getNewInfoMessages()),
            );
        }

        return $this->checkoutFacade->placeOrder($reloadedQuoteTransfer);
    }

    protected function notifyOnFailure(RecurringScheduleTransfer $recurringScheduleTransfer, CheckoutResponseTransfer $checkoutResponseTransfer): void
    {
        if ($checkoutResponseTransfer->getIsSuccess()) {
            return;
        }

        try {
            $this->mailNotificationSender->notifyPlacementFailure($recurringScheduleTransfer->getIdRecurringScheduleOrFail());
        } catch (Throwable $throwable) {
            $this->getLogger()->error(
                sprintf('Placement failure notification email could not be sent for schedule ID %d: %s', $recurringScheduleTransfer->getIdRecurringScheduleOrFail(), $throwable->getMessage()),
                ['exception' => $throwable],
            );
        }
    }
}
