<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence;

use DateTimeImmutable;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Exception\InvalidCadenceOptionsException;

class WeekDayTriggerDateCalculator implements WeekDayTriggerDateCalculatorInterface
{
    protected const string ISO_WEEK_DAY_FORMAT = 'N';

    protected const int DAYS_IN_WEEK = 7;

    protected const int FIRST_OFFSET_IN_DAYS = 1;

    public function __construct(protected readonly WeekDayOptionsReaderInterface $weekDayOptionsReader)
    {
    }

    /**
     * @param array<string> $cadenceOptions
     *
     * @throws \SprykerFeature\Zed\OrderExperienceManagement\Business\Exception\InvalidCadenceOptionsException
     */
    public function calculateNextTriggerDate(DateTimeImmutable $currentTriggerDate, array $cadenceOptions): DateTimeImmutable
    {
        $weekDays = $this->weekDayOptionsReader->extractWeekDays($cadenceOptions);

        if ($weekDays === []) {
            throw new InvalidCadenceOptionsException(
                sprintf(
                    'Cadence type "%s" requires at least one weekday in the cadence options.',
                    SharedOrderExperienceManagementConfig::CADENCE_TYPE_BY_DAYS,
                ),
            );
        }

        for ($offsetInDays = static::FIRST_OFFSET_IN_DAYS; $offsetInDays <= static::DAYS_IN_WEEK; $offsetInDays++) {
            $candidateDate = $currentTriggerDate->modify(sprintf('+%d days', $offsetInDays));

            if (in_array((int)$candidateDate->format(static::ISO_WEEK_DAY_FORMAT), $weekDays, true)) {
                return $candidateDate;
            }
        }

        throw new InvalidCadenceOptionsException('No matching weekday found within a full week.');
    }
}
