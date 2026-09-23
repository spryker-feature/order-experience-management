<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Intake;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CheckoutErrorTransfer;
use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\LocaleTransfer;
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
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\OrderIntakeCartCodeApplierInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\OrderIntakeQuoteBuilderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\OrderIntakeRequestPreparerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\OrderIntakeWriter;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Resolver\OrderIntakePaymentResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Translator\OrderIntakeMessageTranslatorInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Business
 * @group Intake
 * @group OrderIntakeWriterTest
 * Add your own group annotations below this line
 */
class OrderIntakeWriterTest extends Unit
{
    /**
     * What `Customer::register()` puts on the customer during placement. The prefix is the project's
     * own — `Pyz\Zed\Customer\CustomerConfig::getCustomerSequenceNumberPrefix()` returns 'customer',
     * so core's fallback to the store name never applies here.
     */
    protected const string GENERATED_CUSTOMER_REFERENCE = 'customer--51';

    protected const string SUBMITTED_CUSTOMER_REFERENCE = 'I-MADE-THIS-UP';

    protected const string ORDER_REFERENCE = 'DE--355513-144880-8257';

    protected const int ID_SALES_ORDER = 42;

    protected const string LOCALE_NAME = 'de_DE';

    protected const int ID_LOCALE = 5;

    protected const string SKU = '128_29955336';

    protected const string ITEM_UUID = '3fa85f64-5717-4562-b3fc-2c963f66afa6';

    protected const string CHECKOUT_ERROR_GLOSSARY_KEY = 'checkout.error.address.invalid';

    protected const string TRANSLATED_CHECKOUT_ERROR_MESSAGE = 'Die Adresse ist ungültig.';

    /**
     * A new customer's reference is minted by the platform during placement, so the response has to
     * report what the quote ended up carrying — never what arrived in the payload. Placement is
     * staged to assign the reference exactly as `CustomerOrderSaver` does.
     */
    public function testCreateOrderFromIntakeReportsTheReferenceGeneratedDuringPlacement(): void
    {
        // Arrange
        $customerTransfer = new CustomerTransfer();
        $quoteTransfer = (new QuoteTransfer())->setCustomer($customerTransfer);
        $orderIntakeRequestTransfer = $this->createOrderIntakeRequest($customerTransfer);

        // Act
        $orderIntakeResponseTransfer = $this->createWriter($quoteTransfer)->createOrderFromIntake($orderIntakeRequestTransfer);

        // Assert
        $this->assertSame(static::GENERATED_CUSTOMER_REFERENCE, $orderIntakeResponseTransfer->getCustomerReference());
        $this->assertSame(static::ORDER_REFERENCE, $orderIntakeResponseTransfer->getOrderReference());
        $this->assertSame('created', $orderIntakeResponseTransfer->getProcessingResult());
    }

    /**
     * The regression this guards: echoing the payload's own reference back instead of reading the
     * quote. A value the caller invented must never reach the response as if it were the customer's.
     */
    public function testCreateOrderFromIntakeDoesNotEchoASubmittedCustomerReference(): void
    {
        // Arrange
        $customerTransfer = (new CustomerTransfer())->setCustomerReference(static::SUBMITTED_CUSTOMER_REFERENCE);
        $quoteTransfer = (new QuoteTransfer())->setCustomer($customerTransfer);
        $orderIntakeRequestTransfer = $this->createOrderIntakeRequest($customerTransfer);

        // Act
        $orderIntakeResponseTransfer = $this->createWriter($quoteTransfer)->createOrderFromIntake($orderIntakeRequestTransfer);

        // Assert
        $this->assertNotSame(static::SUBMITTED_CUSTOMER_REFERENCE, $orderIntakeResponseTransfer->getCustomerReference());
        $this->assertSame(static::GENERATED_CUSTOMER_REFERENCE, $orderIntakeResponseTransfer->getCustomerReference());
    }

    /**
     * Checkout reports failures as `CheckoutErrorTransfer`s whose `message` is a glossary key; the
     * writer must translate it into the locale the request carries before it reaches the response,
     * rather than passing the raw key through untranslated.
     */
    public function testCreateOrderFromIntakeTranslatesCheckoutErrorMessageUsingRequestLocale(): void
    {
        // Arrange
        $customerTransfer = new CustomerTransfer();
        $quoteTransfer = (new QuoteTransfer())->setCustomer($customerTransfer);
        $orderIntakeRequestTransfer = $this->createOrderIntakeRequest($customerTransfer)->setLocaleName('de_DE');

        $localeTransfer = (new LocaleTransfer())->setLocaleName('de_DE');

        $orderIntakeMessageTranslatorMock = $this->createMock(OrderIntakeMessageTranslatorInterface::class);
        $orderIntakeMessageTranslatorMock->expects($this->once())
            ->method('resolveLocale')
            ->with('de_DE')
            ->willReturn($localeTransfer);
        $orderIntakeMessageTranslatorMock->expects($this->once())
            ->method('translateCheckoutError')
            ->with(static::CHECKOUT_ERROR_GLOSSARY_KEY, ['{{threshold}}' => '€50.00'], $localeTransfer)
            ->willReturn(static::TRANSLATED_CHECKOUT_ERROR_MESSAGE);

        $checkoutFailureResponse = (new CheckoutResponseTransfer())
            ->setIsSuccess(false)
            ->addError(
                (new CheckoutErrorTransfer())
                    ->setMessage(static::CHECKOUT_ERROR_GLOSSARY_KEY)
                    ->setParameters(['{{threshold}}' => '€50.00']),
            );

        // Act
        $orderIntakeResponseTransfer = $this->createWriter($quoteTransfer, $orderIntakeMessageTranslatorMock, $checkoutFailureResponse)
            ->createOrderFromIntake($orderIntakeRequestTransfer);

        // Assert
        $validationIssueTransfers = $orderIntakeResponseTransfer->getValidationIssues();
        $this->assertCount(1, $validationIssueTransfers);
        $this->assertSame(
            static::TRANSLATED_CHECKOUT_ERROR_MESSAGE,
            $validationIssueTransfers->offsetGet(0)->getMessage(),
        );
    }

    /**
     * `$localeName` is the same for every error in one checkout response, so the locale must be
     * resolved once and reused — not re-resolved per error via `OrderIntakeMessageTranslator::resolveLocale()`.
     */
    public function testCreateOrderFromIntakeResolvesTheLocaleOnceForMultipleCheckoutErrors(): void
    {
        // Arrange
        $customerTransfer = new CustomerTransfer();
        $quoteTransfer = (new QuoteTransfer())->setCustomer($customerTransfer);
        $orderIntakeRequestTransfer = $this->createOrderIntakeRequest($customerTransfer)->setLocaleName('de_DE');

        $localeTransfer = (new LocaleTransfer())->setLocaleName('de_DE');

        $orderIntakeMessageTranslatorMock = $this->createMock(OrderIntakeMessageTranslatorInterface::class);
        $orderIntakeMessageTranslatorMock->expects($this->once())
            ->method('resolveLocale')
            ->with('de_DE')
            ->willReturn($localeTransfer);
        $orderIntakeMessageTranslatorMock->expects($this->exactly(2))
            ->method('translateCheckoutError')
            ->willReturn(static::TRANSLATED_CHECKOUT_ERROR_MESSAGE);

        $checkoutFailureResponse = (new CheckoutResponseTransfer())
            ->setIsSuccess(false)
            ->addError((new CheckoutErrorTransfer())->setMessage(static::CHECKOUT_ERROR_GLOSSARY_KEY))
            ->addError((new CheckoutErrorTransfer())->setMessage('checkout.error.another_issue'));

        // Act
        $orderIntakeResponseTransfer = $this->createWriter($quoteTransfer, $orderIntakeMessageTranslatorMock, $checkoutFailureResponse)
            ->createOrderFromIntake($orderIntakeRequestTransfer);

        // Assert
        $this->assertCount(2, $orderIntakeResponseTransfer->getValidationIssues());
    }

    /**
     * `SalesOrderSaver::addLocale()` always writes the ambient/default locale, never anything
     * submitted — this is the one place that overrides it, and only once the order already exists.
     */
    public function testCreateOrderFromIntakeAppliesTheSubmittedLocaleAfterPlacement(): void
    {
        // Arrange
        $customerTransfer = new CustomerTransfer();
        $quoteTransfer = (new QuoteTransfer())->setCustomer($customerTransfer);
        $orderIntakeRequestTransfer = $this->createOrderIntakeRequest($customerTransfer)
            ->setOrderLocaleName(static::LOCALE_NAME);

        $localeFacadeMock = $this->createMock(LocaleFacadeInterface::class);
        $localeFacadeMock->method('getLocale')
            ->with(static::LOCALE_NAME)
            ->willReturn((new LocaleTransfer())->setIdLocale(static::ID_LOCALE)->setLocaleName(static::LOCALE_NAME));

        $salesFacadeMock = $this->createMock(SalesFacadeInterface::class);
        $salesFacadeMock->expects($this->once())
            ->method('updateOrder')
            ->with(
                $this->callback(fn (OrderTransfer $orderTransfer): bool => $orderTransfer->getFkLocale() === static::ID_LOCALE),
                static::ID_SALES_ORDER,
            )
            ->willReturn(true);

        // Act
        $this->createWriter($quoteTransfer, null, null, $salesFacadeMock, $localeFacadeMock)
            ->createOrderFromIntake($orderIntakeRequestTransfer);
    }

    /**
     * `SalesFacade::updateOrder()` reports failure as `false`. The order itself is already committed
     * by then, so the result stays `created` — but the caller is told the locale it asked for did not
     * land, instead of the patch failing silently behind a successful response.
     */
    public function testCreateOrderFromIntakeReportsAnIssueWhenTheOrderLocaleCouldNotBeApplied(): void
    {
        // Arrange
        $customerTransfer = new CustomerTransfer();
        $quoteTransfer = (new QuoteTransfer())->setCustomer($customerTransfer);
        $orderIntakeRequestTransfer = $this->createOrderIntakeRequest($customerTransfer)
            ->setOrderLocaleName(static::LOCALE_NAME);

        $localeFacadeMock = $this->createMock(LocaleFacadeInterface::class);
        $localeFacadeMock->method('getLocale')
            ->with(static::LOCALE_NAME)
            ->willReturn((new LocaleTransfer())->setIdLocale(static::ID_LOCALE)->setLocaleName(static::LOCALE_NAME));

        $salesFacadeMock = $this->createMock(SalesFacadeInterface::class);
        $salesFacadeMock->method('updateOrder')->willReturn(false);

        // Act
        $orderIntakeResponseTransfer = $this->createWriter($quoteTransfer, null, null, $salesFacadeMock, $localeFacadeMock)
            ->createOrderFromIntake($orderIntakeRequestTransfer);

        // Assert
        $this->assertTrue($orderIntakeResponseTransfer->getIsSuccessful());
        $this->assertCount(1, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertSame(
            'orderLocaleName',
            $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0)->getField(),
        );
    }

    public function testCreateOrderFromIntakeSkipsUpdatingTheOrderLocaleWhenNoneWasSubmitted(): void
    {
        // Arrange
        $customerTransfer = new CustomerTransfer();
        $quoteTransfer = (new QuoteTransfer())->setCustomer($customerTransfer);
        $orderIntakeRequestTransfer = $this->createOrderIntakeRequest($customerTransfer);

        $salesFacadeMock = $this->createMock(SalesFacadeInterface::class);
        $salesFacadeMock->expects($this->never())->method('updateOrder');

        // Act
        $this->createWriter($quoteTransfer, null, null, $salesFacadeMock)
            ->createOrderFromIntake($orderIntakeRequestTransfer);
    }

    public function testCreateOrderFromIntakeStopsBeforeQuoteBuildingWhenRequestPreparationReportedAnIssue(): void
    {
        // Arrange
        $customerTransfer = new CustomerTransfer();
        $quoteTransfer = (new QuoteTransfer())->setCustomer($customerTransfer);
        $orderIntakeRequestTransfer = $this->createOrderIntakeRequest($customerTransfer);

        $orderIntakeRequestPreparerMock = $this->createMock(OrderIntakeRequestPreparerInterface::class);
        $orderIntakeRequestPreparerMock->method('prepareRequest')->willReturnCallback(
            function (
                OrderIntakeRequestTransfer $requestTransfer,
                OrderIntakeResponseTransfer $responseTransfer,
            ): OrderIntakeRequestTransfer {
                $responseTransfer->addValidationIssue(new OrderIntakeValidationIssueTransfer());

                return $requestTransfer;
            },
        );

        $orderIntakeQuoteBuilderMock = $this->createMock(OrderIntakeQuoteBuilderInterface::class);
        $orderIntakeQuoteBuilderMock->expects($this->never())->method('buildQuote');

        $calculationFacadeMock = $this->createMock(CalculationFacadeInterface::class);
        $calculationFacadeMock->expects($this->never())->method('recalculateQuote');

        $checkoutFacadeMock = $this->createMock(CheckoutFacadeInterface::class);
        $checkoutFacadeMock->expects($this->never())->method('placeOrder');

        // Act
        $orderIntakeResponseTransfer = $this->createWriter($quoteTransfer, null, null, null, null, [
            'orderIntakeRequestPreparer' => $orderIntakeRequestPreparerMock,
            'orderIntakeQuoteBuilder' => $orderIntakeQuoteBuilderMock,
            'calculationFacade' => $calculationFacadeMock,
            'checkoutFacade' => $checkoutFacadeMock,
        ])->createOrderFromIntake($orderIntakeRequestTransfer);

        // Assert
        $this->assertFalse($orderIntakeResponseTransfer->getIsSuccessful());
        $this->assertSame('rejected', $orderIntakeResponseTransfer->getProcessingResult());
    }

    public function testCreateOrderFromIntakeStopsBeforeCartCodesWhenQuoteBuildingReportedAnIssue(): void
    {
        // Arrange
        $customerTransfer = new CustomerTransfer();
        $quoteTransfer = (new QuoteTransfer())->setCustomer($customerTransfer);
        $orderIntakeRequestTransfer = $this->createOrderIntakeRequest($customerTransfer);

        $orderIntakeQuoteBuilderMock = $this->createMock(OrderIntakeQuoteBuilderInterface::class);
        $orderIntakeQuoteBuilderMock->method('buildQuote')->willReturnCallback(
            function (
                OrderIntakeRequestTransfer $requestTransfer,
                OrderIntakeResponseTransfer $responseTransfer,
            ) use ($quoteTransfer): QuoteTransfer {
                $responseTransfer->addValidationIssue(new OrderIntakeValidationIssueTransfer());

                return $quoteTransfer;
            },
        );

        $orderIntakeCartCodeApplierMock = $this->createMock(OrderIntakeCartCodeApplierInterface::class);
        $orderIntakeCartCodeApplierMock->expects($this->never())->method('applyCartCodes');

        $calculationFacadeMock = $this->createMock(CalculationFacadeInterface::class);
        $calculationFacadeMock->expects($this->never())->method('recalculateQuote');

        $checkoutFacadeMock = $this->createMock(CheckoutFacadeInterface::class);
        $checkoutFacadeMock->expects($this->never())->method('placeOrder');

        // Act
        $orderIntakeResponseTransfer = $this->createWriter($quoteTransfer, null, null, null, null, [
            'orderIntakeQuoteBuilder' => $orderIntakeQuoteBuilderMock,
            'orderIntakeCartCodeApplier' => $orderIntakeCartCodeApplierMock,
            'calculationFacade' => $calculationFacadeMock,
            'checkoutFacade' => $checkoutFacadeMock,
        ])->createOrderFromIntake($orderIntakeRequestTransfer);

        // Assert
        $this->assertFalse($orderIntakeResponseTransfer->getIsSuccessful());
        $this->assertSame('rejected', $orderIntakeResponseTransfer->getProcessingResult());
    }

    /**
     * `OrderIntakeCartCodeApplier::applyCartCodes()` runs immediately before this gate; once it has
     * recorded an issue, calculation and checkout must never run.
     */
    public function testCreateOrderFromIntakeStopsBeforeRecalculationWhenCartCodeApplicationReportedAnIssue(): void
    {
        // Arrange
        $customerTransfer = new CustomerTransfer();
        $quoteTransfer = (new QuoteTransfer())->setCustomer($customerTransfer);
        $orderIntakeRequestTransfer = $this->createOrderIntakeRequest($customerTransfer);

        $orderIntakeCartCodeApplierMock = $this->createMock(OrderIntakeCartCodeApplierInterface::class);
        $orderIntakeCartCodeApplierMock->method('applyCartCodes')->willReturnCallback(
            function (
                OrderIntakeRequestTransfer $requestTransfer,
                QuoteTransfer $appliedQuoteTransfer,
                OrderIntakeResponseTransfer $responseTransfer,
            ) use ($quoteTransfer): QuoteTransfer {
                $responseTransfer->addValidationIssue(new OrderIntakeValidationIssueTransfer());

                return $quoteTransfer;
            },
        );

        $calculationFacadeMock = $this->createMock(CalculationFacadeInterface::class);
        $calculationFacadeMock->expects($this->never())->method('recalculateQuote');

        $checkoutFacadeMock = $this->createMock(CheckoutFacadeInterface::class);
        $checkoutFacadeMock->expects($this->never())->method('placeOrder');

        // Act
        $orderIntakeResponseTransfer = $this->createWriter($quoteTransfer, null, null, null, null, [
            'orderIntakeCartCodeApplier' => $orderIntakeCartCodeApplierMock,
            'calculationFacade' => $calculationFacadeMock,
            'checkoutFacade' => $checkoutFacadeMock,
        ])->createOrderFromIntake($orderIntakeRequestTransfer);

        // Assert
        $this->assertFalse($orderIntakeResponseTransfer->getIsSuccessful());
        $this->assertSame('rejected', $orderIntakeResponseTransfer->getProcessingResult());
    }

    /**
     * `OrderIntakePaymentResolver::resolvePayment()` runs immediately before this gate — the fourth
     * `hasValidationIssues()` short-circuit, reached after `recalculateQuote()` already ran. Once
     * payment resolution has recorded an issue, checkout must never run.
     */
    public function testCreateOrderFromIntakeStopsBeforeCheckoutWhenPaymentResolutionReportedAnIssue(): void
    {
        // Arrange
        $customerTransfer = new CustomerTransfer();
        $quoteTransfer = (new QuoteTransfer())->setCustomer($customerTransfer);
        $orderIntakeRequestTransfer = $this->createOrderIntakeRequest($customerTransfer);

        $orderIntakePaymentResolverMock = $this->createMock(OrderIntakePaymentResolverInterface::class);
        $orderIntakePaymentResolverMock->method('resolvePayment')->willReturnCallback(
            function (
                QuoteTransfer $resolvedQuoteTransfer,
                OrderIntakeResponseTransfer $responseTransfer,
            ) use ($quoteTransfer): QuoteTransfer {
                $responseTransfer->addValidationIssue(new OrderIntakeValidationIssueTransfer());

                return $quoteTransfer;
            },
        );

        $calculationFacadeMock = $this->createMock(CalculationFacadeInterface::class);
        $calculationFacadeMock->expects($this->once())->method('recalculateQuote')->willReturn($quoteTransfer);

        $checkoutFacadeMock = $this->createMock(CheckoutFacadeInterface::class);
        $checkoutFacadeMock->expects($this->never())->method('placeOrder');

        // Act
        $orderIntakeResponseTransfer = $this->createWriter($quoteTransfer, null, null, null, null, [
            'orderIntakePaymentResolver' => $orderIntakePaymentResolverMock,
            'calculationFacade' => $calculationFacadeMock,
            'checkoutFacade' => $checkoutFacadeMock,
        ])->createOrderFromIntake($orderIntakeRequestTransfer);

        // Assert
        $this->assertFalse($orderIntakeResponseTransfer->getIsSuccessful());
        $this->assertSame('rejected', $orderIntakeResponseTransfer->getProcessingResult());
    }

    protected function createOrderIntakeRequest(CustomerTransfer $customerTransfer): OrderIntakeRequestTransfer
    {
        return (new OrderIntakeRequestTransfer())
            ->setCustomer($customerTransfer)
            ->addItem((new OrderIntakeItemTransfer())->setSku(static::SKU)->setQuantity(1));
    }

    /**
     * Everything the writer orchestrates is stubbed; only the writer's own sequencing is under test.
     * `placeOrder` assigns the customer reference onto the quote's customer, which is what
     * `CustomerOrderSaver` does when it registers a new customer mid-placement.
     *
     * @param array<string, object> $collaboratorOverrides
     */
    protected function createWriter(
        QuoteTransfer $quoteTransfer,
        ?OrderIntakeMessageTranslatorInterface $orderIntakeMessageTranslator = null,
        ?CheckoutResponseTransfer $checkoutFailureResponse = null,
        ?SalesFacadeInterface $salesFacadeMock = null,
        ?LocaleFacadeInterface $localeFacadeMock = null,
        array $collaboratorOverrides = [],
    ): OrderIntakeWriter {
        $orderIntakeRequestPreparerMock = $this->createMock(OrderIntakeRequestPreparerInterface::class);
        $orderIntakeRequestPreparerMock->method('prepareRequest')->willReturnArgument(0);

        $orderIntakeQuoteBuilderMock = $this->createMock(OrderIntakeQuoteBuilderInterface::class);
        $orderIntakeQuoteBuilderMock->method('buildQuote')->willReturn($quoteTransfer);

        $orderIntakeCartCodeApplierMock = $this->createMock(OrderIntakeCartCodeApplierInterface::class);
        $orderIntakeCartCodeApplierMock->method('applyCartCodes')->willReturn($quoteTransfer);

        $calculationFacadeMock = $this->createMock(CalculationFacadeInterface::class);
        $calculationFacadeMock->method('recalculateQuote')->willReturn($quoteTransfer);

        $orderIntakePaymentResolverMock = $this->createMock(OrderIntakePaymentResolverInterface::class);
        $orderIntakePaymentResolverMock->method('resolvePayment')->willReturnArgument(0);

        $checkoutFacadeMock = $this->createMock(CheckoutFacadeInterface::class);
        $checkoutFacadeMock->method('placeOrder')
            ->willReturnCallback(function (QuoteTransfer $placedQuoteTransfer) use ($checkoutFailureResponse): CheckoutResponseTransfer {
                if ($checkoutFailureResponse !== null) {
                    return $checkoutFailureResponse;
                }

                $placedQuoteTransfer->getCustomerOrFail()->setCustomerReference(static::GENERATED_CUSTOMER_REFERENCE);

                return (new CheckoutResponseTransfer())
                    ->setIsSuccess(true)
                    ->setSaveOrder(
                        (new SaveOrderTransfer())
                            ->setIdSalesOrder(static::ID_SALES_ORDER)
                            ->setOrderReference(static::ORDER_REFERENCE)
                            ->addOrderItem(
                                (new ItemTransfer())
                                    ->setSku(static::SKU)
                                    ->setUuid(static::ITEM_UUID),
                            ),
                    );
            });

        // Keyed by the OrderIntakeWriter constructor's parameter names, in constructor order, so a
        // single test can swap in one collaborator (e.g. to make it record a validation issue, or to
        // assert a downstream collaborator is never reached) without re-stating every other mock.
        $collaborators = [
            'orderIntakeRequestPreparer' => $orderIntakeRequestPreparerMock,
            'orderIntakeQuoteBuilder' => $orderIntakeQuoteBuilderMock,
            'orderIntakeCartCodeApplier' => $orderIntakeCartCodeApplierMock,
            'calculationFacade' => $calculationFacadeMock,
            'orderIntakePaymentResolver' => $orderIntakePaymentResolverMock,
            'checkoutFacade' => $checkoutFacadeMock,
            'orderIntakeMessageTranslator' => $orderIntakeMessageTranslator ?? $this->createMock(OrderIntakeMessageTranslatorInterface::class),
            'salesFacade' => $salesFacadeMock ?? $this->createMock(SalesFacadeInterface::class),
            'localeFacade' => $localeFacadeMock ?? $this->createMock(LocaleFacadeInterface::class),
        ];

        return new OrderIntakeWriter(...array_replace($collaborators, $collaboratorOverrides));
    }
}
