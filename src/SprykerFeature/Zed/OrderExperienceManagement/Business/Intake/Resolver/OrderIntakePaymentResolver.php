<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Resolver;

use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\OrderIntakeValidationIssueTransfer;
use Generated\Shared\Transfer\PaymentMethodTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Zed\Payment\Business\PaymentFacadeInterface;

/**
 * Confirms the submitted `paymentMethod` names a real, available payment method and resolves its provider.
 */
class OrderIntakePaymentResolver implements OrderIntakePaymentResolverInterface
{
    protected const string FIELD_PAYMENT_METHOD = 'paymentMethod';

    protected const string MESSAGE_PAYMENT_METHOD_REQUIRED = 'Payment method is required.';

    protected const string MESSAGE_PAYMENT_METHOD_NOT_FOUND = 'Payment method "%paymentMethod%" was not found or is not available for %store% %currency%.';

    public function __construct(
        protected readonly PaymentFacadeInterface $paymentFacade,
    ) {
    }

    public function resolvePayment(
        QuoteTransfer $quoteTransfer,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
    ): QuoteTransfer {
        $paymentMethodKey = $quoteTransfer->getPayment()?->getPaymentSelection();

        if ($paymentMethodKey === null || $paymentMethodKey === '') {
            $orderIntakeResponseTransfer->addValidationIssue(
                (new OrderIntakeValidationIssueTransfer())
                    ->setField(static::FIELD_PAYMENT_METHOD)
                    ->setMessage(static::MESSAGE_PAYMENT_METHOD_REQUIRED),
            );

            return $quoteTransfer;
        }

        $paymentMethodTransfer = $this->findAvailablePaymentMethod($quoteTransfer, $paymentMethodKey);

        if ($paymentMethodTransfer === null) {
            $orderIntakeResponseTransfer->addValidationIssue(
                (new OrderIntakeValidationIssueTransfer())
                    ->setField(static::FIELD_PAYMENT_METHOD)
                    ->setMessage(static::MESSAGE_PAYMENT_METHOD_NOT_FOUND)
                    ->setParameters([
                        '%paymentMethod%' => $paymentMethodKey,
                        '%store%' => (string)$quoteTransfer->getStore()?->getName(),
                        '%currency%' => (string)$quoteTransfer->getCurrency()?->getCode(),
                    ]),
            );

            return $quoteTransfer;
        }

        return $this->applyResolvedPaymentMethod($quoteTransfer, $paymentMethodTransfer);
    }

    protected function findAvailablePaymentMethod(
        QuoteTransfer $quoteTransfer,
        string $paymentMethodKey,
    ): ?PaymentMethodTransfer {
        $availablePaymentMethodsTransfer = $this->paymentFacade->getAvailableMethods($quoteTransfer);

        foreach ($availablePaymentMethodsTransfer->getMethods() as $paymentMethodTransfer) {
            if ($paymentMethodTransfer->getPaymentMethodKey() === $paymentMethodKey) {
                return $paymentMethodTransfer;
            }
        }

        return null;
    }

    protected function applyResolvedPaymentMethod(
        QuoteTransfer $quoteTransfer,
        PaymentMethodTransfer $paymentMethodTransfer,
    ): QuoteTransfer {
        $paymentMethodKey = $paymentMethodTransfer->getPaymentMethodKeyOrFail();
        $paymentProviderKey = $paymentMethodTransfer->getPaymentProvider()?->getPaymentProviderKey() ?? $paymentMethodKey;

        $quoteTransfer->getPaymentOrFail()
            ->setPaymentSelection($paymentMethodKey)
            ->setPaymentProvider($paymentProviderKey)
            ->setPaymentProviderName($paymentProviderKey);

        foreach ($quoteTransfer->getPayments() as $paymentTransfer) {
            if ($paymentTransfer->getGiftCard() !== null) {
                continue;
            }

            $paymentTransfer
                ->setPaymentSelection($paymentMethodKey)
                ->setPaymentProvider($paymentProviderKey)
                ->setPaymentProviderName($paymentProviderKey);
        }

        return $quoteTransfer;
    }
}
