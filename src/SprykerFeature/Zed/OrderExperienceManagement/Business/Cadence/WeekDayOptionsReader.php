<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence;

use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;

class WeekDayOptionsReader implements WeekDayOptionsReaderInterface
{
    /**
     * @param array<string> $cadenceOptions
     *
     * @return array<int>
     */
    public function extractWeekDays(array $cadenceOptions): array
    {
        $weekDays = [];

        foreach ($cadenceOptions as $cadenceOption) {
            if (!is_numeric($cadenceOption)) {
                continue;
            }

            $weekDay = (int)$cadenceOption;

            if (!in_array($weekDay, SharedOrderExperienceManagementConfig::SUPPORTED_WEEK_DAYS, true)) {
                continue;
            }

            $weekDays[] = $weekDay;
        }

        $weekDays = array_values(array_unique($weekDays));
        sort($weekDays);

        return $weekDays;
    }
}
