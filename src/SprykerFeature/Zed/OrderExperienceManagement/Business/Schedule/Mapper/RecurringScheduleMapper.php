<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Mapper;

use DateTimeImmutable;
use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use Spryker\Zed\Locale\Business\LocaleFacadeInterface;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence\CadenceResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;

class RecurringScheduleMapper implements RecurringScheduleMapperInterface
{
    protected const string DATE_FORMAT = 'Y-m-d';

    protected const string PAYMENT_METHOD_UNKNOWN = 'unknown';

    public function __construct(
        protected readonly CadenceResolverInterface $cadenceResolver,
        protected readonly UtilEncodingServiceInterface $utilEncodingService,
        protected readonly OrderExperienceManagementConfig $subscriptionConfig,
        protected readonly LocaleFacadeInterface $localeFacade,
    ) {
    }

    public function mapQuoteToRecurringSchedule(
        QuoteTransfer $quoteTransfer,
        CheckoutResponseTransfer $checkoutResponseTransfer,
    ): RecurringScheduleTransfer {
        $quoteArray = $quoteTransfer->toArray(true, true);
        unset(
            $quoteArray[QuoteTransfer::ITEMS],
            $quoteArray[QuoteTransfer::BUNDLE_ITEMS],
            $quoteArray[QuoteTransfer::ID_QUOTE],
            $quoteArray[QuoteTransfer::RECURRING_ORDER_SETTINGS],
        );

        $recurringOrderSettings = $quoteTransfer->getRecurringOrderSettings();
        $cadenceType = $recurringOrderSettings?->getCadenceType() ?? SharedOrderExperienceManagementConfig::CADENCE_TYPE_WEEKLY;
        $cadenceValue = $recurringOrderSettings?->getCadenceValue();
        $nextTriggerDate = $this->resolveNextTriggerDate($cadenceType, $cadenceValue);

        return (new RecurringScheduleTransfer())
            ->setIdCustomer($quoteTransfer->getCustomerOrFail()->getIdCustomerOrFail())
            ->setIdCompanyUser($quoteTransfer->getCustomer()?->getCompanyUserTransfer()?->getIdCompanyUser())
            ->setIdSourceSalesOrder($checkoutResponseTransfer->getSaveOrder()?->getIdSalesOrder())
            ->setName($recurringOrderSettings?->getScheduleName() ?? $checkoutResponseTransfer->getSaveOrder()?->getOrderReference())
            ->setCadenceType($cadenceType)
            ->setCadenceValue($cadenceValue)
            ->setFirstTriggerDate($nextTriggerDate)
            ->setNextTriggerDate($nextTriggerDate)
            ->setPaymentMethod($this->resolvePaymentMethod($quoteTransfer))
            ->setStoreName($quoteTransfer->getStoreOrFail()->getNameOrFail())
            ->setCurrencyIsoCode($quoteTransfer->getCurrencyOrFail()->getCodeOrFail())
            ->setPriceMode($quoteTransfer->getPriceModeOrFail())
            ->setCustomerReference($quoteTransfer->getCustomerReference())
            ->setLocaleName($quoteTransfer->getCustomer()?->getLocale()?->getLocaleName() ?? $this->localeFacade->getCurrentLocaleName())
            ->setStatus($this->subscriptionConfig->getDefaultScheduleStatus())
            ->setNotificationWindowHours($this->subscriptionConfig->getDefaultNotificationWindowHours())
            ->setQuoteData((string)$this->utilEncodingService->encodeJson($quoteArray));
    }

    protected function resolveNextTriggerDate(string $cadenceType, ?int $cadenceValue): string
    {
        $recurringScheduleTransfer = (new RecurringScheduleTransfer())
            ->setCadenceType($cadenceType)
            ->setCadenceValue($cadenceValue)
            ->setNextTriggerDate((new DateTimeImmutable())->format(static::DATE_FORMAT));

        return $this->cadenceResolver->resolveNextTriggerDate($recurringScheduleTransfer)->format(static::DATE_FORMAT);
    }

    protected function resolvePaymentMethod(QuoteTransfer $quoteTransfer): string
    {
        $payments = $quoteTransfer->getPayments();

        if ($payments->count() > 0) {
            return $payments->offsetGet(0)->getPaymentMethod() ?? static::PAYMENT_METHOD_UNKNOWN;
        }

        return $quoteTransfer->getPayment()?->getPaymentMethod() ?? static::PAYMENT_METHOD_UNKNOWN;
    }
}
