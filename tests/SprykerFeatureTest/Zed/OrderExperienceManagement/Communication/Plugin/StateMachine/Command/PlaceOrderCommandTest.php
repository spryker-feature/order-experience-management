<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Command;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CheckoutErrorTransfer;
use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\CustomerResponseTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteErrorTransfer;
use Generated\Shared\Transfer\QuoteResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\StateMachineItemTransfer;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleHistoryQuery;
use RuntimeException;
use Spryker\Zed\Cart\Business\CartFacadeInterface;
use Spryker\Zed\Checkout\Business\CheckoutFacadeInterface;
use Spryker\Zed\Customer\Business\CustomerFacadeInterface;
use Spryker\Zed\Kernel\Locator;
use Spryker\Zed\Mail\Business\MailFacadeInterface;
use Spryker\Zed\Payment\Business\PaymentFacadeInterface;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Command\PlaceOrderCommandPlugin;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementDependencyProvider;
use SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester;

/**
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Communication
 * @group Plugin
 * @group StateMachine
 * @group Command
 * @group PlaceOrderCommandPluginTest
 */
class PlaceOrderCommandTest extends Unit
{
    protected const string SKU = 'SKU-1';

    protected const int QUANTITY = 2;

    protected const string CUSTOMER_REFERENCE = 'DE--42';

    protected const string CUSTOMER_EMAIL = 'buyer@example.com';

    /**
     * @uses \SprykerFeature\Zed\OrderExperienceManagement\Business\Order\PlacementCheckoutResponseBuilder::GLOSSARY_KEY_ITEMS_NOT_PURCHASABLE
     */
    protected const string GLOSSARY_KEY_ITEMS_NOT_PURCHASABLE = 'recurring_orders.error.items_not_purchasable';

    protected const string SKU_2 = 'SKU-2';

    protected const int QUANTITY_SKU_2 = 3;

    protected const int QUANTITY_ABUNDANT = 999;

    protected const string MESSENGER_KEY_STALE = 'test.recurring_orders.reload.stale';

    protected const string MESSENGER_KEY_FRESH = 'test.recurring_orders.reload.fresh';

    protected OrderExperienceManagementBusinessTester $tester;

    public function testGetNameReturnsExpectedCommandName(): void
    {
        $this->assertSame('RecurringOrders/PlaceOrder', (new PlaceOrderCommandPlugin())->getName());
    }

    public function testRunPlacesOrderRecalculatesPaymentAndWritesPlacedHistoryOnSuccess(): void
    {
        // Arrange
        $scheduleTransfer = $this->haveScheduleWithItem();

        $cartFacadeMock = $this->createCartFacadeMockReturningReloadedQuote();
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_CART, $cartFacadeMock);

        $checkoutFacadeMock = $this->createMock(CheckoutFacadeInterface::class);
        $checkoutFacadeMock->method('placeOrder')->willReturn((new CheckoutResponseTransfer())->setIsSuccess(true));
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_CHECKOUT, $checkoutFacadeMock);

        $paymentFacadeMock = $this->createMock(PaymentFacadeInterface::class);
        $paymentFacadeMock->expects($this->once())->method('recalculatePayments');
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_PAYMENT, $paymentFacadeMock);

        $mailFacadeMock = $this->createMock(MailFacadeInterface::class);
        $mailFacadeMock->expects($this->never())->method('handleMail');
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_MAIL, $mailFacadeMock);

        // Act
        $this->createCommand()->run(
            (new StateMachineItemTransfer())->setIdentifier($scheduleTransfer->getIdRecurringScheduleOrFail()),
        );

        // Assert
        $historyEntity = SpyRecurringScheduleHistoryQuery::create()
            ->filterByFkRecurringSchedule($scheduleTransfer->getIdRecurringScheduleOrFail())
            ->findOne();
        $this->assertNotNull($historyEntity);
        $this->assertSame(SharedOrderExperienceManagementConfig::HISTORY_EVENT_TYPE_PLACED, $historyEntity->getEventType());
        $this->assertNull($historyEntity->getDetail());
    }

    public function testRunReturnsFailureWithoutHistoryOrNotificationWhenScheduleNotFound(): void
    {
        // Arrange
        $mailFacadeMock = $this->createMock(MailFacadeInterface::class);
        $mailFacadeMock->expects($this->never())->method('handleMail');
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_MAIL, $mailFacadeMock);

        // Act
        $this->createCommand()->run(
            (new StateMachineItemTransfer())->setIdentifier(0),
        );
    }

    public function testRunWritesFailedHistoryAndNotifiesWhenQuoteReloadFails(): void
    {
        // Arrange
        $scheduleTransfer = $this->haveScheduleWithItem([RecurringScheduleTransfer::CUSTOMER_REFERENCE => static::CUSTOMER_REFERENCE]);

        $cartFacadeMock = $this->createMock(CartFacadeInterface::class);
        $cartFacadeMock->method('reloadItemsInQuote')->willReturn(
            (new QuoteResponseTransfer())->setIsSuccessful(false)->addError((new QuoteErrorTransfer())->setMessage('reload failed')),
        );
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_CART, $cartFacadeMock);

        $checkoutFacadeMock = $this->createMock(CheckoutFacadeInterface::class);
        $checkoutFacadeMock->expects($this->never())->method('placeOrder');
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_CHECKOUT, $checkoutFacadeMock);

        $paymentFacadeMock = $this->createMock(PaymentFacadeInterface::class);
        $paymentFacadeMock->expects($this->never())->method('recalculatePayments');
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_PAYMENT, $paymentFacadeMock);

        $mailFacadeMock = $this->createMock(MailFacadeInterface::class);
        $mailFacadeMock->expects($this->once())->method('handleMail');
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_MAIL, $mailFacadeMock);

        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_CUSTOMER, $this->createCustomerFacadeMockReturningBuyer());

        // Act
        $this->createCommand()->run(
            (new StateMachineItemTransfer())->setIdentifier($scheduleTransfer->getIdRecurringScheduleOrFail()),
        );

        // Assert
        $historyEntity = SpyRecurringScheduleHistoryQuery::create()
            ->filterByFkRecurringSchedule($scheduleTransfer->getIdRecurringScheduleOrFail())
            ->findOne();
        $this->assertNotNull($historyEntity);
        $this->assertSame(SharedOrderExperienceManagementConfig::HISTORY_EVENT_TYPE_FAILED, $historyEntity->getEventType());
        $this->assertHistoryDetail('reload failed', $historyEntity->getDetail());
    }

    public function testRunWritesFailedHistoryAndNotifiesWhenItemsNotPurchasable(): void
    {
        // Arrange
        $scheduleTransfer = $this->haveScheduleWithItem([RecurringScheduleTransfer::CUSTOMER_REFERENCE => static::CUSTOMER_REFERENCE]);

        $cartFacadeMock = $this->createMock(CartFacadeInterface::class);
        $cartFacadeMock->method('reloadItemsInQuote')->willReturn(
            (new QuoteResponseTransfer())->setIsSuccessful(true)->setQuoteTransfer(new QuoteTransfer()),
        );
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_CART, $cartFacadeMock);

        $checkoutFacadeMock = $this->createMock(CheckoutFacadeInterface::class);
        $checkoutFacadeMock->expects($this->never())->method('placeOrder');
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_CHECKOUT, $checkoutFacadeMock);

        $paymentFacadeMock = $this->createMock(PaymentFacadeInterface::class);
        $paymentFacadeMock->expects($this->once())->method('recalculatePayments');
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_PAYMENT, $paymentFacadeMock);

        $mailFacadeMock = $this->createMock(MailFacadeInterface::class);
        $mailFacadeMock->expects($this->once())->method('handleMail');
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_MAIL, $mailFacadeMock);

        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_CUSTOMER, $this->createCustomerFacadeMockReturningBuyer());

        // Act
        $this->createCommand()->run(
            (new StateMachineItemTransfer())->setIdentifier($scheduleTransfer->getIdRecurringScheduleOrFail()),
        );

        // Assert
        $historyEntity = SpyRecurringScheduleHistoryQuery::create()
            ->filterByFkRecurringSchedule($scheduleTransfer->getIdRecurringScheduleOrFail())
            ->findOne();
        $this->assertNotNull($historyEntity);
        $this->assertSame(SharedOrderExperienceManagementConfig::HISTORY_EVENT_TYPE_FAILED, $historyEntity->getEventType());
        $this->assertHistoryDetail(static::GLOSSARY_KEY_ITEMS_NOT_PURCHASABLE, $historyEntity->getDetail());
    }

    public function testRunWritesFailedHistoryAndNotifiesWhenCheckoutFails(): void
    {
        // Arrange
        $scheduleTransfer = $this->haveScheduleWithItem([RecurringScheduleTransfer::CUSTOMER_REFERENCE => static::CUSTOMER_REFERENCE]);

        $cartFacadeMock = $this->createCartFacadeMockReturningReloadedQuote();
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_CART, $cartFacadeMock);

        $checkoutFacadeMock = $this->createMock(CheckoutFacadeInterface::class);
        $checkoutFacadeMock->method('placeOrder')->willReturn(
            (new CheckoutResponseTransfer())->setIsSuccess(false)->addError((new CheckoutErrorTransfer())->setMessage('checkout failed')),
        );
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_CHECKOUT, $checkoutFacadeMock);

        $paymentFacadeMock = $this->createMock(PaymentFacadeInterface::class);
        $paymentFacadeMock->expects($this->once())->method('recalculatePayments');
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_PAYMENT, $paymentFacadeMock);

        $mailFacadeMock = $this->createMock(MailFacadeInterface::class);
        $mailFacadeMock->expects($this->once())->method('handleMail');
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_MAIL, $mailFacadeMock);

        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_CUSTOMER, $this->createCustomerFacadeMockReturningBuyer());

        // Act
        $this->createCommand()->run(
            (new StateMachineItemTransfer())->setIdentifier($scheduleTransfer->getIdRecurringScheduleOrFail()),
        );

        // Assert
        $historyEntity = SpyRecurringScheduleHistoryQuery::create()
            ->filterByFkRecurringSchedule($scheduleTransfer->getIdRecurringScheduleOrFail())
            ->findOne();
        $this->assertNotNull($historyEntity);
        $this->assertSame(SharedOrderExperienceManagementConfig::HISTORY_EVENT_TYPE_FAILED, $historyEntity->getEventType());
        $this->assertHistoryDetail('checkout failed', $historyEntity->getDetail());
    }

    public function testRunDoesNotThrowAndWritesHistoryWhenPlacementFailureNotificationThrows(): void
    {
        // Arrange
        $scheduleTransfer = $this->haveScheduleWithItem([RecurringScheduleTransfer::CUSTOMER_REFERENCE => static::CUSTOMER_REFERENCE]);

        $cartFacadeMock = $this->createCartFacadeMockReturningReloadedQuote();
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_CART, $cartFacadeMock);

        $checkoutFacadeMock = $this->createMock(CheckoutFacadeInterface::class);
        $checkoutFacadeMock->method('placeOrder')->willReturn(
            (new CheckoutResponseTransfer())->setIsSuccess(false)
                ->addError((new CheckoutErrorTransfer())->setMessage('checkout failed')),
        );
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_CHECKOUT, $checkoutFacadeMock);

        $paymentFacadeMock = $this->createMock(PaymentFacadeInterface::class);
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_PAYMENT, $paymentFacadeMock);

        $mailFacadeMock = $this->createMock(MailFacadeInterface::class);
        $mailFacadeMock->method('handleMail')->willThrowException(new RuntimeException('SMTP connection refused'));
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_MAIL, $mailFacadeMock);

        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_CUSTOMER, $this->createCustomerFacadeMockReturningBuyer());

        // Act — must not throw even though mail is unavailable
        $this->createCommand()->run(
            (new StateMachineItemTransfer())->setIdentifier($scheduleTransfer->getIdRecurringScheduleOrFail()),
        );

        // Assert — failure history entry is still persisted
        $historyEntity = SpyRecurringScheduleHistoryQuery::create()
            ->filterByFkRecurringSchedule($scheduleTransfer->getIdRecurringScheduleOrFail())
            ->findOne();
        $this->assertNotNull($historyEntity);
        $this->assertSame(SharedOrderExperienceManagementConfig::HISTORY_EVENT_TYPE_FAILED, $historyEntity->getEventType());
        $this->assertHistoryDetail('checkout failed', $historyEntity->getDetail());
    }

    public function testRunListsOnlyUnavailableSkusWhenSomeItemsRemainPurchasable(): void
    {
        // Arrange - a two-item schedule where the reload keeps SKU-1 in full but drops SKU-2 entirely.
        $scheduleTransfer = $this->haveScheduleWithItem([RecurringScheduleTransfer::CUSTOMER_REFERENCE => static::CUSTOMER_REFERENCE]);
        $this->tester->haveRecurringScheduleItem($scheduleTransfer->getIdRecurringScheduleOrFail(), [
            RecurringScheduleItemTransfer::SKU => static::SKU_2,
            RecurringScheduleItemTransfer::QUANTITY => static::QUANTITY_SKU_2,
            RecurringScheduleItemTransfer::ITEM_DATA => json_encode(['sku' => static::SKU_2], JSON_THROW_ON_ERROR),
        ]);

        $cartFacadeMock = $this->createMock(CartFacadeInterface::class);
        $cartFacadeMock->method('reloadItemsInQuote')->willReturn(
            (new QuoteResponseTransfer())->setIsSuccessful(true)->setQuoteTransfer(
                (new QuoteTransfer())->addItem(
                    (new ItemTransfer())->setSku(static::SKU)->setQuantity(static::QUANTITY_ABUNDANT),
                ),
            ),
        );
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_CART, $cartFacadeMock);

        $checkoutFacadeMock = $this->createMock(CheckoutFacadeInterface::class);
        $checkoutFacadeMock->expects($this->never())->method('placeOrder');
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_CHECKOUT, $checkoutFacadeMock);

        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_PAYMENT, $this->createMock(PaymentFacadeInterface::class));
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_MAIL, $this->createMock(MailFacadeInterface::class));
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_CUSTOMER, $this->createCustomerFacadeMockReturningBuyer());

        // Act
        $this->createCommand()->run(
            (new StateMachineItemTransfer())->setIdentifier($scheduleTransfer->getIdRecurringScheduleOrFail()),
        );

        // Assert - only SKU-2 is reported as not purchasable; the fully reloaded SKU-1 is excluded.
        $historyEntity = SpyRecurringScheduleHistoryQuery::create()
            ->filterByFkRecurringSchedule($scheduleTransfer->getIdRecurringScheduleOrFail())
            ->findOne();
        $this->assertNotNull($historyEntity);
        $this->assertSame(SharedOrderExperienceManagementConfig::HISTORY_EVENT_TYPE_FAILED, $historyEntity->getEventType());

        $unavailableSkus = $this->getUnpurchasableSkusFromDetail($historyEntity->getDetail());
        $this->assertStringContainsString(static::SKU_2, $unavailableSkus);
        $this->assertStringNotContainsString(static::SKU, $unavailableSkus);
    }

    public function testRunCapturesMessengerMessagesEmittedDuringReloadIntoFailureDetail(): void
    {
        // Arrange - a message already stored in the (process-static) messenger before placement must be ignored;
        // only messages emitted DURING the reload should surface on the failure detail.
        $scheduleTransfer = $this->haveScheduleWithItem([RecurringScheduleTransfer::CUSTOMER_REFERENCE => static::CUSTOMER_REFERENCE]);

        // Seed the stored messages directly (bypassing translation) so only the reload-time message is captured.
        /** @var \Spryker\Zed\Messenger\Business\MessengerFacadeInterface $messengerFacade */
        $messengerFacade = Locator::getInstance()->messenger()->facade();
        $messengerFacade->getStoredMessages()->addErrorMessage(static::MESSENGER_KEY_STALE);

        $cartFacadeMock = $this->createMock(CartFacadeInterface::class);
        $cartFacadeMock->method('reloadItemsInQuote')->willReturnCallback(
            function () use ($messengerFacade): QuoteResponseTransfer {
                $messengerFacade->getStoredMessages()->addErrorMessage(static::MESSENGER_KEY_FRESH);

                return (new QuoteResponseTransfer())
                    ->setIsSuccessful(false)
                    ->addError((new QuoteErrorTransfer())->setMessage('reload failed'));
            },
        );
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_CART, $cartFacadeMock);

        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_MAIL, $this->createMock(MailFacadeInterface::class));
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_CUSTOMER, $this->createCustomerFacadeMockReturningBuyer());

        // Act
        $this->createCommand()->run(
            (new StateMachineItemTransfer())->setIdentifier($scheduleTransfer->getIdRecurringScheduleOrFail()),
        );

        // Assert - the message emitted during reload is captured, the pre-existing one is not.
        $historyEntity = SpyRecurringScheduleHistoryQuery::create()
            ->filterByFkRecurringSchedule($scheduleTransfer->getIdRecurringScheduleOrFail())
            ->findOne();
        $this->assertNotNull($historyEntity);
        $detail = $historyEntity->getDetail();
        $this->assertNotNull($detail);
        $this->assertStringContainsString(static::MESSENGER_KEY_FRESH, $detail);
        $this->assertStringNotContainsString(static::MESSENGER_KEY_STALE, $detail);
    }

    protected function haveScheduleWithItem(array $scheduleOverrides = []): RecurringScheduleTransfer
    {
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $scheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, $scheduleOverrides);

        $this->tester->haveRecurringScheduleItem($scheduleTransfer->getIdRecurringScheduleOrFail(), [
            RecurringScheduleItemTransfer::SKU => static::SKU,
            RecurringScheduleItemTransfer::QUANTITY => static::QUANTITY,
            RecurringScheduleItemTransfer::ITEM_DATA => json_encode(['sku' => static::SKU], JSON_THROW_ON_ERROR),
        ]);

        return $scheduleTransfer;
    }

    protected function createCartFacadeMockReturningReloadedQuote(): CartFacadeInterface
    {
        $cartFacadeMock = $this->createMock(CartFacadeInterface::class);
        $cartFacadeMock->method('reloadItemsInQuote')->willReturnCallback(
            fn (QuoteTransfer $quoteTransfer): QuoteResponseTransfer => (new QuoteResponseTransfer())
                ->setIsSuccessful(true)
                ->setQuoteTransfer($quoteTransfer),
        );

        return $cartFacadeMock;
    }

    protected function createCustomerFacadeMockReturningBuyer(): CustomerFacadeInterface
    {
        $customerFacadeMock = $this->createMock(CustomerFacadeInterface::class);
        $customerFacadeMock->method('findCustomerByReference')->willReturn(
            (new CustomerResponseTransfer())
                ->setIsSuccess(true)
                ->setCustomerTransfer(
                    (new CustomerTransfer())
                        ->setEmail(static::CUSTOMER_EMAIL)
                        ->setFirstName('Test')
                        ->setLastName('Buyer'),
                ),
        );

        return $customerFacadeMock;
    }

    protected function assertHistoryDetail(string $expectedMessage, ?string $detail): void
    {
        $this->assertNotNull($detail);
        $errors = json_decode($detail, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($expectedMessage, $errors[0]['message']);
    }

    protected function getUnpurchasableSkusFromDetail(?string $detail): string
    {
        $this->assertNotNull($detail);
        $errors = json_decode($detail, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(static::GLOSSARY_KEY_ITEMS_NOT_PURCHASABLE, $errors[0]['message']);

        return $errors[0]['parameters']['%skus%'] ?? '';
    }

    protected function createCommand(): PlaceOrderCommandPlugin
    {
        $command = new PlaceOrderCommandPlugin();
        $command->setBusinessFactory($this->tester->getFactory());

        return $command;
    }
}
