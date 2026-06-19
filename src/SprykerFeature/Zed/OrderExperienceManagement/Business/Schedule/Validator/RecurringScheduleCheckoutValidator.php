<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator;

use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringOrderSettingsTransfer;
use SprykerFeature\Service\OrderExperienceManagement\OrderExperienceManagementServiceInterface;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence\CadenceResolverInterface;

class RecurringScheduleCheckoutValidator implements RecurringScheduleCheckoutValidatorInterface
{
    protected const string GLOSSARY_KEY_NOT_ELIGIBLE = 'recurring_orders.checkout.error.not_eligible';

    protected const string GLOSSARY_KEY_UNSUPPORTED_CADENCE_TYPE = 'recurring_orders.checkout.error.unsupported_cadence_type';

    protected const string GLOSSARY_KEY_CADENCE_VALUE_REQUIRED = 'recurring_orders.checkout.error.cadence_value_required';

    public function __construct(
        protected readonly OrderExperienceManagementServiceInterface $subscriptionService,
        protected readonly CadenceResolverInterface $cadenceResolver,
    ) {
    }

    public function canCreateFromCheckout(QuoteTransfer $quoteTransfer): bool
    {
        if ($quoteTransfer->getRecurringOrderSettings() === null) {
            return false;
        }

        return $this->validateCheckout($quoteTransfer) === null;
    }

    public function validateCheckout(QuoteTransfer $quoteTransfer): ?string
    {
        $recurringOrderSettingsTransfer = $quoteTransfer->getRecurringOrderSettings();

        if ($recurringOrderSettingsTransfer === null) {
            return null;
        }

        if (!$this->subscriptionService->isEligibleForRecurringOrder($quoteTransfer)) {
            return static::GLOSSARY_KEY_NOT_ELIGIBLE;
        }

        return $this->validateCadence($recurringOrderSettingsTransfer);
    }

    protected function validateCadence(RecurringOrderSettingsTransfer $recurringOrderSettingsTransfer): ?string
    {
        $cadenceType = $recurringOrderSettingsTransfer->getCadenceType();

        if ($cadenceType === null || !$this->cadenceResolver->isSupported($cadenceType)) {
            return static::GLOSSARY_KEY_UNSUPPORTED_CADENCE_TYPE;
        }

        if (!$this->isCadenceValueValid($cadenceType, $recurringOrderSettingsTransfer->getCadenceValue())) {
            return static::GLOSSARY_KEY_CADENCE_VALUE_REQUIRED;
        }

        return null;
    }

    protected function isCadenceValueValid(string $cadenceType, ?int $cadenceValue): bool
    {
        if ($cadenceType !== SharedOrderExperienceManagementConfig::CADENCE_TYPE_EVERY_N_WEEKS) {
            return true;
        }

        return $cadenceValue !== null && $cadenceValue >= 1;
    }
}
