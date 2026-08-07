<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Mapper;

use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringOrderSettingsTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence\CadenceResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Date\FirstTriggerDateResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;

class RecurringScheduleMapper implements RecurringScheduleMapperInterface
{
    protected const string DATE_FORMAT = 'Y-m-d';

    protected const string PAYMENT_METHOD_UNKNOWN = 'unknown';

    public function __construct(
        protected readonly CadenceResolverInterface $cadenceResolver,
        protected readonly UtilEncodingServiceInterface $utilEncodingService,
        protected readonly OrderExperienceManagementConfig $config,
        protected readonly FirstTriggerDateResolverInterface $firstTriggerDateResolver,
    ) {
    }

    public function mapQuoteToRecurringSchedule(
        QuoteTransfer $quoteTransfer,
        CheckoutResponseTransfer $checkoutResponseTransfer,
        string $currentLocaleName,
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
        $cadenceValue = $this->resolveCadenceValue($cadenceType, $recurringOrderSettings?->getCadenceValue());
        $firstTriggerDate = $this->resolveFirstTriggerDate($recurringOrderSettings, $cadenceType, $cadenceValue);

        return (new RecurringScheduleTransfer())
            ->setIdCustomer($quoteTransfer->getCustomerOrFail()->getIdCustomerOrFail())
            ->setIdCompanyUser($quoteTransfer->getCustomer()?->getCompanyUserTransfer()?->getIdCompanyUser())
            ->setIdSourceSalesOrder($checkoutResponseTransfer->getSaveOrder()?->getIdSalesOrder())
            ->setName($recurringOrderSettings?->getScheduleName() ?? $checkoutResponseTransfer->getSaveOrder()?->getOrderReference())
            ->setCadenceType($cadenceType)
            ->setCadenceValue($cadenceValue)
            ->setFirstTriggerDate($firstTriggerDate)
            ->setNextTriggerDate($firstTriggerDate)
            ->setPaymentMethod($this->resolvePaymentMethod($quoteTransfer))
            ->setStoreName($quoteTransfer->getStoreOrFail()->getNameOrFail())
            ->setCurrencyIsoCode($quoteTransfer->getCurrencyOrFail()->getCodeOrFail())
            ->setPriceMode($quoteTransfer->getPriceModeOrFail())
            ->setCustomerReference($quoteTransfer->getCustomerReference())
            ->setLocaleName($quoteTransfer->getCustomer()?->getLocale()?->getLocaleName() ?? $currentLocaleName)
            ->setStatus($this->config->getDefaultScheduleStatus())
            ->setNotificationWindowHours($this->config->getDefaultNotificationWindowHours())
            ->setQuoteData((string)$this->utilEncodingService->encodeJson($quoteArray));
    }

    public function mapRequestedRecurringScheduleToRecurringSchedule(
        RecurringScheduleTransfer $requestedRecurringScheduleTransfer,
        RecurringScheduleTransfer $existingRecurringScheduleTransfer,
        RecurringScheduleTransfer $recurringScheduleTransfer,
    ): RecurringScheduleTransfer {
        $recurringScheduleTransfer->setIdRecurringSchedule(
            $existingRecurringScheduleTransfer->getIdRecurringScheduleOrFail(),
        );

        if ($requestedRecurringScheduleTransfer->getName() !== null) {
            $recurringScheduleTransfer->setName($requestedRecurringScheduleTransfer->getName());
        }

        if ($requestedRecurringScheduleTransfer->getCadenceType() !== null) {
            $recurringScheduleTransfer->setCadenceType($requestedRecurringScheduleTransfer->getCadenceType());
            $recurringScheduleTransfer->setCadenceValue(
                $this->resolveCadenceValue(
                    $requestedRecurringScheduleTransfer->getCadenceType(),
                    $requestedRecurringScheduleTransfer->getCadenceValue(),
                ),
            );
        }

        if ($requestedRecurringScheduleTransfer->getNextTriggerDate() !== null) {
            $recurringScheduleTransfer->setNextTriggerDate($requestedRecurringScheduleTransfer->getNextTriggerDate());
        }

        return $recurringScheduleTransfer;
    }

    protected function resolveCadenceValue(string $cadenceType, ?int $cadenceValue): ?int
    {
        if ($cadenceType !== SharedOrderExperienceManagementConfig::CADENCE_TYPE_EVERY_N_WEEKS) {
            return null;
        }

        return $cadenceValue;
    }

    protected function resolveFirstTriggerDate(
        ?RecurringOrderSettingsTransfer $recurringOrderSettingsTransfer,
        string $cadenceType,
        ?int $cadenceValue,
    ): string {
        return $this->firstTriggerDateResolver
            ->resolve($recurringOrderSettingsTransfer?->getStartDate(), $cadenceType, $cadenceValue)
            ->format(static::DATE_FORMAT);
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
