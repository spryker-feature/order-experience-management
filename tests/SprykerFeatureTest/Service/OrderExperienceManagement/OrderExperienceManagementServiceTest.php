<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Service\OrderExperienceManagement;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\QuoteTransfer;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Service
 *  OrderExperienceManagement
 * @group OrderExperienceManagementServiceTest
 * Add your own group annotations below this line
 */
class OrderExperienceManagementServiceTest extends Unit
{
    protected OrderExperienceManagementServiceTester $tester;

    public function testIsEligibleReturnsTrueWhenQuoteHasInvoicePayment(): void
    {
        // Arrange
        $quoteTransfer = (new QuoteTransfer())
            ->addPayment((new PaymentTransfer())->setPaymentMethod('invoice'));

        // Act
        $isEligible = $this->tester->getService()->isEligibleForRecurringOrder($quoteTransfer);

        // Assert
        $this->assertTrue($isEligible);
    }

    public function testIsEligibleReturnsFalseWhenQuoteOriginatesFromRfq(): void
    {
        // Arrange
        $quoteTransfer = (new QuoteTransfer())
            ->setQuoteRequestVersionReference('RFQ--1')
            ->addPayment((new PaymentTransfer())->setPaymentMethod('invoice'));

        // Act
        $isEligible = $this->tester->getService()->isEligibleForRecurringOrder($quoteTransfer);

        // Assert
        $this->assertFalse($isEligible);
    }

    public function testIsEligibleReturnsFalseWhenPaymentMethodIsNotInvoiceBased(): void
    {
        // Arrange
        $quoteTransfer = (new QuoteTransfer())
            ->addPayment((new PaymentTransfer())->setPaymentMethod('creditCard'));

        // Act
        $isEligible = $this->tester->getService()->isEligibleForRecurringOrder($quoteTransfer);

        // Assert
        $this->assertFalse($isEligible);
    }

    public function testIsEligibleReturnsFalseWhenNoPaymentsPresent(): void
    {
        // Arrange + Act
        $isEligible = $this->tester->getService()->isEligibleForRecurringOrder(new QuoteTransfer());

        // Assert
        $this->assertFalse($isEligible);
    }

    public function testIsEligibleReturnsFalseWhenQuoteIsLocked(): void
    {
        // Arrange
        $quoteTransfer = (new QuoteTransfer())
            ->setIsLocked(true)
            ->addPayment((new PaymentTransfer())->setPaymentMethod('invoice'));

        // Act
        $isEligible = $this->tester->getService()->isEligibleForRecurringOrder($quoteTransfer);

        // Assert
        $this->assertFalse($isEligible);
    }

    public function testIsEligibleReturnsFalseWhenCustomerIsGuest(): void
    {
        // Arrange
        $quoteTransfer = (new QuoteTransfer())
            ->setCustomer((new CustomerTransfer())->setIsGuest(true))
            ->addPayment((new PaymentTransfer())->setPaymentMethod('invoice'));

        // Act
        $isEligible = $this->tester->getService()->isEligibleForRecurringOrder($quoteTransfer);

        // Assert
        $this->assertFalse($isEligible);
    }

    public function testIsEligibleReturnsTrueWhenCustomerIsAuthenticated(): void
    {
        // Arrange
        $quoteTransfer = (new QuoteTransfer())
            ->setCustomer((new CustomerTransfer())->setIsGuest(false))
            ->addPayment((new PaymentTransfer())->setPaymentMethod('invoice'));

        // Act
        $isEligible = $this->tester->getService()->isEligibleForRecurringOrder($quoteTransfer);

        // Assert
        $this->assertTrue($isEligible);
    }
}
