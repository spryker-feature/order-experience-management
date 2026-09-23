<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Intake\Resolver;

use ArrayObject;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\GiftCardTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\PaymentMethodsTransfer;
use Generated\Shared\Transfer\PaymentMethodTransfer;
use Generated\Shared\Transfer\PaymentProviderTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Spryker\Zed\Payment\Business\PaymentFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Resolver\OrderIntakePaymentResolver;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Business
 * @group Intake
 * @group Resolver
 * @group OrderIntakePaymentResolverTest
 * Add your own group annotations below this line
 */
class OrderIntakePaymentResolverTest extends Unit
{
    protected const string STORE_NAME = 'DE';

    protected const string CURRENCY_CODE = 'EUR';

    protected const string PAYMENT_METHOD_KEY = 'dummyPaymentInvoice';

    protected const string PAYMENT_PROVIDER_KEY = 'DummyPayment';

    protected const string NOPAYMENT_KEY = 'Nopayment';

    public function testResolvePaymentAddsAValidationIssueWhenNoMethodWasSubmitted(): void
    {
        // Arrange
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $paymentFacadeMock = $this->createMock(PaymentFacadeInterface::class);
        $paymentFacadeMock->expects($this->never())->method('getAvailableMethods');

        // Act
        $quoteTransfer = (new OrderIntakePaymentResolver($paymentFacadeMock))->resolvePayment(
            $this->createQuote(new PaymentTransfer()),
            $orderIntakeResponseTransfer,
        );

        // Assert
        $validationIssueTransfer = $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0);

        $this->assertCount(1, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertSame('paymentMethod', $validationIssueTransfer->getField());
        $this->assertNull($quoteTransfer->getPaymentOrFail()->getPaymentProvider());
    }

    /**
     * An unavailable key must be caught here with a clear per-field message, not left to surface
     * later as the checkout pre-condition's opaque `checkout.payment_method.invalid`.
     */
    public function testResolvePaymentReportsAMethodThatIsNotAvailable(): void
    {
        // Arrange
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $paymentFacadeMock = $this->createMock(PaymentFacadeInterface::class);
        $paymentFacadeMock->method('getAvailableMethods')->willReturn(new PaymentMethodsTransfer());

        // Act
        $quoteTransfer = (new OrderIntakePaymentResolver($paymentFacadeMock))->resolvePayment(
            $this->createQuote((new PaymentTransfer())->setPaymentSelection(static::PAYMENT_METHOD_KEY)),
            $orderIntakeResponseTransfer,
        );

        // Assert
        $validationIssueTransfer = $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0);

        $this->assertCount(1, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertSame('paymentMethod', $validationIssueTransfer->getField());
        $this->assertSame(static::PAYMENT_METHOD_KEY, $validationIssueTransfer->getParameters()['%paymentMethod%']);
        $this->assertSame(static::STORE_NAME, $validationIssueTransfer->getParameters()['%store%']);
        $this->assertSame(static::CURRENCY_CODE, $validationIssueTransfer->getParameters()['%currency%']);
        $this->assertNull($quoteTransfer->getPaymentOrFail()->getPaymentProvider());
    }

    /**
     * A persisted method carries its own provider — the case that covers everything except Nopayment.
     */
    public function testResolvePaymentResolvesTheProviderFromAPersistedMethod(): void
    {
        // Arrange
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $paymentMethodTransfer = (new PaymentMethodTransfer())
            ->setPaymentMethodKey(static::PAYMENT_METHOD_KEY)
            ->setPaymentProvider((new PaymentProviderTransfer())->setPaymentProviderKey(static::PAYMENT_PROVIDER_KEY));

        $paymentFacadeMock = $this->createMock(PaymentFacadeInterface::class);
        $paymentFacadeMock->method('getAvailableMethods')
            ->willReturn((new PaymentMethodsTransfer())->addMethod($paymentMethodTransfer));

        // Act
        $quoteTransfer = (new OrderIntakePaymentResolver($paymentFacadeMock))->resolvePayment(
            $this->createQuote((new PaymentTransfer())->setPaymentSelection(static::PAYMENT_METHOD_KEY)),
            $orderIntakeResponseTransfer,
        );

        // Assert
        $paymentTransfer = $quoteTransfer->getPaymentOrFail();

        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertSame(static::PAYMENT_METHOD_KEY, $paymentTransfer->getPaymentSelection());
        $this->assertSame(static::PAYMENT_PROVIDER_KEY, $paymentTransfer->getPaymentProvider());
        $this->assertSame(static::PAYMENT_PROVIDER_KEY, $paymentTransfer->getPaymentProviderName());
    }

    /**
     * Nopayment is registered only via PAYMENT_METHOD_STATEMACHINE_MAPPING, never persisted, and
     * carries no nested provider — its own method key IS the provider key by construction (a
     * single-method provider has nothing to disambiguate).
     */
    public function testResolvePaymentResolvesTheProviderFromTheMethodKeyWhenNoProviderIsNested(): void
    {
        // Arrange
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $paymentMethodTransfer = (new PaymentMethodTransfer())->setPaymentMethodKey(static::NOPAYMENT_KEY);

        $paymentFacadeMock = $this->createMock(PaymentFacadeInterface::class);
        $paymentFacadeMock->method('getAvailableMethods')
            ->willReturn((new PaymentMethodsTransfer())->addMethod($paymentMethodTransfer));

        // Act
        $quoteTransfer = (new OrderIntakePaymentResolver($paymentFacadeMock))->resolvePayment(
            $this->createQuote((new PaymentTransfer())->setPaymentSelection(static::NOPAYMENT_KEY)),
            $orderIntakeResponseTransfer,
        );

        // Assert
        $paymentTransfer = $quoteTransfer->getPaymentOrFail();

        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertSame(static::NOPAYMENT_KEY, $paymentTransfer->getPaymentProvider());
        $this->assertSame(static::NOPAYMENT_KEY, $paymentTransfer->getPaymentProviderName());
    }

    /**
     * `GiftCardCalculator` (run by `CalculationFacade::recalculateQuote()` just before this step)
     * appends one `PaymentTransfer` per applicable gift card, carrying its OWN provider — never the
     * caller's chosen payment method. Overwriting it here, as the unqualified loop over `payments`
     * used to, makes `GiftCardCheckoutPreConditionPlugin::hasGiftCardPayments()` stop recognizing it
     * as a gift-card payment and the redemption silently disappears.
     */
    public function testResolvePaymentDoesNotOverwriteAGiftCardPaymentEntry(): void
    {
        // Arrange
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $paymentMethodTransfer = (new PaymentMethodTransfer())
            ->setPaymentMethodKey(static::PAYMENT_METHOD_KEY)
            ->setPaymentProvider((new PaymentProviderTransfer())->setPaymentProviderKey(static::PAYMENT_PROVIDER_KEY));

        $paymentFacadeMock = $this->createMock(PaymentFacadeInterface::class);
        $paymentFacadeMock->method('getAvailableMethods')
            ->willReturn((new PaymentMethodsTransfer())->addMethod($paymentMethodTransfer));

        $mainPaymentTransfer = (new PaymentTransfer())->setPaymentSelection(static::PAYMENT_METHOD_KEY);
        $giftCardPaymentTransfer = (new PaymentTransfer())
            ->setPaymentProvider('GiftCard')
            ->setPaymentSelection('GiftCard')
            ->setGiftCard(new GiftCardTransfer());

        $quoteTransfer = $this->createQuote($mainPaymentTransfer)
            ->setPayments(new ArrayObject([$mainPaymentTransfer, $giftCardPaymentTransfer]));

        // Act
        $quoteTransfer = (new OrderIntakePaymentResolver($paymentFacadeMock))->resolvePayment(
            $quoteTransfer,
            $orderIntakeResponseTransfer,
        );

        // Assert
        $this->assertSame('GiftCard', $giftCardPaymentTransfer->getPaymentProvider());
        $this->assertSame(static::PAYMENT_PROVIDER_KEY, $quoteTransfer->getPayments()->offsetGet(0)->getPaymentProvider());
    }

    protected function createQuote(PaymentTransfer $paymentTransfer): QuoteTransfer
    {
        return (new QuoteTransfer())
            ->setPayment($paymentTransfer)
            ->setStore((new StoreTransfer())->setName(static::STORE_NAME))
            ->setCurrency((new CurrencyTransfer())->setCode(static::CURRENCY_CODE));
    }
}
