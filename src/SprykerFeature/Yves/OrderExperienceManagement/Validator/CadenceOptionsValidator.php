<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Validator;

use Generated\Shared\Transfer\RecurringOrderSettingsTransfer;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderSelectorForm;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringScheduleEditForm;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CadenceOptionsValidator implements CadenceOptionsValidatorInterface
{
    protected const string GLOSSARY_KEY_CHECKOUT_DAYS_REQUIRED = 'recurring_orders.checkout.validation.days_required';

    protected const string GLOSSARY_KEY_SCHEDULE_EDIT_DAYS_REQUIRED = 'recurring_orders.detail.edit.validation.days_required';

    public function validateCheckoutCadenceOptions(
        ?RecurringOrderSettingsTransfer $recurringOrderSettingsTransfer,
        ExecutionContextInterface $context,
    ): void {
        if ($recurringOrderSettingsTransfer === null) {
            return;
        }

        $this->validateWeekDaySelection(
            $recurringOrderSettingsTransfer->getCadenceType(),
            $recurringOrderSettingsTransfer->getCadenceOptions(),
            static::GLOSSARY_KEY_CHECKOUT_DAYS_REQUIRED,
            RecurringOrderSelectorForm::FIELD_CADENCE_OPTIONS,
            $context,
        );
    }

    /**
     * @param array<string, mixed>|null $formData
     */
    public function validateScheduleEditCadenceOptions(?array $formData, ExecutionContextInterface $context): void
    {
        if ($formData === null) {
            return;
        }

        $cadenceType = $formData[RecurringScheduleEditForm::FIELD_CADENCE_TYPE] ?? null;
        $cadenceOptions = $formData[RecurringScheduleEditForm::FIELD_CADENCE_OPTIONS] ?? [];

        $this->validateWeekDaySelection(
            is_string($cadenceType) ? $cadenceType : null,
            is_array($cadenceOptions) ? $cadenceOptions : [],
            static::GLOSSARY_KEY_SCHEDULE_EDIT_DAYS_REQUIRED,
            RecurringScheduleEditForm::FIELD_CADENCE_OPTIONS,
            $context,
        );
    }

    /**
     * @param array<mixed> $cadenceOptions
     */
    protected function validateWeekDaySelection(
        ?string $cadenceType,
        array $cadenceOptions,
        string $glossaryKey,
        string $fieldName,
        ExecutionContextInterface $context,
    ): void {
        if ($cadenceType !== SharedOrderExperienceManagementConfig::CADENCE_TYPE_BY_DAYS) {
            return;
        }

        if ($cadenceOptions !== []) {
            return;
        }

        $context->buildViolation($glossaryKey)
            ->atPath($fieldName)
            ->addViolation();
    }
}
