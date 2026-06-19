<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Communication\Plugin;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringOrderSettingsTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\BiWeeklyCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\EveryNWeeksCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\MonthlyCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\WeeklyCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Checkout\RecurringOrderCheckoutPreConditionPlugin;
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
 * @group RecurringOrderCheckoutPreConditionPluginTest
 * Add your own group annotations below this line
 */
class RecurringOrderCheckoutPreConditionPluginTest extends Unit
{
    /**
     * @uses \SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig::DEFAULT_INVOICE_PAYMENT_METHOD_KEYS
     */
    protected const string PAYMENT_METHOD_INVOICE = 'invoice';

    protected OrderExperienceManagementBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::PLUGINS_CADENCE_TYPE, [
            new WeeklyCadenceTypePlugin(),
            new BiWeeklyCadenceTypePlugin(),
            new MonthlyCadenceTypePlugin(),
            new EveryNWeeksCadenceTypePlugin(),
        ]);
    }

    public function testCheckConditionReturnsTrueWhenRecurringOrderSettingsIsNull(): void
    {
        // Arrange
        $plugin = new RecurringOrderCheckoutPreConditionPlugin();
        $checkoutResponseTransfer = new CheckoutResponseTransfer();

        // Act
        $result = $plugin->checkCondition(new QuoteTransfer(), $checkoutResponseTransfer);

        // Assert
        $this->assertTrue($result);
        $this->assertEmpty($checkoutResponseTransfer->getErrors());
    }

    public function testCheckConditionReturnsTrueForSupportedCadenceType(): void
    {
        // Arrange
        $plugin = new RecurringOrderCheckoutPreConditionPlugin();
        $quoteTransfer = (new QuoteTransfer())
            ->setRecurringOrderSettings((new RecurringOrderSettingsTransfer())->setCadenceType('weekly'))
            ->addPayment((new PaymentTransfer())->setPaymentMethod(static::PAYMENT_METHOD_INVOICE));
        $checkoutResponseTransfer = new CheckoutResponseTransfer();

        // Act
        $result = $plugin->checkCondition($quoteTransfer, $checkoutResponseTransfer);

        // Assert
        $this->assertTrue($result);
        $this->assertEmpty($checkoutResponseTransfer->getErrors());
    }

    public function testCheckConditionReturnsTrueForEveryNWeeksWithValidCadenceValue(): void
    {
        // Arrange
        $plugin = new RecurringOrderCheckoutPreConditionPlugin();
        $quoteTransfer = (new QuoteTransfer())
            ->setRecurringOrderSettings(
                (new RecurringOrderSettingsTransfer())
                    ->setCadenceType('every_n_weeks')
                    ->setCadenceValue(3),
            )
            ->addPayment((new PaymentTransfer())->setPaymentMethod(static::PAYMENT_METHOD_INVOICE));
        $checkoutResponseTransfer = new CheckoutResponseTransfer();

        // Act
        $result = $plugin->checkCondition($quoteTransfer, $checkoutResponseTransfer);

        // Assert
        $this->assertTrue($result);
        $this->assertEmpty($checkoutResponseTransfer->getErrors());
    }

    public function testCheckConditionReturnsFalseWhenQuoteIsNotEligible(): void
    {
        // Arrange — valid cadence but no invoice payment makes the quote ineligible
        $plugin = new RecurringOrderCheckoutPreConditionPlugin();
        $quoteTransfer = (new QuoteTransfer())
            ->setRecurringOrderSettings((new RecurringOrderSettingsTransfer())->setCadenceType('weekly'));
        $checkoutResponseTransfer = new CheckoutResponseTransfer();

        // Act
        $result = $plugin->checkCondition($quoteTransfer, $checkoutResponseTransfer);

        // Assert
        $this->assertFalse($result);
        $this->assertNotEmpty($checkoutResponseTransfer->getErrors());
    }

    public function testCheckConditionReturnsFalseWhenCadenceTypeIsUnsupported(): void
    {
        // Arrange
        $plugin = new RecurringOrderCheckoutPreConditionPlugin();
        $quoteTransfer = (new QuoteTransfer())
            ->setRecurringOrderSettings(
                (new RecurringOrderSettingsTransfer())->setCadenceType('unsupported_type'),
            )
            ->addPayment((new PaymentTransfer())->setPaymentMethod(static::PAYMENT_METHOD_INVOICE));
        $checkoutResponseTransfer = new CheckoutResponseTransfer();

        // Act
        $result = $plugin->checkCondition($quoteTransfer, $checkoutResponseTransfer);

        // Assert
        $this->assertFalse($result);
        $this->assertNotEmpty($checkoutResponseTransfer->getErrors());
    }

    public function testCheckConditionReturnsFalseWhenEveryNWeeksHasNullCadenceValue(): void
    {
        // Arrange
        $plugin = new RecurringOrderCheckoutPreConditionPlugin();
        $quoteTransfer = (new QuoteTransfer())
            ->setRecurringOrderSettings(
                (new RecurringOrderSettingsTransfer())
                    ->setCadenceType('every_n_weeks')
                    ->setCadenceValue(null),
            )
            ->addPayment((new PaymentTransfer())->setPaymentMethod(static::PAYMENT_METHOD_INVOICE));
        $checkoutResponseTransfer = new CheckoutResponseTransfer();

        // Act
        $result = $plugin->checkCondition($quoteTransfer, $checkoutResponseTransfer);

        // Assert
        $this->assertFalse($result);
        $this->assertNotEmpty($checkoutResponseTransfer->getErrors());
    }

    public function testCheckConditionReturnsFalseWhenEveryNWeeksHasCadenceValueOfZero(): void
    {
        // Arrange
        $plugin = new RecurringOrderCheckoutPreConditionPlugin();
        $quoteTransfer = (new QuoteTransfer())
            ->setRecurringOrderSettings(
                (new RecurringOrderSettingsTransfer())
                    ->setCadenceType('every_n_weeks')
                    ->setCadenceValue(0),
            )
            ->addPayment((new PaymentTransfer())->setPaymentMethod(static::PAYMENT_METHOD_INVOICE));
        $checkoutResponseTransfer = new CheckoutResponseTransfer();

        // Act
        $result = $plugin->checkCondition($quoteTransfer, $checkoutResponseTransfer);

        // Assert
        $this->assertFalse($result);
        $this->assertNotEmpty($checkoutResponseTransfer->getErrors());
    }
}
