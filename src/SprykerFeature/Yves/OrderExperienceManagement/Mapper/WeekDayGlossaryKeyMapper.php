<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Mapper;

use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig;

class WeekDayGlossaryKeyMapper implements WeekDayGlossaryKeyMapperInterface
{
    /**
     * @var array<int, string>
     */
    protected const array WEEK_DAY_GLOSSARY_KEYS = [
        SharedOrderExperienceManagementConfig::DAY_MONDAY => 'recurring_orders.day.monday',
        SharedOrderExperienceManagementConfig::DAY_TUESDAY => 'recurring_orders.day.tuesday',
        SharedOrderExperienceManagementConfig::DAY_WEDNESDAY => 'recurring_orders.day.wednesday',
        SharedOrderExperienceManagementConfig::DAY_THURSDAY => 'recurring_orders.day.thursday',
        SharedOrderExperienceManagementConfig::DAY_FRIDAY => 'recurring_orders.day.friday',
        SharedOrderExperienceManagementConfig::DAY_SATURDAY => 'recurring_orders.day.saturday',
        SharedOrderExperienceManagementConfig::DAY_SUNDAY => 'recurring_orders.day.sunday',
    ];

    public function __construct(protected readonly OrderExperienceManagementConfig $config)
    {
    }

    /**
     * @return array<int, string>
     */
    public function mapWeekDaysToGlossaryKeys(): array
    {
        $weekDayGlossaryKeys = [];

        foreach ($this->config->getSupportedWeekDays() as $weekDay) {
            if (!isset(static::WEEK_DAY_GLOSSARY_KEYS[$weekDay])) {
                continue;
            }

            $weekDayGlossaryKeys[$weekDay] = static::WEEK_DAY_GLOSSARY_KEYS[$weekDay];
        }

        return $weekDayGlossaryKeys;
    }

    /**
     * @return array<string, string>
     */
    public function mapWeekDaysToChoices(): array
    {
        $weekDayChoices = [];

        foreach ($this->mapWeekDaysToGlossaryKeys() as $weekDay => $glossaryKey) {
            $weekDayChoices[$glossaryKey] = (string)$weekDay;
        }

        return $weekDayChoices;
    }
}
