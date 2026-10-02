<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Communication\Mapper;

use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;

class WeekDayNameMapper implements WeekDayNameMapperInterface
{
    /**
     * @var array<int, string>
     */
    protected const array WEEK_DAY_NAMES = [
        SharedOrderExperienceManagementConfig::DAY_MONDAY => 'Monday',
        SharedOrderExperienceManagementConfig::DAY_TUESDAY => 'Tuesday',
        SharedOrderExperienceManagementConfig::DAY_WEDNESDAY => 'Wednesday',
        SharedOrderExperienceManagementConfig::DAY_THURSDAY => 'Thursday',
        SharedOrderExperienceManagementConfig::DAY_FRIDAY => 'Friday',
        SharedOrderExperienceManagementConfig::DAY_SATURDAY => 'Saturday',
        SharedOrderExperienceManagementConfig::DAY_SUNDAY => 'Sunday',
    ];

    /**
     * @return array<int, string>
     */
    public function mapWeekDaysToNames(): array
    {
        return static::WEEK_DAY_NAMES;
    }
}
