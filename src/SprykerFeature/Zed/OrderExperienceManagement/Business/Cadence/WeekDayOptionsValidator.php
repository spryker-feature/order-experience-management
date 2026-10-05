<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence;

use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;

class WeekDayOptionsValidator implements WeekDayOptionsValidatorInterface
{
    public function __construct(protected readonly OrderExperienceManagementConfig $orderExperienceManagementConfig)
    {
    }

    /**
     * @param array<string> $cadenceOptions
     */
    public function areWeekDaysValid(array $cadenceOptions): bool
    {
        if ($cadenceOptions === []) {
            return false;
        }

        $supportedWeekDays = $this->orderExperienceManagementConfig->getSupportedWeekDays();

        foreach ($cadenceOptions as $cadenceOption) {
            if (!is_numeric($cadenceOption)) {
                return false;
            }

            if (!in_array((int)$cadenceOption, $supportedWeekDays, true)) {
                return false;
            }
        }

        return true;
    }
}
