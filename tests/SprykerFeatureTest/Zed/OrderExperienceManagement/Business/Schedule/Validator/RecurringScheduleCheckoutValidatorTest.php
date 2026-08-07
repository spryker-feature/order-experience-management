<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Schedule\Validator;

use Codeception\Test\Unit;
use DateTimeImmutable;
use Generated\Shared\Transfer\CheckoutErrorTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringOrderSettingsTransfer;
use SprykerFeature\Service\OrderExperienceManagement\OrderExperienceManagementServiceInterface;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence\CadenceResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\RecurringScheduleCheckoutValidator;
use SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\RecurringOrderCheckoutValidatorPluginInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Schedule
 * @group Validator
 * @group RecurringScheduleCheckoutValidatorTest
 * Add your own group annotations below this line
 */
class RecurringScheduleCheckoutValidatorTest extends Unit
{
    protected const string DATE_FORMAT = 'Y-m-d';

    protected const string GLOSSARY_KEY_START_DATE_IN_PAST = 'recurring_orders.checkout.error.start_date_in_past';

    protected const string GLOSSARY_KEY_START_DATE_REQUIRED = 'recurring_orders.checkout.error.start_date_required';

    protected const string GLOSSARY_KEY_PLUGIN_ERROR = 'test.recurring_order.checkout.plugin_error';

    /**
     * @var array<string, string>
     */
    protected const PLUGIN_ERROR_PARAMETERS = ['%budget%' => 'Marketing Q1'];

    public function testCanCreateFromCheckoutReturnsTrueForFutureStartDate(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuoteTransfer((new DateTimeImmutable('+30 days'))->format(static::DATE_FORMAT));

        // Act & Assert
        $this->assertTrue($this->createValidator()->canCreateFromCheckout($quoteTransfer));
    }

    public function testCanCreateFromCheckoutReturnsTrueForTodayStartDate(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuoteTransfer((new DateTimeImmutable())->format(static::DATE_FORMAT));

        // Act & Assert - today is selectable; only past dates are rejected.
        $this->assertTrue($this->createValidator()->canCreateFromCheckout($quoteTransfer));
    }

    public function testCanCreateFromCheckoutReturnsFalseForPastStartDate(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuoteTransfer((new DateTimeImmutable('-1 day'))->format(static::DATE_FORMAT));

        // Act & Assert
        $this->assertFalse($this->createValidator()->canCreateFromCheckout($quoteTransfer));
    }

    public function testValidateCheckoutReturnsInPastKeyForPastStartDate(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuoteTransfer((new DateTimeImmutable('-1 day'))->format(static::DATE_FORMAT));

        // Act
        $checkoutErrorTransfer = $this->createValidator()->validateCheckout($quoteTransfer);

        // Assert
        $this->assertSame(static::GLOSSARY_KEY_START_DATE_IN_PAST, $checkoutErrorTransfer?->getMessage());
    }

    public function testValidateCheckoutReturnsRequiredKeyForEmptyStartDate(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuoteTransfer(null);

        // Act
        $checkoutErrorTransfer = $this->createValidator()->validateCheckout($quoteTransfer);

        // Assert
        $this->assertSame(static::GLOSSARY_KEY_START_DATE_REQUIRED, $checkoutErrorTransfer?->getMessage());
    }

    public function testCanCreateFromCheckoutReturnsFalseForEmptyStartDate(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuoteTransfer(null);

        // Act & Assert
        $this->assertFalse($this->createValidator()->canCreateFromCheckout($quoteTransfer));
    }

    public function testValidateCheckoutReturnsErrorWithParametersReturnedByValidatorPlugin(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuoteTransfer((new DateTimeImmutable('+30 days'))->format(static::DATE_FORMAT));

        // Act
        $checkoutErrorTransfer = $this->createValidator($this->createPluginCheckoutErrorTransfer())
            ->validateCheckout($quoteTransfer);

        // Assert
        $this->assertNotNull($checkoutErrorTransfer);
        $this->assertSame(static::GLOSSARY_KEY_PLUGIN_ERROR, $checkoutErrorTransfer->getMessage());
        $this->assertSame(static::PLUGIN_ERROR_PARAMETERS, $checkoutErrorTransfer->getParameters());
    }

    public function testCanCreateFromCheckoutReturnsFalseWhenValidatorPluginReturnsError(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuoteTransfer((new DateTimeImmutable('+30 days'))->format(static::DATE_FORMAT));

        // Act & Assert
        $this->assertFalse(
            $this->createValidator($this->createPluginCheckoutErrorTransfer())->canCreateFromCheckout($quoteTransfer),
        );
    }

    public function testValidateCheckoutDoesNotInvokeValidatorPluginsForNonRecurringQuote(): void
    {
        // Arrange
        $quoteTransfer = new QuoteTransfer();

        $recurringOrderCheckoutValidatorPluginMock = $this->createMock(RecurringOrderCheckoutValidatorPluginInterface::class);
        $recurringOrderCheckoutValidatorPluginMock->expects($this->never())->method('validate');

        $validator = new RecurringScheduleCheckoutValidator(
            $this->createOrderExperienceManagementServiceMock(),
            $this->createCadenceResolverMock(),
            [$recurringOrderCheckoutValidatorPluginMock],
        );

        // Act & Assert
        $this->assertNull($validator->validateCheckout($quoteTransfer));
    }

    protected function createQuoteTransfer(?string $startDate): QuoteTransfer
    {
        return (new QuoteTransfer())->setRecurringOrderSettings(
            (new RecurringOrderSettingsTransfer())
                ->setCadenceType(SharedOrderExperienceManagementConfig::CADENCE_TYPE_WEEKLY)
                ->setStartDate($startDate),
        );
    }

    protected function createValidator(?CheckoutErrorTransfer $pluginCheckoutErrorTransfer = null): RecurringScheduleCheckoutValidator
    {
        $recurringOrderCheckoutValidatorPluginMock = $this->createMock(RecurringOrderCheckoutValidatorPluginInterface::class);
        $recurringOrderCheckoutValidatorPluginMock->method('validate')->willReturn($pluginCheckoutErrorTransfer);

        return new RecurringScheduleCheckoutValidator(
            $this->createOrderExperienceManagementServiceMock(),
            $this->createCadenceResolverMock(),
            [$recurringOrderCheckoutValidatorPluginMock],
        );
    }

    protected function createPluginCheckoutErrorTransfer(): CheckoutErrorTransfer
    {
        return (new CheckoutErrorTransfer())
            ->setMessage(static::GLOSSARY_KEY_PLUGIN_ERROR)
            ->setParameters(static::PLUGIN_ERROR_PARAMETERS);
    }

    protected function createOrderExperienceManagementServiceMock(): OrderExperienceManagementServiceInterface
    {
        $orderExperienceManagementServiceMock = $this->createMock(OrderExperienceManagementServiceInterface::class);
        $orderExperienceManagementServiceMock->method('isEligibleForRecurringOrder')->willReturn(true);

        return $orderExperienceManagementServiceMock;
    }

    protected function createCadenceResolverMock(): CadenceResolverInterface
    {
        $cadenceResolverMock = $this->createMock(CadenceResolverInterface::class);
        $cadenceResolverMock->method('isSupported')->willReturn(true);
        $cadenceResolverMock->method('isValueRequired')->willReturn(false);

        return $cadenceResolverMock;
    }
}
