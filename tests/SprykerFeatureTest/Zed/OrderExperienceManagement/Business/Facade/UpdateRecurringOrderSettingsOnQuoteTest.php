<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Facade;

use Codeception\Test\Unit;
use DateTimeImmutable;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\QuoteErrorTransfer;
use Generated\Shared\Transfer\QuoteResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringOrderQuoteUpdateRequestTransfer;
use Generated\Shared\Transfer\RecurringOrderQuoteUpdateResponseTransfer;
use Generated\Shared\Transfer\RecurringOrderSettingsTransfer;
use Spryker\Zed\Quote\Business\QuoteFacadeInterface;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\WeeklyCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementDependencyProvider;
use SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Business
 * @group Facade
 * @group UpdateRecurringOrderSettingsOnQuoteTest
 * Add your own group annotations below this line
 */
class UpdateRecurringOrderSettingsOnQuoteTest extends Unit
{
    protected OrderExperienceManagementBusinessTester $tester;

    public function testReturnsFalseWhenQuoteIsNotFound(): void
    {
        // Arrange
        $quoteFacadeMock = $this->createMock(QuoteFacadeInterface::class);
        $quoteFacadeMock->method('findQuoteById')
            ->willReturn((new QuoteResponseTransfer())->setIsSuccessful(false));

        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_QUOTE, $quoteFacadeMock);

        $requestTransfer = (new RecurringOrderQuoteUpdateRequestTransfer())
            ->setIdQuote(PHP_INT_MAX)
            ->setRecurringOrderSettings(
                (new RecurringOrderSettingsTransfer())->setCadenceType('monthly'),
            );

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringOrderSettingsOnQuote($requestTransfer);

        // Assert
        $this->assertFalse($responseTransfer->getIsSuccessful());
        $this->assertGreaterThan(0, $responseTransfer->getErrors()->count());
    }

    public function testMapsQuotePersistenceErrorsToResponse(): void
    {
        // Arrange
        $quoteFacadeMock = $this->createMock(QuoteFacadeInterface::class);
        $quoteFacadeMock->method('findQuoteById')
            ->willReturn((new QuoteResponseTransfer())->setIsSuccessful(true)->setQuoteTransfer(new QuoteTransfer()));
        $quoteFacadeMock->method('updateQuote')
            ->willReturn(
                (new QuoteResponseTransfer())
                    ->setIsSuccessful(false)
                    ->addError((new QuoteErrorTransfer())->setMessage('quote.error.persistence_failed')),
            );

        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_QUOTE, $quoteFacadeMock);

        $requestTransfer = (new RecurringOrderQuoteUpdateRequestTransfer())
            ->setIdQuote(1)
            ->setRecurringOrderSettings((new RecurringOrderSettingsTransfer())->setCadenceType('monthly'));

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringOrderSettingsOnQuote($requestTransfer);

        // Assert
        $this->assertFalse($responseTransfer->getIsSuccessful());
        $this->assertSame(
            'quote.error.persistence_failed',
            $responseTransfer->getErrors()->offsetGet(0)->getMessage(),
        );
    }

    public function testSetsRecurringOrderSettingsOnQuote(): void
    {
        // Arrange
        $recurringOrderSettingsTransfer = (new RecurringOrderSettingsTransfer())
            ->setCadenceType('weekly');

        $updatedQuoteTransfer = (new QuoteTransfer())
            ->setRecurringOrderSettings($recurringOrderSettingsTransfer);

        $quoteFacadeMock = $this->createMock(QuoteFacadeInterface::class);
        $quoteFacadeMock->method('findQuoteById')
            ->willReturn((new QuoteResponseTransfer())->setIsSuccessful(true)->setQuoteTransfer(new QuoteTransfer()));
        $quoteFacadeMock->method('updateQuote')
            ->willReturn((new QuoteResponseTransfer())->setIsSuccessful(true)->setQuoteTransfer($updatedQuoteTransfer));

        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_QUOTE, $quoteFacadeMock);

        $requestTransfer = (new RecurringOrderQuoteUpdateRequestTransfer())
            ->setIdQuote(1)
            ->setRecurringOrderSettings($recurringOrderSettingsTransfer);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringOrderSettingsOnQuote($requestTransfer);

        // Assert
        $this->assertTrue($responseTransfer->getIsSuccessful());
        $this->assertSame(
            'weekly',
            $responseTransfer->getQuoteOrFail()->getRecurringOrderSettingsOrFail()->getCadenceType(),
        );
    }

    public function testResolvesFirstOrderDateToChosenFutureStartDate(): void
    {
        // Arrange
        $startDate = (new DateTimeImmutable('+30 days'))->format('Y-m-d');

        // Act
        $responseTransfer = $this->updateSettingsWithStartDate($startDate);

        // Assert - a future start date is the first recurring order itself.
        $this->assertSame(
            $startDate,
            $responseTransfer->getQuoteOrFail()->getRecurringOrderSettingsOrFail()->getFirstOrderDate(),
        );
    }

    public function testResolvesFirstOrderDateOneCadenceIntervalAfterTodayWhenStartDateIsToday(): void
    {
        // Arrange
        $startDate = (new DateTimeImmutable('today'))->format('Y-m-d');
        $expectedFirstOrderDate = (new DateTimeImmutable('today'))->modify('+7 days')->format('Y-m-d');

        // Act
        $responseTransfer = $this->updateSettingsWithStartDate($startDate);

        // Assert - today's checkout order covers today, so the first recurring order is one interval away.
        $this->assertSame(
            $expectedFirstOrderDate,
            $responseTransfer->getQuoteOrFail()->getRecurringOrderSettingsOrFail()->getFirstOrderDate(),
        );
    }

    protected function updateSettingsWithStartDate(string $startDate): RecurringOrderQuoteUpdateResponseTransfer
    {
        $this->tester->setDependency(
            OrderExperienceManagementDependencyProvider::PLUGINS_CADENCE_TYPE,
            [new WeeklyCadenceTypePlugin()],
        );

        $recurringOrderSettingsTransfer = (new RecurringOrderSettingsTransfer())
            ->setCadenceType(SharedOrderExperienceManagementConfig::CADENCE_TYPE_WEEKLY)
            ->setStartDate($startDate);

        $updatedQuoteTransfer = (new QuoteTransfer())->setRecurringOrderSettings($recurringOrderSettingsTransfer);

        $quoteFacadeMock = $this->createMock(QuoteFacadeInterface::class);
        $quoteFacadeMock->method('findQuoteById')
            ->willReturn((new QuoteResponseTransfer())->setIsSuccessful(true)->setQuoteTransfer(new QuoteTransfer()));
        $quoteFacadeMock->method('updateQuote')
            ->willReturn((new QuoteResponseTransfer())->setIsSuccessful(true)->setQuoteTransfer($updatedQuoteTransfer));

        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_QUOTE, $quoteFacadeMock);

        $requestTransfer = (new RecurringOrderQuoteUpdateRequestTransfer())
            ->setIdQuote(1)
            ->setRecurringOrderSettings($recurringOrderSettingsTransfer);

        return $this->tester->getFacade()->updateRecurringOrderSettingsOnQuote($requestTransfer);
    }

    public function testClearsRecurringOrderSettingsWhenSettingsAreNull(): void
    {
        // Arrange
        $updatedQuoteTransfer = (new QuoteTransfer())->setRecurringOrderSettings(null);

        $quoteFacadeMock = $this->createMock(QuoteFacadeInterface::class);
        $quoteFacadeMock->method('findQuoteById')
            ->willReturn((new QuoteResponseTransfer())->setIsSuccessful(true)->setQuoteTransfer(new QuoteTransfer()));
        $quoteFacadeMock->method('updateQuote')
            ->willReturn((new QuoteResponseTransfer())->setIsSuccessful(true)->setQuoteTransfer($updatedQuoteTransfer));

        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_QUOTE, $quoteFacadeMock);

        $requestTransfer = (new RecurringOrderQuoteUpdateRequestTransfer())
            ->setIdQuote(1)
            ->setRecurringOrderSettings(null);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringOrderSettingsOnQuote($requestTransfer);

        // Assert
        $this->assertTrue($responseTransfer->getIsSuccessful());
        $this->assertNull($responseTransfer->getQuoteOrFail()->getRecurringOrderSettings());
    }

    public function testSetsCustomerFromRequestWhenQuoteHasNoCustomer(): void
    {
        // Arrange
        $customerTransfer = (new CustomerTransfer())->setCustomerReference('customer-ref-1');
        $updatedQuoteTransfer = (new QuoteTransfer())->setCustomer($customerTransfer);

        $quoteFacadeMock = $this->createMock(QuoteFacadeInterface::class);
        $quoteFacadeMock->method('findQuoteById')
            ->willReturn((new QuoteResponseTransfer())->setIsSuccessful(true)->setQuoteTransfer(new QuoteTransfer()));
        $quoteFacadeMock->method('updateQuote')
            ->willReturn((new QuoteResponseTransfer())->setIsSuccessful(true)->setQuoteTransfer($updatedQuoteTransfer));

        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_QUOTE, $quoteFacadeMock);

        $requestTransfer = (new RecurringOrderQuoteUpdateRequestTransfer())
            ->setIdQuote(1)
            ->setCustomer($customerTransfer);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringOrderSettingsOnQuote($requestTransfer);

        // Assert
        $this->assertTrue($responseTransfer->getIsSuccessful());
        $this->assertSame(
            'customer-ref-1',
            $responseTransfer->getQuoteOrFail()->getCustomerOrFail()->getCustomerReference(),
        );
    }

    public function testReturnsFalseWhenQuoteBelongsToAnotherCustomer(): void
    {
        // Arrange
        $quoteWithCustomer = (new QuoteTransfer())
            ->setCustomer((new CustomerTransfer())->setCustomerReference('existing-ref'));

        $quoteFacadeMock = $this->createMock(QuoteFacadeInterface::class);
        $quoteFacadeMock->method('findQuoteById')
            ->willReturn((new QuoteResponseTransfer())->setIsSuccessful(true)->setQuoteTransfer($quoteWithCustomer));
        $quoteFacadeMock->expects($this->never())->method('updateQuote');

        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_QUOTE, $quoteFacadeMock);

        $requestTransfer = (new RecurringOrderQuoteUpdateRequestTransfer())
            ->setIdQuote(1)
            ->setCustomer((new CustomerTransfer())->setCustomerReference('request-ref'));

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringOrderSettingsOnQuote($requestTransfer);

        // Assert
        $this->assertFalse($responseTransfer->getIsSuccessful());
        $this->assertGreaterThan(0, $responseTransfer->getErrors()->count());
    }

    public function testReturnsFalseWhenOwnedQuoteReceivesRequestWithoutCustomer(): void
    {
        // Arrange
        $quoteWithCustomer = (new QuoteTransfer())->setCustomerReference('existing-ref');

        $quoteFacadeMock = $this->createMock(QuoteFacadeInterface::class);
        $quoteFacadeMock->method('findQuoteById')
            ->willReturn((new QuoteResponseTransfer())->setIsSuccessful(true)->setQuoteTransfer($quoteWithCustomer));
        $quoteFacadeMock->expects($this->never())->method('updateQuote');

        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_QUOTE, $quoteFacadeMock);

        $requestTransfer = (new RecurringOrderQuoteUpdateRequestTransfer())
            ->setIdQuote(1)
            ->setRecurringOrderSettings((new RecurringOrderSettingsTransfer())->setCadenceType('monthly'));

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringOrderSettingsOnQuote($requestTransfer);

        // Assert
        $this->assertFalse($responseTransfer->getIsSuccessful());
        $this->assertGreaterThan(0, $responseTransfer->getErrors()->count());
    }

    public function testUpdatesQuoteWhenRequestCustomerMatchesQuoteOwner(): void
    {
        // Arrange
        $recurringOrderSettingsTransfer = (new RecurringOrderSettingsTransfer())->setCadenceType('weekly');

        $quoteWithCustomer = (new QuoteTransfer())->setCustomerReference('customer-ref-1');
        $updatedQuoteTransfer = (new QuoteTransfer())
            ->setCustomerReference('customer-ref-1')
            ->setRecurringOrderSettings($recurringOrderSettingsTransfer);

        $quoteFacadeMock = $this->createMock(QuoteFacadeInterface::class);
        $quoteFacadeMock->method('findQuoteById')
            ->willReturn((new QuoteResponseTransfer())->setIsSuccessful(true)->setQuoteTransfer($quoteWithCustomer));
        $quoteFacadeMock->method('updateQuote')
            ->willReturn((new QuoteResponseTransfer())->setIsSuccessful(true)->setQuoteTransfer($updatedQuoteTransfer));

        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_QUOTE, $quoteFacadeMock);

        $requestTransfer = (new RecurringOrderQuoteUpdateRequestTransfer())
            ->setIdQuote(1)
            ->setRecurringOrderSettings($recurringOrderSettingsTransfer)
            ->setCustomer((new CustomerTransfer())->setCustomerReference('customer-ref-1'));

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringOrderSettingsOnQuote($requestTransfer);

        // Assert
        $this->assertTrue($responseTransfer->getIsSuccessful());
        $this->assertSame(
            'weekly',
            $responseTransfer->getQuoteOrFail()->getRecurringOrderSettingsOrFail()->getCadenceType(),
        );
    }

    public function testReturnsFalseWhenQuoteUpdateFails(): void
    {
        // Arrange
        $quoteFacadeMock = $this->createMock(QuoteFacadeInterface::class);
        $quoteFacadeMock->method('findQuoteById')
            ->willReturn((new QuoteResponseTransfer())->setIsSuccessful(true)->setQuoteTransfer(new QuoteTransfer()));
        $quoteFacadeMock->method('updateQuote')
            ->willReturn((new QuoteResponseTransfer())->setIsSuccessful(false));

        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::FACADE_QUOTE, $quoteFacadeMock);

        $requestTransfer = (new RecurringOrderQuoteUpdateRequestTransfer())
            ->setIdQuote(1)
            ->setRecurringOrderSettings(
                (new RecurringOrderSettingsTransfer())->setCadenceType('monthly'),
            );

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringOrderSettingsOnQuote($requestTransfer);

        // Assert
        $this->assertFalse($responseTransfer->getIsSuccessful());
    }
}
