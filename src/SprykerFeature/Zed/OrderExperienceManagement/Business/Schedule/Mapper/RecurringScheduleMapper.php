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
        $cadenceOptions = $this->resolveCadenceOptions($cadenceType, $recurringOrderSettings?->getCadenceOptions() ?? []);
        $firstTriggerDate = $this->resolveFirstTriggerDate($recurringOrderSettings, $cadenceType, $cadenceValue, $cadenceOptions);

        return (new RecurringScheduleTransfer())
            ->setIdCustomer($quoteTransfer->getCustomerOrFail()->getIdCustomerOrFail())
            ->setIdCompanyUser($quoteTransfer->getCustomer()?->getCompanyUserTransfer()?->getIdCompanyUser())
            ->setIdSourceSalesOrder($checkoutResponseTransfer->getSaveOrder()?->getIdSalesOrder())
            ->setName($recurringOrderSettings?->getScheduleName() ?? $checkoutResponseTransfer->getSaveOrder()?->getOrderReference())
            ->setCadenceType($cadenceType)
            ->setCadenceValue($cadenceValue)
            ->setCadenceOptions($cadenceOptions)
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
            $recurringScheduleTransfer->setCadenceOptions(
                $this->resolveCadenceOptions(
                    $requestedRecurringScheduleTransfer->getCadenceType(),
                    $requestedRecurringScheduleTransfer->getCadenceOptions(),
                ),
            );
        }

        if ($requestedRecurringScheduleTransfer->getNextTriggerDate() !== null) {
            $recurringScheduleTransfer->setNextTriggerDate($requestedRecurringScheduleTransfer->getNextTriggerDate());
        }

        if ($requestedRecurringScheduleTransfer->getPriceTolerancePercent() !== null) {
            $recurringScheduleTransfer->setPriceTolerancePercent($requestedRecurringScheduleTransfer->getPriceTolerancePercent());
        }

        if ($requestedRecurringScheduleTransfer->getIsProductPriceChangeIgnored() !== null) {
            $recurringScheduleTransfer->setIsProductPriceChangeIgnored($requestedRecurringScheduleTransfer->getIsProductPriceChangeIgnored());
        }

        return $recurringScheduleTransfer;
    }

    /**
     * @param \ArrayObject<int, string>|array<string> $cadenceOptions
     *
     * @return array<string>
     */
    protected function getRequiredCadenceFields(string $cadenceType, ?int $cadenceValue, iterable $cadenceOptions = []): array
    {
        return $this->cadenceResolver
            ->isCadenceValid($cadenceType, $cadenceValue, $this->castCadenceOptions($cadenceOptions))
            ->getRequiredCadenceFields();
    }

    protected function resolveCadenceValue(string $cadenceType, ?int $cadenceValue): ?int
    {
        $requiredCadenceFields = $this->getRequiredCadenceFields($cadenceType, $cadenceValue);

        if (!in_array(SharedOrderExperienceManagementConfig::CADENCE_FIELD_CADENCE_VALUE, $requiredCadenceFields, true)) {
            return null;
        }

        return $cadenceValue;
    }

    /**
     * @param \ArrayObject<int, string>|array<string> $cadenceOptions
     *
     * @return array<string>
     */
    protected function resolveCadenceOptions(string $cadenceType, iterable $cadenceOptions): array
    {
        $requiredCadenceFields = $this->getRequiredCadenceFields($cadenceType, null, $cadenceOptions);

        if (!in_array(SharedOrderExperienceManagementConfig::CADENCE_FIELD_CADENCE_OPTIONS, $requiredCadenceFields, true)) {
            return [];
        }

        return $this->castCadenceOptions($cadenceOptions);
    }

    /**
     * @param \ArrayObject<int, string>|array<string> $cadenceOptions
     *
     * @return array<string>
     */
    protected function castCadenceOptions(iterable $cadenceOptions): array
    {
        $castCadenceOptions = [];

        foreach ($cadenceOptions as $cadenceOption) {
            $castCadenceOptions[] = (string)$cadenceOption;
        }

        return $castCadenceOptions;
    }

    /**
     * @param array<string> $cadenceOptions
     */
    protected function resolveFirstTriggerDate(
        ?RecurringOrderSettingsTransfer $recurringOrderSettingsTransfer,
        string $cadenceType,
        ?int $cadenceValue,
        array $cadenceOptions = [],
    ): string {
        return $this->firstTriggerDateResolver
            ->resolve($recurringOrderSettingsTransfer?->getStartDate(), $cadenceType, $cadenceValue, $cadenceOptions)
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
