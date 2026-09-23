<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Intake;

use Generated\Shared\Transfer\CartCodeRequestTransfer;
use Generated\Shared\Transfer\CartCodeResponseTransfer;
use Generated\Shared\Transfer\LocaleTransfer;
use Generated\Shared\Transfer\MessageTransfer;
use Generated\Shared\Transfer\OrderIntakeRequestTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\OrderIntakeValidationIssueTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Shared\CartCode\CartCodesConfig;
use Spryker\Zed\CartCode\Business\CartCodeFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Translator\OrderIntakeMessageTranslatorInterface;

class OrderIntakeCartCodeApplier implements OrderIntakeCartCodeApplierInterface
{
    protected const string FIELD_CART_CODES = 'cartCodes';

    protected const string MESSAGE_CART_CODE_REJECTED = 'Cart code "%cartCode%" was rejected: %reason%';

    protected const string MESSAGE_CART_CODE_NOT_APPLIED = 'Cart code "%cartCode%" was not applied: it is unknown, inactive, or does not apply to this order.';

    protected const string ERROR_UNKNOWN = 'unknown error';

    public function __construct(
        protected readonly CartCodeFacadeInterface $cartCodeFacade,
        protected readonly OrderIntakeMessageTranslatorInterface $orderIntakeMessageTranslator,
    ) {
    }

    public function applyCartCodes(
        OrderIntakeRequestTransfer $orderIntakeRequestTransfer,
        QuoteTransfer $quoteTransfer,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
    ): QuoteTransfer {
        $cartCodes = $orderIntakeRequestTransfer->getCartCodes();

        if ($cartCodes === []) {
            return $quoteTransfer;
        }

        $localeTransfer = $this->orderIntakeMessageTranslator->resolveLocale($orderIntakeRequestTransfer->getLocaleName());

        foreach ($cartCodes as $cartCode) {
            $quoteTransfer = $this->applyCartCode($cartCode, $quoteTransfer, $orderIntakeResponseTransfer, $localeTransfer);
        }

        return $quoteTransfer;
    }

    protected function applyCartCode(
        string $cartCode,
        QuoteTransfer $quoteTransfer,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        LocaleTransfer $localeTransfer,
    ): QuoteTransfer {
        $cartCodeResponseTransfer = $this->cartCodeFacade->addCartCode(
            (new CartCodeRequestTransfer())
                ->setQuote($quoteTransfer)
                ->setCartCode($cartCode),
        );

        if (!$cartCodeResponseTransfer->getIsSuccessful()) {
            $this->reportRejectedCartCode($orderIntakeResponseTransfer, $cartCode, $cartCodeResponseTransfer, $localeTransfer);

            return $quoteTransfer;
        }

        if (!$this->hasSuccessMessage($cartCodeResponseTransfer)) {
            $this->reportNotAppliedCartCode($orderIntakeResponseTransfer, $cartCode);

            return $quoteTransfer;
        }

        return $cartCodeResponseTransfer->getQuoteOrFail();
    }

    protected function hasSuccessMessage(CartCodeResponseTransfer $cartCodeResponseTransfer): bool
    {
        foreach ($cartCodeResponseTransfer->getMessages() as $messageTransfer) {
            if ($messageTransfer->getType() === CartCodesConfig::MESSAGE_TYPE_SUCCESS) {
                return true;
            }
        }

        return false;
    }

    protected function reportNotAppliedCartCode(
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        string $cartCode,
    ): void {
        $orderIntakeResponseTransfer->addValidationIssue(
            (new OrderIntakeValidationIssueTransfer())
                ->setField(static::FIELD_CART_CODES)
                ->setMessage(static::MESSAGE_CART_CODE_NOT_APPLIED)
                ->setParameters(['%cartCode%' => $cartCode]),
        );
    }

    protected function reportRejectedCartCode(
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        string $cartCode,
        CartCodeResponseTransfer $cartCodeResponseTransfer,
        LocaleTransfer $localeTransfer,
    ): void {
        $orderIntakeResponseTransfer->addValidationIssue(
            (new OrderIntakeValidationIssueTransfer())
                ->setField(static::FIELD_CART_CODES)
                ->setMessage(static::MESSAGE_CART_CODE_REJECTED)
                ->setParameters([
                    '%cartCode%' => $cartCode,
                    '%reason%' => $this->extractReason($cartCodeResponseTransfer, $localeTransfer),
                ]),
        );
    }

    protected function extractReason(
        CartCodeResponseTransfer $cartCodeResponseTransfer,
        LocaleTransfer $localeTransfer,
    ): string {
        $messageTransfer = $this->findReasonMessage($cartCodeResponseTransfer);

        if ($messageTransfer === null) {
            return static::ERROR_UNKNOWN;
        }

        return $this->orderIntakeMessageTranslator->translateValidationIssue(
            (string)$messageTransfer->getValue(),
            $messageTransfer->getParameters(),
            $localeTransfer,
        );
    }

    protected function findReasonMessage(CartCodeResponseTransfer $cartCodeResponseTransfer): ?MessageTransfer
    {
        $fallbackMessageTransfers = [];

        foreach ($cartCodeResponseTransfer->getMessages() as $messageTransfer) {
            if ($messageTransfer->getValue() === null) {
                continue;
            }

            if ($messageTransfer->getType() === CartCodesConfig::MESSAGE_TYPE_ERROR) {
                return $messageTransfer;
            }

            $fallbackMessageTransfers[] = $messageTransfer;
        }

        return $fallbackMessageTransfers[0] ?? null;
    }
}
