<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Intake;

use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\OrderIntakeItemTransfer;
use Generated\Shared\Transfer\OrderIntakeRequestTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\OrderIntakeValidationIssueTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\SaveOrderTransfer;
use Spryker\Zed\Calculation\Business\CalculationFacadeInterface;
use Spryker\Zed\Checkout\Business\CheckoutFacadeInterface;
use Spryker\Zed\Locale\Business\LocaleFacadeInterface;
use Spryker\Zed\Sales\Business\SalesFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Resolver\OrderIntakePaymentResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Translator\OrderIntakeMessageTranslatorInterface;

/**
 * Reuses the existing checkout path wholesale; only the quote assembly is new:
 *   assemble -> recalculateQuote -> placeOrder
 */
class OrderIntakeWriter implements OrderIntakeWriterInterface
{
    protected const string PROCESSING_RESULT_CREATED = 'created';

    protected const string PROCESSING_RESULT_REJECTED = 'rejected';

    protected const string ISSUE_FIELD_ORDER_LOCALE_NAME = 'orderLocaleName';

    protected const string ISSUE_MESSAGE_ORDER_LOCALE_NOT_APPLIED = 'The order was created, but the requested order locale could not be applied to it.';

    public function __construct(
        protected readonly OrderIntakeRequestPreparerInterface $orderIntakeRequestPreparer,
        protected readonly OrderIntakeQuoteBuilderInterface $orderIntakeQuoteBuilder,
        protected readonly OrderIntakeCartCodeApplierInterface $orderIntakeCartCodeApplier,
        protected readonly CalculationFacadeInterface $calculationFacade,
        protected readonly OrderIntakePaymentResolverInterface $orderIntakePaymentResolver,
        protected readonly CheckoutFacadeInterface $checkoutFacade,
        protected readonly OrderIntakeMessageTranslatorInterface $orderIntakeMessageTranslator,
        protected readonly SalesFacadeInterface $salesFacade,
        protected readonly LocaleFacadeInterface $localeFacade,
    ) {
    }

    public function createOrderFromIntake(
        OrderIntakeRequestTransfer $orderIntakeRequestTransfer,
    ): OrderIntakeResponseTransfer {
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $orderIntakeRequestTransfer = $this->orderIntakeRequestPreparer->prepareRequest(
            $orderIntakeRequestTransfer,
            $orderIntakeResponseTransfer,
        );

        if ($this->hasValidationIssues($orderIntakeResponseTransfer)) {
            return $this->rejectValidation($orderIntakeResponseTransfer, $orderIntakeRequestTransfer->getLocaleName());
        }

        $quoteTransfer = $this->orderIntakeQuoteBuilder->buildQuote($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);

        if ($this->hasValidationIssues($orderIntakeResponseTransfer)) {
            return $this->rejectValidation($orderIntakeResponseTransfer, $orderIntakeRequestTransfer->getLocaleName());
        }

        $quoteTransfer = $this->orderIntakeCartCodeApplier->applyCartCodes(
            $orderIntakeRequestTransfer,
            $quoteTransfer,
            $orderIntakeResponseTransfer,
        );

        if ($this->hasValidationIssues($orderIntakeResponseTransfer)) {
            return $this->rejectValidation($orderIntakeResponseTransfer, $orderIntakeRequestTransfer->getLocaleName());
        }

        // Calculation runs BEFORE validation: checkout pre-conditions such as the order-value
        // threshold read $quoteTransfer->getTotals(), which does not exist on a freshly assembled quote.
        $quoteTransfer = $this->calculationFacade->recalculateQuote($quoteTransfer);
        $orderIntakeResponseTransfer->setTotals($quoteTransfer->getTotals());

        $quoteTransfer = $this->orderIntakePaymentResolver->resolvePayment(
            $quoteTransfer,
            $orderIntakeResponseTransfer,
        );

        if ($this->hasValidationIssues($orderIntakeResponseTransfer)) {
            return $this->rejectValidation($orderIntakeResponseTransfer, $orderIntakeRequestTransfer->getLocaleName());
        }

        return $this->placeOrder($orderIntakeRequestTransfer, $quoteTransfer, $orderIntakeResponseTransfer);
    }

    protected function reportCustomerReference(
        QuoteTransfer $quoteTransfer,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
    ): OrderIntakeResponseTransfer {
        return $orderIntakeResponseTransfer->setCustomerReference(
            $quoteTransfer->getCustomer()?->getCustomerReference(),
        );
    }

    /**
     * @phpstan-impure
     */
    protected function hasValidationIssues(OrderIntakeResponseTransfer $orderIntakeResponseTransfer): bool
    {
        return $orderIntakeResponseTransfer->getValidationIssues()->count() > 0;
    }

    protected function rejectValidation(
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        ?string $localeName,
    ): OrderIntakeResponseTransfer {
        $this->translateValidationIssues($orderIntakeResponseTransfer, $localeName);

        return $orderIntakeResponseTransfer
            ->setIsSuccessful(false)
            ->setProcessingResult(static::PROCESSING_RESULT_REJECTED);
    }

    protected function translateValidationIssues(
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        ?string $localeName,
    ): void {
        $localeTransfer = $this->orderIntakeMessageTranslator->resolveLocale($localeName);

        foreach ($orderIntakeResponseTransfer->getValidationIssues() as $validationIssueTransfer) {
            $message = $validationIssueTransfer->getMessage();

            if ($message === null) {
                continue;
            }

            $validationIssueTransfer->setMessage(
                $this->orderIntakeMessageTranslator->translateValidationIssue($message, $validationIssueTransfer->getParameters(), $localeTransfer),
            );
        }
    }

    protected function placeOrder(
        OrderIntakeRequestTransfer $orderIntakeRequestTransfer,
        QuoteTransfer $quoteTransfer,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
    ): OrderIntakeResponseTransfer {
        $checkoutResponseTransfer = $this->checkoutFacade->placeOrder($quoteTransfer);

        if (!$checkoutResponseTransfer->getIsSuccess()) {
            return $this->rejectFromCheckoutResponse(
                $orderIntakeResponseTransfer,
                $checkoutResponseTransfer,
                $orderIntakeRequestTransfer->getLocaleName(),
            );
        }

        $saveOrderTransfer = $checkoutResponseTransfer->getSaveOrderOrFail();

        $this->reportCustomerReference($quoteTransfer, $orderIntakeResponseTransfer)
            ->setIsSuccessful(true)
            ->setProcessingResult(static::PROCESSING_RESULT_CREATED)
            ->setOrderReference($saveOrderTransfer->getOrderReference());

        if (!$this->applyOrderLocale($orderIntakeRequestTransfer, $saveOrderTransfer)) {
            $localeTransfer = $this->orderIntakeMessageTranslator->resolveLocale($orderIntakeRequestTransfer->getLocaleName());

            $orderIntakeResponseTransfer->addValidationIssue(
                (new OrderIntakeValidationIssueTransfer())
                    ->setField(static::ISSUE_FIELD_ORDER_LOCALE_NAME)
                    ->setMessage($this->orderIntakeMessageTranslator->translateValidationIssue(
                        static::ISSUE_MESSAGE_ORDER_LOCALE_NOT_APPLIED,
                        [],
                        $localeTransfer,
                    )),
            );
        }

        return $this->addResolvedItems($orderIntakeRequestTransfer, $saveOrderTransfer, $orderIntakeResponseTransfer);
    }

    protected function applyOrderLocale(
        OrderIntakeRequestTransfer $orderIntakeRequestTransfer,
        SaveOrderTransfer $saveOrderTransfer,
    ): bool {
        $localeName = $orderIntakeRequestTransfer->getOrderLocaleName();

        if ($localeName === null || trim($localeName) === '') {
            return true;
        }

        $localeTransfer = $this->localeFacade->getLocale($localeName);

        return $this->salesFacade->updateOrder(
            (new OrderTransfer())->setFkLocale($localeTransfer->getIdLocaleOrFail()),
            $saveOrderTransfer->getIdSalesOrderOrFail(),
        );
    }

    protected function addResolvedItems(
        OrderIntakeRequestTransfer $orderIntakeRequestTransfer,
        SaveOrderTransfer $saveOrderTransfer,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
    ): OrderIntakeResponseTransfer {
        $uuidsBySku = [];

        foreach ($saveOrderTransfer->getOrderItems() as $savedItemTransfer) {
            $uuidsBySku[$savedItemTransfer->getSkuOrFail()][] = $savedItemTransfer->getUuid();
        }

        foreach ($orderIntakeRequestTransfer->getItems() as $orderIntakeItemTransfer) {
            $sku = $orderIntakeItemTransfer->getSkuOrFail();

            $candidateUuids = $uuidsBySku[$sku] ?? [];
            $uuid = array_shift($candidateUuids);
            $uuidsBySku[$sku] = $candidateUuids;

            $orderIntakeResponseTransfer->addItem(
                (new OrderIntakeItemTransfer())
                    ->fromArray($orderIntakeItemTransfer->toArray(true, true), true)
                    ->setUuid($uuid),
            );
        }

        return $orderIntakeResponseTransfer;
    }

    protected function rejectFromCheckoutResponse(
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        CheckoutResponseTransfer $checkoutResponseTransfer,
        ?string $localeName,
    ): OrderIntakeResponseTransfer {
        $localeTransfer = $this->orderIntakeMessageTranslator->resolveLocale($localeName);

        foreach ($checkoutResponseTransfer->getErrors() as $checkoutErrorTransfer) {
            $message = $checkoutErrorTransfer->getMessage();

            $orderIntakeResponseTransfer->addValidationIssue(
                (new OrderIntakeValidationIssueTransfer())
                    ->setMessage($message !== null
                        ? $this->orderIntakeMessageTranslator->translateCheckoutError($message, $checkoutErrorTransfer->getParameters(), $localeTransfer)
                        : null),
            );
        }

        return $orderIntakeResponseTransfer
            ->setIsSuccessful(false)
            ->setProcessingResult(static::PROCESSING_RESULT_REJECTED);
    }
}
