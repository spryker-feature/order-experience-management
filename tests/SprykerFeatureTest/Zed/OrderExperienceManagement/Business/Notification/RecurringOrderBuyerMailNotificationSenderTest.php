<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Notification;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\CustomerResponseTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\LocaleTransfer;
use Generated\Shared\Transfer\MailTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use PHPUnit\Framework\MockObject\MockObject;
use Spryker\Zed\CompanyUser\Business\CompanyUserFacadeInterface;
use Spryker\Zed\Customer\Business\CustomerFacadeInterface;
use Spryker\Zed\Mail\Business\MailFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\Mapper\RecurringOrderNotificationMailMapper;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\Reader\RecurringScheduleBuyerReader;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\RecurringOrderBuyerMailNotificationSender;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\Resolver\NotificationRecipientResolver;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface;
use SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Business
 * @group Notification
 * @group RecurringOrderBuyerMailNotificationSenderTest
 * Add your own group annotations below this line
 */
class RecurringOrderBuyerMailNotificationSenderTest extends Unit
{
    protected OrderExperienceManagementBusinessTester $tester;

    public function testNotifyUpcomingOrderSendsMailWithCorrectData(): void
    {
        // Arrange
        $buyerCustomerTransfer = (new CustomerTransfer())
            ->setEmail('buyer@example.com')
            ->setFirstName('John')
            ->setLastName('Doe');

        $recurringScheduleTransfer = (new RecurringScheduleTransfer())
            ->setIdRecurringSchedule(1)
            ->setCustomerReference('DE--1')
            ->setLocaleName('de_DE')
            ->setStoreName('DE')
            ->setName('My Schedule')
            ->setNextTriggerDate('2026-07-01');

        $capturedMailTransfer = null;
        $mailFacadeMock = $this->createMailFacadeMock();
        $mailFacadeMock
            ->expects($this->once())
            ->method('handleMail')
            ->willReturnCallback(static function (MailTransfer $mailTransfer) use (&$capturedMailTransfer): void {
                $capturedMailTransfer = $mailTransfer;
            });

        $sender = $this->createSender(
            repository: $this->createRepositoryMockReturning($recurringScheduleTransfer),
            customerFacade: $this->createCustomerFacadeMockReturningSuccess($buyerCustomerTransfer),
            mailFacade: $mailFacadeMock,
        );

        // Act
        $sender->notifyUpcomingOrder(1);

        // Assert
        $this->assertNotNull($capturedMailTransfer);
        $this->assertSame($buyerCustomerTransfer, $capturedMailTransfer->getCustomer());
        $this->assertSame($recurringScheduleTransfer, $capturedMailTransfer->getRecurringSchedule());
        $this->assertSame('recurring_orders.notify_buyer_upcoming_order', $capturedMailTransfer->getType());
        $this->assertSame('DE', $capturedMailTransfer->getStoreName());
        $this->assertSame('recurring_orders.mail.notify_buyer_upcoming_order.subject', $capturedMailTransfer->getSubject());
        $this->assertCount(1, $capturedMailTransfer->getRecipients());
        $recipient = $capturedMailTransfer->getRecipients()->offsetGet(0);
        $this->assertSame('buyer@example.com', $recipient->getEmail());
        $this->assertSame('John Doe', $recipient->getName());
    }

    public function testNotifyUpcomingOrderUsesScheduleLocaleNameWhenSet(): void
    {
        // Arrange
        $buyerCustomerTransfer = (new CustomerTransfer())
            ->setEmail('buyer@example.com')
            ->setFirstName('Jane')
            ->setLastName('Smith')
            ->setLocale((new LocaleTransfer())->setLocaleName('en_US'));

        $recurringScheduleTransfer = (new RecurringScheduleTransfer())
            ->setCustomerReference('DE--1')
            ->setLocaleName('de_DE');

        $capturedMailTransfer = null;
        $mailFacadeMock = $this->createMailFacadeMock();
        $mailFacadeMock
            ->method('handleMail')
            ->willReturnCallback(static function (MailTransfer $mailTransfer) use (&$capturedMailTransfer): void {
                $capturedMailTransfer = $mailTransfer;
            });

        $sender = $this->createSender(
            repository: $this->createRepositoryMockReturning($recurringScheduleTransfer),
            customerFacade: $this->createCustomerFacadeMockReturningSuccess($buyerCustomerTransfer),
            mailFacade: $mailFacadeMock,
        );

        // Act
        $sender->notifyUpcomingOrder(1);

        // Assert
        $this->assertSame('de_DE', $capturedMailTransfer?->getLocale()?->getLocaleName());
    }

    public function testNotifyUpcomingOrderUsesBuyerLocaleWhenScheduleLocaleIsNull(): void
    {
        // Arrange
        $buyerLocaleTransfer = (new LocaleTransfer())->setLocaleName('en_US');
        $buyerCustomerTransfer = (new CustomerTransfer())
            ->setEmail('buyer@example.com')
            ->setFirstName('Jane')
            ->setLastName('Smith')
            ->setLocale($buyerLocaleTransfer);

        $recurringScheduleTransfer = (new RecurringScheduleTransfer())
            ->setCustomerReference('DE--1')
            ->setLocaleName(null);

        $capturedMailTransfer = null;
        $mailFacadeMock = $this->createMailFacadeMock();
        $mailFacadeMock
            ->method('handleMail')
            ->willReturnCallback(static function (MailTransfer $mailTransfer) use (&$capturedMailTransfer): void {
                $capturedMailTransfer = $mailTransfer;
            });

        $sender = $this->createSender(
            repository: $this->createRepositoryMockReturning($recurringScheduleTransfer),
            customerFacade: $this->createCustomerFacadeMockReturningSuccess($buyerCustomerTransfer),
            mailFacade: $mailFacadeMock,
        );

        // Act
        $sender->notifyUpcomingOrder(1);

        // Assert
        $this->assertSame($buyerLocaleTransfer, $capturedMailTransfer?->getLocale());
    }

    public function testNotifyUpcomingOrderDoesNotSendMailWhenScheduleNotFound(): void
    {
        // Arrange
        $mailFacadeMock = $this->createMailFacadeMock();
        $mailFacadeMock->expects($this->never())->method('handleMail');

        $sender = $this->createSender(
            repository: $this->createRepositoryMockReturning(null),
            mailFacade: $mailFacadeMock,
        );

        // Act
        $sender->notifyUpcomingOrder(999);
    }

    public function testNotifyUpcomingOrderDoesNotSendMailWhenCustomerReferenceIsNull(): void
    {
        // Arrange
        $recurringScheduleTransfer = (new RecurringScheduleTransfer())
            ->setCustomerReference(null);

        $mailFacadeMock = $this->createMailFacadeMock();
        $mailFacadeMock->expects($this->never())->method('handleMail');

        $sender = $this->createSender(
            repository: $this->createRepositoryMockReturning($recurringScheduleTransfer),
            mailFacade: $mailFacadeMock,
        );

        // Act
        $sender->notifyUpcomingOrder(1);
    }

    public function testNotifyUpcomingOrderDoesNotSendMailWhenCustomerLookupFails(): void
    {
        // Arrange
        $recurringScheduleTransfer = (new RecurringScheduleTransfer())
            ->setCustomerReference('DE--1');

        $customerFacadeMock = $this->createMock(CustomerFacadeInterface::class);
        $customerFacadeMock
            ->method('findCustomerByReference')
            ->willReturn((new CustomerResponseTransfer())->setIsSuccess(false));

        $mailFacadeMock = $this->createMailFacadeMock();
        $mailFacadeMock->expects($this->never())->method('handleMail');

        $sender = $this->createSender(
            repository: $this->createRepositoryMockReturning($recurringScheduleTransfer),
            customerFacade: $customerFacadeMock,
            mailFacade: $mailFacadeMock,
        );

        // Act
        $sender->notifyUpcomingOrder(1);
    }

    public function testNotifyUpcomingOrderUsesCompanyAdminAsRecipientWhenBuyerIsAnonymized(): void
    {
        // Arrange
        $buyerCustomerTransfer = (new CustomerTransfer())
            ->setEmail('anonymized@example.com')
            ->setAnonymizedAt('2026-01-01');

        $adminCustomerTransfer = (new CustomerTransfer())
            ->setEmail('admin@company.com')
            ->setFirstName('Admin')
            ->setLastName('User');

        $recurringScheduleTransfer = (new RecurringScheduleTransfer())
            ->setCustomerReference('DE--1')
            ->setIdCompanyUser(10);

        $companyUserFacadeMock = $this->createMock(CompanyUserFacadeInterface::class);
        $companyUserFacadeMock
            ->method('getCompanyUserById')
            ->with(10)
            ->willReturn((new CompanyUserTransfer())->setFkCompany(5));

        $adminCompanyUserTransfer = (new CompanyUserTransfer())->setCustomer($adminCustomerTransfer);
        $companyUserFacadeMock
            ->method('findInitialCompanyUserByCompanyId')
            ->with(5)
            ->willReturn($adminCompanyUserTransfer);

        $capturedMailTransfer = null;
        $mailFacadeMock = $this->createMailFacadeMock();
        $mailFacadeMock
            ->method('handleMail')
            ->willReturnCallback(static function (MailTransfer $mailTransfer) use (&$capturedMailTransfer): void {
                $capturedMailTransfer = $mailTransfer;
            });

        $sender = $this->createSender(
            repository: $this->createRepositoryMockReturning($recurringScheduleTransfer),
            customerFacade: $this->createCustomerFacadeMockReturningSuccess($buyerCustomerTransfer),
            companyUserFacade: $companyUserFacadeMock,
            mailFacade: $mailFacadeMock,
        );

        // Act
        $sender->notifyUpcomingOrder(1);

        // Assert
        $this->assertNotNull($capturedMailTransfer);
        $this->assertSame($buyerCustomerTransfer, $capturedMailTransfer->getCustomer());
        $recipient = $capturedMailTransfer->getRecipients()->offsetGet(0);
        $this->assertSame('admin@company.com', $recipient->getEmail());
        $this->assertSame('Admin User', $recipient->getName());
    }

    protected function createSender(
        ?OrderExperienceManagementRepositoryInterface $repository = null,
        ?CustomerFacadeInterface $customerFacade = null,
        ?CompanyUserFacadeInterface $companyUserFacade = null,
        ?MailFacadeInterface $mailFacade = null,
        ?OrderExperienceManagementConfig $config = null,
    ): RecurringOrderBuyerMailNotificationSender {
        return new RecurringOrderBuyerMailNotificationSender(
            $repository ?? $this->createMock(OrderExperienceManagementRepositoryInterface::class),
            new RecurringScheduleBuyerReader($customerFacade ?? $this->createMock(CustomerFacadeInterface::class)),
            new NotificationRecipientResolver($companyUserFacade ?? $this->createMock(CompanyUserFacadeInterface::class)),
            new RecurringOrderNotificationMailMapper($config ?? $this->createConfigMock()),
            $mailFacade ?? $this->createMailFacadeMock(),
        );
    }

    protected function createConfigMock(): MockObject&OrderExperienceManagementConfig
    {
        $configMock = $this->getMockBuilder(OrderExperienceManagementConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configMock->method('getBaseUrlYves')->willReturn('https://yves.example.com');

        return $configMock;
    }

    protected function createRepositoryMockReturning(?RecurringScheduleTransfer $scheduleTransfer): MockObject&OrderExperienceManagementRepositoryInterface
    {
        $repositoryMock = $this->createMock(OrderExperienceManagementRepositoryInterface::class);
        $repositoryMock->method('findRecurringScheduleById')->willReturn($scheduleTransfer);

        return $repositoryMock;
    }

    protected function createCustomerFacadeMockReturningSuccess(CustomerTransfer $customerTransfer): MockObject&CustomerFacadeInterface
    {
        $customerResponseTransfer = (new CustomerResponseTransfer())
            ->setIsSuccess(true)
            ->setCustomerTransfer($customerTransfer);

        $customerFacadeMock = $this->createMock(CustomerFacadeInterface::class);
        $customerFacadeMock->method('findCustomerByReference')->willReturn($customerResponseTransfer);

        return $customerFacadeMock;
    }

    protected function createMailFacadeMock(): MockObject&MailFacadeInterface
    {
        return $this->createMock(MailFacadeInterface::class);
    }
}
