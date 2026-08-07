<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator;

use DateTimeImmutable;
use Generated\Shared\Transfer\CheckoutErrorTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringOrderSettingsTransfer;
use SprykerFeature\Service\OrderExperienceManagement\OrderExperienceManagementServiceInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence\CadenceResolverInterface;

class RecurringScheduleCheckoutValidator implements RecurringScheduleCheckoutValidatorInterface
{
    protected const string GLOSSARY_KEY_NOT_ELIGIBLE = 'recurring_orders.checkout.error.not_eligible';

    protected const string GLOSSARY_KEY_SETTINGS_NOT_CONFIRMED = 'recurring_orders.checkout.validation.settings_not_confirmed';

    protected const string GLOSSARY_KEY_CADENCE_REQUIRED = 'recurring_orders.checkout.validation.cadence_required';

    protected const string GLOSSARY_KEY_CADENCE_VALUE_REQUIRED = 'recurring_orders.checkout.error.cadence_value_required';

    protected const string GLOSSARY_KEY_START_DATE_REQUIRED = 'recurring_orders.checkout.error.start_date_required';

    protected const string GLOSSARY_KEY_START_DATE_IN_PAST = 'recurring_orders.checkout.error.start_date_in_past';

    protected const string DATE_FORMAT = 'Y-m-d';

    /**
     * @param array<\SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\RecurringOrderCheckoutValidatorPluginInterface> $recurringOrderCheckoutValidatorPlugins
     */
    public function __construct(
        protected readonly OrderExperienceManagementServiceInterface $subscriptionService,
        protected readonly CadenceResolverInterface $cadenceResolver,
        protected readonly array $recurringOrderCheckoutValidatorPlugins = [],
    ) {
    }

    public function canCreateFromCheckout(QuoteTransfer $quoteTransfer): bool
    {
        if ($quoteTransfer->getRecurringOrderSettings() === null) {
            return false;
        }

        return $this->validateCheckout($quoteTransfer) === null;
    }

    public function validateCheckout(QuoteTransfer $quoteTransfer): ?CheckoutErrorTransfer
    {
        $recurringOrderSettingsTransfer = $quoteTransfer->getRecurringOrderSettings();

        if ($recurringOrderSettingsTransfer === null) {
            return null;
        }

        if (!$this->subscriptionService->isEligibleForRecurringOrder($quoteTransfer)) {
            return $this->createCheckoutErrorTransfer(static::GLOSSARY_KEY_NOT_ELIGIBLE);
        }

        $checkoutErrorTransfer = $this->executeRecurringOrderCheckoutValidatorPlugins($quoteTransfer);

        if ($checkoutErrorTransfer !== null) {
            return $checkoutErrorTransfer;
        }

        $cadenceErrorGlossaryKey = $this->validateCadence($recurringOrderSettingsTransfer);

        if ($cadenceErrorGlossaryKey !== null) {
            return $this->createCheckoutErrorTransfer($cadenceErrorGlossaryKey);
        }

        $startDateErrorGlossaryKey = $this->validateStartDate($recurringOrderSettingsTransfer);

        return $startDateErrorGlossaryKey !== null ? $this->createCheckoutErrorTransfer($startDateErrorGlossaryKey) : null;
    }

    protected function executeRecurringOrderCheckoutValidatorPlugins(QuoteTransfer $quoteTransfer): ?CheckoutErrorTransfer
    {
        foreach ($this->recurringOrderCheckoutValidatorPlugins as $recurringOrderCheckoutValidatorPlugin) {
            $checkoutErrorTransfer = $recurringOrderCheckoutValidatorPlugin->validate($quoteTransfer);

            if ($checkoutErrorTransfer !== null) {
                return $checkoutErrorTransfer;
            }
        }

        return null;
    }

    protected function createCheckoutErrorTransfer(string $glossaryKey): CheckoutErrorTransfer
    {
        return (new CheckoutErrorTransfer())->setMessage($glossaryKey);
    }

    protected function validateStartDate(RecurringOrderSettingsTransfer $recurringOrderSettingsTransfer): ?string
    {
        $startDate = $recurringOrderSettingsTransfer->getStartDate();

        if ($startDate === null || $startDate === '') {
            return static::GLOSSARY_KEY_START_DATE_REQUIRED;
        }

        $startDateTime = DateTimeImmutable::createFromFormat('!' . static::DATE_FORMAT, $startDate);

        if ($startDateTime === false || $startDateTime < new DateTimeImmutable('today')) {
            return static::GLOSSARY_KEY_START_DATE_IN_PAST;
        }

        return null;
    }

    protected function validateCadence(RecurringOrderSettingsTransfer $recurringOrderSettingsTransfer): ?string
    {
        $cadenceType = $recurringOrderSettingsTransfer->getCadenceType();

        if ($cadenceType === null || $cadenceType === '') {
            return static::GLOSSARY_KEY_SETTINGS_NOT_CONFIRMED;
        }

        if (!$this->cadenceResolver->isSupported($cadenceType)) {
            return static::GLOSSARY_KEY_CADENCE_REQUIRED;
        }

        if (!$this->isCadenceValueValid($cadenceType, $recurringOrderSettingsTransfer->getCadenceValue())) {
            return static::GLOSSARY_KEY_CADENCE_VALUE_REQUIRED;
        }

        return null;
    }

    protected function isCadenceValueValid(string $cadenceType, ?int $cadenceValue): bool
    {
        if (!$this->cadenceResolver->isValueRequired($cadenceType)) {
            return true;
        }

        return $cadenceValue !== null && $cadenceValue >= 1;
    }
}
