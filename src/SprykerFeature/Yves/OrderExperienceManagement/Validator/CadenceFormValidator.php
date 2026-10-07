<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Validator;

use DateTimeImmutable;
use DateTimeInterface;
use Generated\Shared\Transfer\RecurringOrderSettingsTransfer;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderSelectorForm;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringScheduleEditForm;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CadenceFormValidator implements CadenceFormValidatorInterface
{
    protected const string GLOSSARY_KEY_DAYS_REQUIRED = 'recurring_orders.cadence.validation.days_required';

    protected const string GLOSSARY_KEY_DATE_NOT_SELECTED_WEEK_DAY = 'recurring_orders.cadence.validation.date_not_selected_week_day';

    protected const string DATE_FORMAT = 'Y-m-d';

    protected const string ISO_WEEK_DAY_FORMAT = 'N';

    public function validateCheckoutCadence(
        ?RecurringOrderSettingsTransfer $recurringOrderSettingsTransfer,
        ExecutionContextInterface $context,
    ): void {
        if ($recurringOrderSettingsTransfer === null) {
            return;
        }

        $cadenceType = $recurringOrderSettingsTransfer->getCadenceType();
        $cadenceOptions = $recurringOrderSettingsTransfer->getCadenceOptions();

        $this->validateWeekDaySelection(
            $cadenceType,
            $cadenceOptions,
            RecurringOrderSelectorForm::FIELD_CADENCE_OPTIONS,
            $context,
        );

        $this->validateDateFallsOnSelectedWeekDay(
            $cadenceType,
            $cadenceOptions,
            $this->parseDate($recurringOrderSettingsTransfer->getStartDate()),
            RecurringOrderSelectorForm::FIELD_START_DATE,
            $context,
        );
    }

    /**
     * @param array<string, mixed>|null $formData
     */
    public function validateScheduleEditCadence(?array $formData, ExecutionContextInterface $context): void
    {
        if ($formData === null) {
            return;
        }

        $cadenceType = $formData[RecurringScheduleEditForm::FIELD_CADENCE_TYPE] ?? null;
        $cadenceType = is_string($cadenceType) ? $cadenceType : null;
        $cadenceOptions = $formData[RecurringScheduleEditForm::FIELD_CADENCE_OPTIONS] ?? [];
        $cadenceOptions = is_array($cadenceOptions) ? $cadenceOptions : [];
        $nextExecutionDate = $formData[RecurringScheduleEditForm::FIELD_NEXT_EXECUTION_DATE] ?? null;

        $this->validateWeekDaySelection(
            $cadenceType,
            $cadenceOptions,
            RecurringScheduleEditForm::FIELD_CADENCE_OPTIONS,
            $context,
        );

        $this->validateDateFallsOnSelectedWeekDay(
            $cadenceType,
            $cadenceOptions,
            $nextExecutionDate instanceof DateTimeInterface ? $nextExecutionDate : null,
            RecurringScheduleEditForm::FIELD_NEXT_EXECUTION_DATE,
            $context,
        );
    }

    /**
     * @param array<mixed> $cadenceOptions
     */
    protected function validateWeekDaySelection(
        ?string $cadenceType,
        array $cadenceOptions,
        string $fieldName,
        ExecutionContextInterface $context,
    ): void {
        if (!$this->isByDays($cadenceType)) {
            return;
        }

        if ($cadenceOptions !== []) {
            return;
        }

        $context->buildViolation(static::GLOSSARY_KEY_DAYS_REQUIRED)
            ->atPath($fieldName)
            ->addViolation();
    }

    /**
     * @param array<mixed> $cadenceOptions
     */
    protected function validateDateFallsOnSelectedWeekDay(
        ?string $cadenceType,
        array $cadenceOptions,
        ?DateTimeInterface $date,
        string $fieldName,
        ExecutionContextInterface $context,
    ): void {
        if (!$this->isByDays($cadenceType) || $cadenceOptions === [] || $date === null) {
            return;
        }

        if ($date->format(static::DATE_FORMAT) <= (new DateTimeImmutable('today'))->format(static::DATE_FORMAT)) {
            return;
        }

        if (in_array($date->format(static::ISO_WEEK_DAY_FORMAT), array_map('strval', $cadenceOptions), true)) {
            return;
        }

        $context->buildViolation(static::GLOSSARY_KEY_DATE_NOT_SELECTED_WEEK_DAY)
            ->atPath($fieldName)
            ->addViolation();
    }

    protected function isByDays(?string $cadenceType): bool
    {
        return $cadenceType === SharedOrderExperienceManagementConfig::CADENCE_TYPE_BY_DAYS;
    }

    protected function parseDate(?string $date): ?DateTimeImmutable
    {
        if ($date === null || $date === '') {
            return null;
        }

        return DateTimeImmutable::createFromFormat('!' . static::DATE_FORMAT, $date) ?: null;
    }
}
