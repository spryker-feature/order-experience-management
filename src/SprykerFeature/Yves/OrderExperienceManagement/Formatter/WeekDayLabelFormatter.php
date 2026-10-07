<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Formatter;

use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Spryker\Client\GlossaryStorage\GlossaryStorageClientInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Mapper\WeekDayGlossaryKeyMapperInterface;

class WeekDayLabelFormatter implements WeekDayLabelFormatterInterface
{
    protected const string LABEL_SEPARATOR = ', ';

    public function __construct(
        protected readonly WeekDayGlossaryKeyMapperInterface $weekDayGlossaryKeyMapper,
        protected readonly GlossaryStorageClientInterface $glossaryStorageClient,
    ) {
    }

    /**
     * @param iterable<\Generated\Shared\Transfer\RecurringScheduleTransfer> $recurringScheduleTransfers
     *
     * @return array<string, string>
     */
    public function formatWeekDayLabelsByScheduleUuid(iterable $recurringScheduleTransfers, string $localeName): array
    {
        $weekDayGlossaryKeys = $this->weekDayGlossaryKeyMapper->mapWeekDaysToGlossaryKeys();
        $translations = $this->glossaryStorageClient->translateBulk(array_values($weekDayGlossaryKeys), $localeName);

        $weekDayLabelsByScheduleUuid = [];

        foreach ($recurringScheduleTransfers as $recurringScheduleTransfer) {
            $uuid = $recurringScheduleTransfer->getUuid();

            if ($uuid === null) {
                continue;
            }

            $weekDayLabelsByScheduleUuid[$uuid] = $this->formatWeekDayLabels(
                $recurringScheduleTransfer,
                $weekDayGlossaryKeys,
                $translations,
            );
        }

        return $weekDayLabelsByScheduleUuid;
    }

    /**
     * @param array<int, string> $weekDayGlossaryKeys
     * @param array<string> $translations
     */
    protected function formatWeekDayLabels(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        array $weekDayGlossaryKeys,
        array $translations,
    ): string {
        $cadenceOptions = $recurringScheduleTransfer->getCadenceOptions();
        $weekDayLabels = [];

        foreach ($weekDayGlossaryKeys as $weekDay => $glossaryKey) {
            if (!in_array((string)$weekDay, $cadenceOptions, true)) {
                continue;
            }

            $weekDayLabels[] = $translations[$glossaryKey] ?? $glossaryKey;
        }

        return implode(static::LABEL_SEPARATOR, $weekDayLabels);
    }
}
