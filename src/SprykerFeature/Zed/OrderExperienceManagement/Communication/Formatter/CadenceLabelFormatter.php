<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Communication\Formatter;

use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Spryker\Zed\Translator\Business\TranslatorFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Mapper\WeekDayNameMapperInterface;

class CadenceLabelFormatter implements CadenceLabelFormatterInterface
{
    protected const string LABEL_SEPARATOR = ', ';

    public function __construct(
        protected readonly TranslatorFacadeInterface $translatorFacade,
        protected readonly WeekDayNameMapperInterface $weekDayNameMapper,
    ) {
    }

    public function formatCadence(RecurringScheduleTransfer $recurringScheduleTransfer): string
    {
        $cadenceType = $recurringScheduleTransfer->getCadenceType();

        if ($cadenceType === null) {
            return '';
        }

        $cadenceLabel = $this->translatorFacade->trans(ucfirst(str_replace('_', ' ', $cadenceType)));
        $cadenceValue = $recurringScheduleTransfer->getCadenceValue();

        if ($cadenceValue !== null) {
            return sprintf('%s (%d)', $cadenceLabel, $cadenceValue);
        }

        return $cadenceLabel;
    }

    public function formatCadenceWithWeekDays(RecurringScheduleTransfer $recurringScheduleTransfer): string
    {
        $cadenceLabel = $this->formatCadence($recurringScheduleTransfer);
        $weekDayNames = $this->formatWeekDayNames($recurringScheduleTransfer);

        if ($cadenceLabel === '' || $weekDayNames === '') {
            return $cadenceLabel;
        }

        return sprintf('%s (%s)', $cadenceLabel, $weekDayNames);
    }

    protected function formatWeekDayNames(RecurringScheduleTransfer $recurringScheduleTransfer): string
    {
        $cadenceOptions = $recurringScheduleTransfer->getCadenceOptions();
        $weekDayNames = [];

        foreach ($this->weekDayNameMapper->mapWeekDaysToNames() as $weekDay => $weekDayName) {
            if (!in_array((string)$weekDay, $cadenceOptions, true)) {
                continue;
            }

            $weekDayNames[] = $this->translatorFacade->trans($weekDayName);
        }

        return implode(static::LABEL_SEPARATOR, $weekDayNames);
    }
}
