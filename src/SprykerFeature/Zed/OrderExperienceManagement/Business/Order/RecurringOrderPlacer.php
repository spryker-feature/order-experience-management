<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Order;

use Generated\Shared\Transfer\CalculableObjectTransfer;
use Generated\Shared\Transfer\CheckoutErrorTransfer;
use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\QuoteResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleHistoryTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Spryker\Shared\Kernel\Container\GlobalContainer;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\Cart\Business\CartFacadeInterface;
use Spryker\Zed\Checkout\Business\CheckoutFacadeInterface;
use Spryker\Zed\Payment\Business\PaymentFacadeInterface;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\RecurringOrderBuyerMailNotificationSenderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementEntityManagerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface;
use Throwable;

class RecurringOrderPlacer implements RecurringOrderPlacerInterface
{
    use LoggerTrait;

    protected const string GLOSSARY_KEY_ITEMS_NOT_PURCHASABLE = 'recurring_orders.error.items_not_purchasable';

    protected const string ERROR_SCHEDULE_NOT_FOUND = 'Recurring schedule not found.';

    public function __construct(
        protected OrderExperienceManagementRepositoryInterface $subscriptionRepository,
        protected OrderExperienceManagementEntityManagerInterface $entityManager,
        protected CheckoutFacadeInterface $checkoutFacade,
        protected RecurringOrderBuyerMailNotificationSenderInterface $mailNotificationSender,
        protected RecurringOrderQuoteBuilderInterface $quoteBuilder,
        protected CartFacadeInterface $cartFacade,
        protected PaymentFacadeInterface $paymentFacade,
    ) {
    }

    public function placeOrder(int $idRecurringSchedule): CheckoutResponseTransfer
    {
        $scheduleTransfer = $this->subscriptionRepository->findRecurringScheduleById($idRecurringSchedule);

        if ($scheduleTransfer === null) {
            return $this->createScheduleNotFoundResponse();
        }

        $quoteTransfer = $this->quoteBuilder->buildPlaceableQuote($scheduleTransfer, true);
        $expectedSkuQuantities = $this->mapItemSkuQuantities($quoteTransfer);

        $paymentTransfer = $quoteTransfer->getPayment();
        $paymentTransfers = clone $quoteTransfer->getPayments();

        $this->ensureStoreContext($quoteTransfer);

        $quoteResponseTransfer = $this->cartFacade->reloadItemsInQuote($quoteTransfer);

        $checkoutResponseTransfer = $this->createReloadErrorResponse($quoteResponseTransfer);

        if ($checkoutResponseTransfer === null) {
            $quoteTransfer = $quoteResponseTransfer->getQuoteTransferOrFail();
            $quoteTransfer->setPayment($paymentTransfer)->setPayments($paymentTransfers);
            $quoteTransfer = $this->recalculatePayments($quoteTransfer);
            $this->skipAddressSaving($quoteTransfer);

            $unpurchasableSkus = $this->findUnpurchasableSkus($expectedSkuQuantities, $quoteTransfer);

            $checkoutResponseTransfer = $this->createUnpurchasableItemsResponse($unpurchasableSkus) ?? $this->checkoutFacade->placeOrder($quoteTransfer);
        }

        $this->writeHistory($scheduleTransfer, $checkoutResponseTransfer);

        if (!$checkoutResponseTransfer->getIsSuccess()) {
            try {
                $this->mailNotificationSender->notifyPlacementFailure($scheduleTransfer->getIdRecurringScheduleOrFail());
            } catch (Throwable $throwable) {
                $this->getLogger()->error(
                    sprintf('Placement failure notification email could not be sent for schedule ID %d: %s', $scheduleTransfer->getIdRecurringScheduleOrFail(), $throwable->getMessage()),
                    ['exception' => $throwable],
                );
            }
        }

        return $checkoutResponseTransfer;
    }

    protected function recalculatePayments(QuoteTransfer $quoteTransfer): QuoteTransfer
    {
        $calculableObjectTransfer = (new CalculableObjectTransfer())->fromArray($quoteTransfer->toArray(), true);
        $calculableObjectTransfer->setOriginalQuote($quoteTransfer);

        $this->paymentFacade->recalculatePayments($calculableObjectTransfer);

        return $quoteTransfer->fromArray($calculableObjectTransfer->toArray(), true);
    }

    /**
     * @return array<string, int>
     */
    protected function mapItemSkuQuantities(QuoteTransfer $quoteTransfer): array
    {
        $skuQuantities = [];

        foreach ($quoteTransfer->getItems() as $itemTransfer) {
            $sku = $itemTransfer->getSkuOrFail();
            $skuQuantities[$sku] = ($skuQuantities[$sku] ?? 0) + $itemTransfer->getQuantityOrFail();
        }

        return $skuQuantities;
    }

    /**
     * @param array<string, int> $expectedSkuQuantities
     *
     * @return list<string>
     */
    protected function findUnpurchasableSkus(array $expectedSkuQuantities, QuoteTransfer $quoteTransfer): array
    {
        $reloadedSkuQuantities = $this->mapItemSkuQuantities($quoteTransfer);
        $unpurchasableSkus = [];

        foreach ($expectedSkuQuantities as $sku => $expectedQuantity) {
            if (($reloadedSkuQuantities[$sku] ?? 0) >= $expectedQuantity) {
                continue;
            }

            $unpurchasableSkus[] = $sku;
        }

        return $unpurchasableSkus;
    }

    /**
     * @param list<string> $unpurchasableSkus
     */
    protected function createUnpurchasableItemsResponse(array $unpurchasableSkus): ?CheckoutResponseTransfer
    {
        if ($unpurchasableSkus === []) {
            return null;
        }

        return (new CheckoutResponseTransfer())
            ->setIsSuccess(false)
            ->addError(
                (new CheckoutErrorTransfer())
                    ->setMessage(static::GLOSSARY_KEY_ITEMS_NOT_PURCHASABLE)
                    ->setParameters(['%skus%' => implode(', ', $unpurchasableSkus)]),
            );
    }

    protected function createReloadErrorResponse(QuoteResponseTransfer $quoteResponseTransfer): ?CheckoutResponseTransfer
    {
        if ($quoteResponseTransfer->getIsSuccessful()) {
            return null;
        }

        $checkoutResponseTransfer = (new CheckoutResponseTransfer())->setIsSuccess(false);

        foreach ($quoteResponseTransfer->getErrors() as $quoteErrorTransfer) {
            $checkoutResponseTransfer->addError(
                (new CheckoutErrorTransfer())->setMessage($quoteErrorTransfer->getMessage()),
            );
        }

        return $checkoutResponseTransfer;
    }

    protected function createScheduleNotFoundResponse(): CheckoutResponseTransfer
    {
        return (new CheckoutResponseTransfer())
            ->setIsSuccess(false)
            ->addError((new CheckoutErrorTransfer())->setMessage(static::ERROR_SCHEDULE_NOT_FOUND));
    }

    protected function skipAddressSaving(QuoteTransfer $quoteTransfer): void
    {
        $quoteTransfer->setIsAddressSavingSkipped(true);

        $quoteTransfer->getBillingAddress()?->setIsAddressSavingSkipped(true);

        foreach ($quoteTransfer->getItems() as $itemTransfer) {
            $itemTransfer->getShipment()?->getShippingAddress()?->setIsAddressSavingSkipped(true);
        }
    }

    protected function ensureStoreContext(QuoteTransfer $quoteTransfer): void
    {
        $storeName = $quoteTransfer->getStore()?->getName();

        if ($storeName === null) {
            return;
        }

        $globalContainer = new GlobalContainer();

        if ($globalContainer->has('store')) {
            return;
        }

        $globalContainer->getContainer()->set('store', function () use ($storeName) {
            return $storeName;
        });
    }

    protected function writeHistory(RecurringScheduleTransfer $scheduleTransfer, CheckoutResponseTransfer $checkoutResponseTransfer): void
    {
        $historyTransfer = (new RecurringScheduleHistoryTransfer())
        ->setIdRecurringSchedule($scheduleTransfer->getIdRecurringScheduleOrFail());

        if ($checkoutResponseTransfer->getIsSuccess()) {
            $historyTransfer
                ->setEventType(SharedOrderExperienceManagementConfig::HISTORY_EVENT_TYPE_PLACED)
                ->setIdSalesOrder($checkoutResponseTransfer->getSaveOrder()?->getIdSalesOrder());
        } else {
            $errors = [];

            foreach ($checkoutResponseTransfer->getErrors() as $error) {
                $errors[] = [
                    'message' => $error->getMessage(),
                    'parameters' => $error->getParameters(),
                ];
            }

            $historyTransfer
                ->setEventType(SharedOrderExperienceManagementConfig::HISTORY_EVENT_TYPE_FAILED)
                ->setDetail(json_encode($errors, JSON_THROW_ON_ERROR));
        }

        $this->entityManager->createRecurringScheduleHistory($historyTransfer);
    }
}
