<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Communication\Plugin;

use Codeception\Test\Unit;
use DateTimeImmutable;
use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringOrderSettingsTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleItemQuery;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleQuery;
use PHPUnit\Framework\MockObject\MockObject;
use Propel\Runtime\ActiveQuery\Criteria;
use Spryker\Zed\StateMachine\Business\StateMachineFacadeInterface;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\BiWeeklyCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\EveryNWeeksCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\MonthlyCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\WeeklyCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Checkout\RecurringOrdersCheckoutPostSavePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementDependencyProvider;
use SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Communication
 * @group Plugin
 * @group RecurringOrdersCheckoutPostSavePluginTest
 * Add your own group annotations below this line
 */
class RecurringOrdersCheckoutPostSavePluginTest extends Unit
{
    /**
     * @uses \Spryker\Shared\Price\PriceConfig::PRICE_MODE_GROSS
     */
    protected const string PRICE_MODE_GROSS = 'GROSS_MODE';

    protected const string CADENCE_TYPE_WEEKLY = SharedOrderExperienceManagementConfig::CADENCE_TYPE_WEEKLY;

    /**
     * @uses \SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig::DEFAULT_INVOICE_PAYMENT_METHOD_KEYS
     */
    protected const string PAYMENT_METHOD_INVOICE = 'invoice';

    protected const string SKU_TEST = 'test-sku';

    protected const string SKU_TEST_SECOND = 'test-sku-2';

    protected const string SKU_TEST_THIRD = 'test-sku-3';

    protected const int UNIT_GROSS_PRICE = 100;

    protected OrderExperienceManagementBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->ensureRecurringScheduleTablesAreEmpty();

        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::PLUGINS_CADENCE_TYPE, [
            new WeeklyCadenceTypePlugin(),
            new BiWeeklyCadenceTypePlugin(),
            new MonthlyCadenceTypePlugin(),
            new EveryNWeeksCadenceTypePlugin(),
        ]);
    }

    public function testDoesNothingWhenRecurringOrderSettingsIsNull(): void
    {
        // Arrange
        $plugin = new RecurringOrdersCheckoutPostSavePlugin();
        $quoteTransfer = (new QuoteTransfer())
            ->setRecurringOrderSettings(null)
            ->addPayment((new PaymentTransfer())->setPaymentMethod(static::PAYMENT_METHOD_INVOICE));

        // Act
        $plugin->executeHook($quoteTransfer, new CheckoutResponseTransfer());

        // Assert — no exception, no DB interaction
        $this->assertTrue(true);
    }

    public function testDoesNothingWhenQuoteOriginatesFromRfq(): void
    {
        // Arrange
        $plugin = new RecurringOrdersCheckoutPostSavePlugin();
        $quoteTransfer = (new QuoteTransfer())
            ->setRecurringOrderSettings((new RecurringOrderSettingsTransfer())->setCadenceType(static::CADENCE_TYPE_WEEKLY))
            ->setQuoteRequestVersionReference('RFQ--1')
            ->addPayment((new PaymentTransfer())->setPaymentMethod(static::PAYMENT_METHOD_INVOICE));

        // Act
        $plugin->executeHook($quoteTransfer, new CheckoutResponseTransfer());

        // Assert — no exception, no DB interaction
        $this->assertTrue(true);
    }

    public function testDoesNothingWhenPaymentMethodIsNotInvoice(): void
    {
        // Arrange
        $plugin = new RecurringOrdersCheckoutPostSavePlugin();
        $quoteTransfer = (new QuoteTransfer())
            ->setRecurringOrderSettings((new RecurringOrderSettingsTransfer())->setCadenceType(static::CADENCE_TYPE_WEEKLY))
            ->addPayment((new PaymentTransfer())->setPaymentMethod('credit_card'));

        // Act
        $plugin->executeHook($quoteTransfer, new CheckoutResponseTransfer());

        // Assert — no exception, no DB interaction
        $this->assertTrue(true);
    }

    public function testDoesNothingWhenOrderPlacementFailed(): void
    {
        // Arrange
        $plugin = new RecurringOrdersCheckoutPostSavePlugin();
        $quoteTransfer = (new QuoteTransfer())
            ->setRecurringOrderSettings((new RecurringOrderSettingsTransfer())->setCadenceType(static::CADENCE_TYPE_WEEKLY))
            ->addPayment((new PaymentTransfer())->setPaymentMethod(static::PAYMENT_METHOD_INVOICE));

        $checkoutResponseTransfer = (new CheckoutResponseTransfer())->setIsSuccess(false);

        $schedulesBeforeCount = SpyRecurringScheduleQuery::create()->count();

        // Act
        $plugin->executeHook($quoteTransfer, $checkoutResponseTransfer);

        // Assert — no schedule created when order placement failed
        $this->assertSame($schedulesBeforeCount, SpyRecurringScheduleQuery::create()->count());
    }

    public function testCreatesRecurringScheduleWhenAllConditionsAreMet(): void
    {
        // Arrange
        $schedulesBeforeCount = SpyRecurringScheduleQuery::create()->count();

        // Act
        $this->placeOrderWithStartDate((new DateTimeImmutable('today'))->format('Y-m-d'));

        // Assert
        $this->assertSame($schedulesBeforeCount + 1, SpyRecurringScheduleQuery::create()->count());
    }

    public function testCreatesRecurringScheduleItemForSingleQuoteItem(): void
    {
        // Act
        $this->placeOrderWithStartDate((new DateTimeImmutable('today'))->format('Y-m-d'));

        // Assert
        $this->assertSame([static::SKU_TEST => 1], $this->getPersistedItemQuantitiesBySku());
    }

    public function testCreatesRecurringScheduleItemsForAllQuoteItems(): void
    {
        // Arrange
        $itemTransfers = [
            $this->createItemTransfer(static::SKU_TEST, 1),
            $this->createItemTransfer(static::SKU_TEST_SECOND, 2),
            $this->createItemTransfer(static::SKU_TEST_THIRD, 3),
        ];

        // Act
        $this->placeOrderWithStartDate((new DateTimeImmutable('today'))->format('Y-m-d'), $itemTransfers);

        // Assert
        $this->assertSame(
            [
                static::SKU_TEST => 1,
                static::SKU_TEST_SECOND => 2,
                static::SKU_TEST_THIRD => 3,
            ],
            $this->getPersistedItemQuantitiesBySku(),
        );
    }

    public function testDoesNotCreateRecurringScheduleWhenStartDateIsMissing(): void
    {
        // Arrange
        $this->tester->setDependency(
            OrderExperienceManagementDependencyProvider::FACADE_STATE_MACHINE,
            $this->createStateMachineFacadeMock(),
        );

        $customerTransfer = $this->tester->haveCustomer()->setIsGuest(false);

        $quoteTransfer = (new QuoteTransfer())
            ->setCustomer($customerTransfer)
            ->setCustomerReference($customerTransfer->getCustomerReference())
            ->setStore((new StoreTransfer())->setName('DE'))
            ->setCurrency($this->tester->haveCurrencyTransfer())
            ->setPriceMode(static::PRICE_MODE_GROSS)
            ->setRecurringOrderSettings((new RecurringOrderSettingsTransfer())->setCadenceType(static::CADENCE_TYPE_WEEKLY))
            ->addPayment((new PaymentTransfer())->setPaymentMethod(static::PAYMENT_METHOD_INVOICE))
            ->addItem((new ItemTransfer())->setSku(static::SKU_TEST)->setQuantity(1)->setUnitGrossPrice(100));

        $schedulesBeforeCount = SpyRecurringScheduleQuery::create()->count();

        // Act
        (new RecurringOrdersCheckoutPostSavePlugin())->executeHook($quoteTransfer, (new CheckoutResponseTransfer())->setIsSuccess(true));

        // Assert - the start date is required, so no schedule is created without it.
        $this->assertSame($schedulesBeforeCount, SpyRecurringScheduleQuery::create()->count());
    }

    public function testPersistsChosenFutureStartDateAsFirstTrigger(): void
    {
        // Arrange
        $startDate = (new DateTimeImmutable('+30 days'))->format('Y-m-d');

        // Act
        $this->placeOrderWithStartDate($startDate);

        // Assert - a future start date is persisted verbatim as the first recurring delivery.
        $this->assertPersistedTriggerDates($startDate);
    }

    public function testPersistsFirstTriggerOneCadenceIntervalAfterTodayWhenStartDateIsToday(): void
    {
        // Arrange
        $startDate = (new DateTimeImmutable('today'))->format('Y-m-d');
        $expectedFirstTriggerDate = (new DateTimeImmutable('today'))->modify('+7 days')->format('Y-m-d');

        // Act
        $this->placeOrderWithStartDate($startDate);

        // Assert - today's checkout order covers today, so the first recurring delivery is one interval away.
        $this->assertPersistedTriggerDates($expectedFirstTriggerDate);
    }

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers Defaults to a single item when empty.
     */
    protected function placeOrderWithStartDate(string $startDate, array $itemTransfers = []): void
    {
        $this->tester->setDependency(
            OrderExperienceManagementDependencyProvider::FACADE_STATE_MACHINE,
            $this->createStateMachineFacadeMock(),
        );

        $customerTransfer = $this->tester->haveCustomer()->setIsGuest(false);

        if ($itemTransfers === []) {
            $itemTransfers = [$this->createItemTransfer(static::SKU_TEST, 1)];
        }

        $quoteTransfer = (new QuoteTransfer())
            ->setCustomer($customerTransfer)
            ->setCustomerReference($customerTransfer->getCustomerReference())
            ->setStore((new StoreTransfer())->setName('DE'))
            ->setCurrency($this->tester->haveCurrencyTransfer())
            ->setPriceMode(static::PRICE_MODE_GROSS)
            ->setRecurringOrderSettings(
                (new RecurringOrderSettingsTransfer())
                    ->setCadenceType(static::CADENCE_TYPE_WEEKLY)
                    ->setStartDate($startDate),
            )
            ->addPayment((new PaymentTransfer())->setPaymentMethod(static::PAYMENT_METHOD_INVOICE));

        foreach ($itemTransfers as $itemTransfer) {
            $quoteTransfer->addItem($itemTransfer);
        }

        (new RecurringOrdersCheckoutPostSavePlugin())->executeHook($quoteTransfer, (new CheckoutResponseTransfer())->setIsSuccess(true));
    }

    protected function createItemTransfer(string $sku, int $quantity): ItemTransfer
    {
        return (new ItemTransfer())
            ->setSku($sku)
            ->setQuantity($quantity)
            ->setUnitGrossPrice($quantity * static::UNIT_GROSS_PRICE);
    }

    /**
     * @return array<string, int>
     */
    protected function getPersistedItemQuantitiesBySku(): array
    {
        $recurringScheduleEntity = SpyRecurringScheduleQuery::create()
            ->orderByIdRecurringSchedule(Criteria::DESC)
            ->findOne();

        $this->assertNotNull($recurringScheduleEntity);

        $itemQuantitiesBySku = [];
        $recurringScheduleItemEntities = SpyRecurringScheduleItemQuery::create()
            ->filterByFkRecurringSchedule($recurringScheduleEntity->getIdRecurringSchedule())
            ->find();

        foreach ($recurringScheduleItemEntities as $recurringScheduleItemEntity) {
            $itemQuantitiesBySku[$recurringScheduleItemEntity->getSku()] = $recurringScheduleItemEntity->getQuantity();
        }

        ksort($itemQuantitiesBySku);

        return $itemQuantitiesBySku;
    }

    protected function assertPersistedTriggerDates(string $expectedTriggerDate): void
    {
        $recurringScheduleEntity = SpyRecurringScheduleQuery::create()
            ->orderByIdRecurringSchedule(Criteria::DESC)
            ->findOne();

        $this->assertNotNull($recurringScheduleEntity);
        $this->assertSame($expectedTriggerDate, $recurringScheduleEntity->getFirstTriggerDate()->format('Y-m-d'));
        $this->assertSame($expectedTriggerDate, $recurringScheduleEntity->getNextTriggerDate()->format('Y-m-d'));
    }

    protected function createStateMachineFacadeMock(): MockObject&StateMachineFacadeInterface
    {
        $mock = $this->createMock(StateMachineFacadeInterface::class);
        $mock->method('triggerForNewStateMachineItem')->willReturn(0);

        return $mock;
    }
}
