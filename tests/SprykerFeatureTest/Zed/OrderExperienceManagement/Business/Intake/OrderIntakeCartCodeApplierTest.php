<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Intake;

use ArrayObject;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\CartCodeRequestTransfer;
use Generated\Shared\Transfer\CartCodeResponseTransfer;
use Generated\Shared\Transfer\LocaleTransfer;
use Generated\Shared\Transfer\MessageTransfer;
use Generated\Shared\Transfer\OrderIntakeRequestTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Zed\CartCode\Business\CartCodeFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\OrderIntakeCartCodeApplier;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Translator\OrderIntakeMessageTranslatorInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Business
 * @group Intake
 * @group OrderIntakeCartCodeApplierTest
 * Add your own group annotations below this line
 */
class OrderIntakeCartCodeApplierTest extends Unit
{
    protected const string CART_CODE_ONE = 'GC-1234-5678';

    protected const string CART_CODE_TWO = 'VOUCHER-10OFF';

    protected const string LOCALE_NAME = 'en_US';

    protected const string GLOSSARY_KEY_GIFT_CARD_FAILED = 'cart.giftcard.apply.failed';

    protected const string TRANSLATED_GIFT_CARD_FAILED = 'The gift card is not applicable';

    public function testApplyCartCodesCallsAddCartCodeForEachSubmittedCode(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = (new OrderIntakeRequestTransfer())
            ->setCartCodes([static::CART_CODE_ONE, static::CART_CODE_TWO]);
        $quoteTransfer = new QuoteTransfer();
        $capturedCartCodes = [];

        $cartCodeFacadeMock = $this->createMock(CartCodeFacadeInterface::class);
        $cartCodeFacadeMock->method('addCartCode')->willReturnCallback(
            function (CartCodeRequestTransfer $cartCodeRequestTransfer) use (&$capturedCartCodes): CartCodeResponseTransfer {
                $capturedCartCodes[] = $cartCodeRequestTransfer->getCartCode();

                return (new CartCodeResponseTransfer())
                    ->setIsSuccessful(true)
                    ->setQuote($cartCodeRequestTransfer->getQuoteOrFail());
            },
        );

        // Act
        $this->createApplier($cartCodeFacadeMock)->applyCartCodes(
            $orderIntakeRequestTransfer,
            $quoteTransfer,
            new OrderIntakeResponseTransfer(),
        );

        // Assert
        $this->assertSame([static::CART_CODE_ONE, static::CART_CODE_TWO], $capturedCartCodes);
    }

    public function testApplyCartCodesReturnsTheQuoteFromTheLastSuccessfulCartCodeResponse(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = (new OrderIntakeRequestTransfer())->setCartCodes([static::CART_CODE_ONE]);
        $recalculatedQuoteTransfer = (new QuoteTransfer())->setOrderCustomReference('recalculated');

        $cartCodeFacadeMock = $this->createMock(CartCodeFacadeInterface::class);
        $cartCodeFacadeMock->method('addCartCode')->willReturn(
            (new CartCodeResponseTransfer())
                ->setIsSuccessful(true)
                ->setQuote($recalculatedQuoteTransfer)
                ->setMessages(new ArrayObject([
                    (new MessageTransfer())->setValue('cart.voucher.apply.successful')->setType('success'),
                ])),
        );

        // Act
        $resultQuoteTransfer = $this->createApplier($cartCodeFacadeMock)->applyCartCodes(
            $orderIntakeRequestTransfer,
            new QuoteTransfer(),
            new OrderIntakeResponseTransfer(),
        );

        // Assert
        $this->assertSame($recalculatedQuoteTransfer, $resultQuoteTransfer);
    }

    /**
     * A rejected code must not silently vanish: it has to surface as a validation issue so the caller
     * learns why the order was refused, and the quote must NOT adopt whatever partial state the
     * rejected call produced.
     */
    public function testApplyCartCodesReportsAValidationIssueWhenCartCodeFacadeRejectsACode(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = (new OrderIntakeRequestTransfer())->setCartCodes([static::CART_CODE_ONE]);
        $originalQuoteTransfer = new QuoteTransfer();
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $cartCodeFacadeMock = $this->createMock(CartCodeFacadeInterface::class);
        $cartCodeFacadeMock->method('addCartCode')->willReturn(
            (new CartCodeResponseTransfer())
                ->setIsSuccessful(false)
                ->setMessages(new ArrayObject([(new MessageTransfer())->setValue('Code is not valid.')])),
        );

        // Act
        $resultQuoteTransfer = $this->createApplier($cartCodeFacadeMock)->applyCartCodes(
            $orderIntakeRequestTransfer,
            $originalQuoteTransfer,
            $orderIntakeResponseTransfer,
        );

        // Assert
        $this->assertSame($originalQuoteTransfer, $resultQuoteTransfer);
        $this->assertCount(1, $orderIntakeResponseTransfer->getValidationIssues());

        $validationIssueTransfer = $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0);
        $this->assertSame('cartCodes', $validationIssueTransfer->getField());
        $this->assertSame(static::CART_CODE_ONE, $validationIssueTransfer->getParameters()['%cartCode%']);
        $this->assertSame('Code is not valid.', $validationIssueTransfer->getParameters()['%reason%']);
    }

    public function testApplyCartCodesCallsCartCodeFacadeNotAtAllWhenNoneWereSubmitted(): void
    {
        // Arrange
        $cartCodeFacadeMock = $this->createMock(CartCodeFacadeInterface::class);
        $cartCodeFacadeMock->expects($this->never())->method('addCartCode');

        // Act
        $this->createApplier($cartCodeFacadeMock)->applyCartCodes(
            new OrderIntakeRequestTransfer(),
            new QuoteTransfer(),
            new OrderIntakeResponseTransfer(),
        );
    }

    /**
     * `CartCodeFacade` reports success whenever no plugin objected — which is exactly what an
     * unknown, inactive or non-applicable code produces, because every plugin then returns a null
     * message and nothing flips `isSuccessful`. The intake API must not place an order that quietly
     * ignored a code the caller asked for.
     */
    public function testApplyCartCodesReportsAValidationIssueWhenNoPluginAcknowledgedTheCode(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = (new OrderIntakeRequestTransfer())->setCartCodes([static::CART_CODE_ONE]);
        $originalQuoteTransfer = new QuoteTransfer();
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $cartCodeFacadeMock = $this->createMock(CartCodeFacadeInterface::class);
        $cartCodeFacadeMock->method('addCartCode')->willReturn(
            (new CartCodeResponseTransfer())
                ->setIsSuccessful(true)
                ->setQuote((new QuoteTransfer())->setOrderCustomReference('recalculated'))
                ->setMessages(new ArrayObject()),
        );

        // Act
        $resultQuoteTransfer = $this->createApplier($cartCodeFacadeMock)->applyCartCodes(
            $orderIntakeRequestTransfer,
            $originalQuoteTransfer,
            $orderIntakeResponseTransfer,
        );

        // Assert
        $this->assertSame($originalQuoteTransfer, $resultQuoteTransfer);
        $this->assertCount(1, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertSame('cartCodes', $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0)->getField());
        $this->assertSame(
            static::CART_CODE_ONE,
            $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0)->getParameters()['%cartCode%'],
        );
    }

    /**
     * A message that is not a success acknowledgement (an informational note from an unrelated
     * plugin, say) must not be mistaken for one.
     */
    public function testApplyCartCodesReportsAValidationIssueWhenOnlyNonSuccessMessagesCameBack(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = (new OrderIntakeRequestTransfer())->setCartCodes([static::CART_CODE_ONE]);
        $originalQuoteTransfer = new QuoteTransfer();
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $cartCodeFacadeMock = $this->createMock(CartCodeFacadeInterface::class);
        $cartCodeFacadeMock->method('addCartCode')->willReturn(
            (new CartCodeResponseTransfer())
                ->setIsSuccessful(true)
                ->setQuote(new QuoteTransfer())
                ->setMessages(new ArrayObject([
                    (new MessageTransfer())->setValue('some.informational.note')->setType('info'),
                ])),
        );

        // Act
        $this->createApplier($cartCodeFacadeMock)->applyCartCodes(
            $orderIntakeRequestTransfer,
            $originalQuoteTransfer,
            $orderIntakeResponseTransfer,
        );

        // Assert
        $this->assertCount(1, $orderIntakeResponseTransfer->getValidationIssues());
    }

    /**
     * The happy path must keep working: an acknowledged code adopts the recalculated quote and
     * reports nothing.
     */
    public function testApplyCartCodesAdoptsTheQuoteWhenAPluginAcknowledgedTheCode(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = (new OrderIntakeRequestTransfer())->setCartCodes([static::CART_CODE_ONE]);
        $recalculatedQuoteTransfer = (new QuoteTransfer())->setOrderCustomReference('recalculated');
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $cartCodeFacadeMock = $this->createMock(CartCodeFacadeInterface::class);
        $cartCodeFacadeMock->method('addCartCode')->willReturn(
            (new CartCodeResponseTransfer())
                ->setIsSuccessful(true)
                ->setQuote($recalculatedQuoteTransfer)
                ->setMessages(new ArrayObject([
                    (new MessageTransfer())->setValue('cart.voucher.apply.successful')->setType('success'),
                ])),
        );

        // Act
        $resultQuoteTransfer = $this->createApplier($cartCodeFacadeMock)->applyCartCodes(
            $orderIntakeRequestTransfer,
            new QuoteTransfer(),
            $orderIntakeResponseTransfer,
        );

        // Assert
        $this->assertSame($recalculatedQuoteTransfer, $resultQuoteTransfer);
        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
    }

    /**
     * The default translator stands in for the real one's documented fallback: an unknown glossary
     * key is returned verbatim.
     */
    protected function createApplier(
        CartCodeFacadeInterface $cartCodeFacadeMock,
        ?OrderIntakeMessageTranslatorInterface $orderIntakeMessageTranslatorMock = null,
    ): OrderIntakeCartCodeApplier {
        if ($orderIntakeMessageTranslatorMock === null) {
            $orderIntakeMessageTranslatorMock = $this->createMock(OrderIntakeMessageTranslatorInterface::class);
            $orderIntakeMessageTranslatorMock->method('resolveLocale')
                ->willReturn((new LocaleTransfer())->setLocaleName(static::LOCALE_NAME));
            $orderIntakeMessageTranslatorMock->method('translateValidationIssue')
                ->willReturnCallback(static fn (string $translationKey): string => $translationKey);
        }

        return new OrderIntakeCartCodeApplier($cartCodeFacadeMock, $orderIntakeMessageTranslatorMock);
    }

    /**
     * Plugins hand back the reason as a glossary key (`cart.giftcard.apply.failed`). The intake
     * pipeline translates an issue's own message but substitutes parameters verbatim, so an
     * untranslated key would reach the API caller raw.
     */
    public function testApplyCartCodesTranslatesTheRejectionReasonGlossaryKey(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = (new OrderIntakeRequestTransfer())
            ->setCartCodes([static::CART_CODE_ONE])
            ->setLocaleName(static::LOCALE_NAME);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $cartCodeFacadeMock = $this->createMock(CartCodeFacadeInterface::class);
        $cartCodeFacadeMock->method('addCartCode')->willReturn(
            (new CartCodeResponseTransfer())
                ->setIsSuccessful(false)
                ->setMessages(new ArrayObject([
                    (new MessageTransfer())->setValue(static::GLOSSARY_KEY_GIFT_CARD_FAILED)->setType('error'),
                ])),
        );

        $orderIntakeMessageTranslatorMock = $this->createMock(OrderIntakeMessageTranslatorInterface::class);
        $orderIntakeMessageTranslatorMock->method('resolveLocale')
            ->willReturn((new LocaleTransfer())->setLocaleName(static::LOCALE_NAME));
        $orderIntakeMessageTranslatorMock->method('translateValidationIssue')->willReturnCallback(
            static fn (string $translationKey): string => $translationKey === static::GLOSSARY_KEY_GIFT_CARD_FAILED
                ? static::TRANSLATED_GIFT_CARD_FAILED
                : $translationKey,
        );

        // Act
        $this->createApplier($cartCodeFacadeMock, $orderIntakeMessageTranslatorMock)->applyCartCodes(
            $orderIntakeRequestTransfer,
            new QuoteTransfer(),
            $orderIntakeResponseTransfer,
        );

        // Assert
        $validationIssueTransfer = $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0);
        $this->assertSame(static::TRANSLATED_GIFT_CARD_FAILED, $validationIssueTransfer->getParameters()['%reason%']);
    }

    /**
     * When several plugins left a message behind, the error is the one that explains the rejection.
     */
    public function testApplyCartCodesPrefersTheErrorMessageAsTheRejectionReason(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = (new OrderIntakeRequestTransfer())->setCartCodes([static::CART_CODE_ONE]);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $cartCodeFacadeMock = $this->createMock(CartCodeFacadeInterface::class);
        $cartCodeFacadeMock->method('addCartCode')->willReturn(
            (new CartCodeResponseTransfer())
                ->setIsSuccessful(false)
                ->setMessages(new ArrayObject([
                    (new MessageTransfer())->setValue('some.informational.note')->setType('info'),
                    (new MessageTransfer())->setValue(static::GLOSSARY_KEY_GIFT_CARD_FAILED)->setType('error'),
                ])),
        );

        // Act
        $this->createApplier($cartCodeFacadeMock)->applyCartCodes(
            $orderIntakeRequestTransfer,
            new QuoteTransfer(),
            $orderIntakeResponseTransfer,
        );

        // Assert
        $validationIssueTransfer = $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0);
        $this->assertSame(static::GLOSSARY_KEY_GIFT_CARD_FAILED, $validationIssueTransfer->getParameters()['%reason%']);
    }
}
