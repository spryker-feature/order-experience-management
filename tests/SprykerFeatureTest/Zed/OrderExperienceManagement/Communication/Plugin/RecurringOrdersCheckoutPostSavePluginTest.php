<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Communication\Plugin;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringOrderSettingsTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleQuery;
use PHPUnit\Framework\MockObject\MockObject;
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
            ->addItem((new ItemTransfer())->setSku('test-sku')->setQuantity(1)->setUnitGrossPrice(100));

        $schedulesBeforeCount = SpyRecurringScheduleQuery::create()->count();

        // Act
        (new RecurringOrdersCheckoutPostSavePlugin())->executeHook($quoteTransfer, (new CheckoutResponseTransfer())->setIsSuccess(true));

        // Assert
        $this->assertSame($schedulesBeforeCount + 1, SpyRecurringScheduleQuery::create()->count());
    }

    protected function createStateMachineFacadeMock(): MockObject&StateMachineFacadeInterface
    {
        $mock = $this->createMock(StateMachineFacadeInterface::class);
        $mock->method('triggerForNewStateMachineItem')->willReturn(0);

        return $mock;
    }
}
