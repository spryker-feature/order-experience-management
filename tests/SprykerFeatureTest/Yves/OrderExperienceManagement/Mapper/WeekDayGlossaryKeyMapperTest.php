<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\OrderExperienceManagement\Mapper;

use Codeception\Test\Unit;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Yves\OrderExperienceManagement\Mapper\WeekDayGlossaryKeyMapper;
use SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Yves
 * @group OrderExperienceManagement
 * @group Mapper
 * @group WeekDayGlossaryKeyMapperTest
 * Add your own group annotations below this line
 */
class WeekDayGlossaryKeyMapperTest extends Unit
{
    public function testMapsEveryWeekDayTheProjectOffersInConfigOrder(): void
    {
        // Arrange
        $mapper = $this->createMapper([
            SharedOrderExperienceManagementConfig::DAY_SUNDAY,
            SharedOrderExperienceManagementConfig::DAY_MONDAY,
            SharedOrderExperienceManagementConfig::DAY_FRIDAY,
        ]);

        // Act
        $weekDayGlossaryKeys = $mapper->mapWeekDaysToGlossaryKeys();

        // Assert
        $this->assertSame([
            SharedOrderExperienceManagementConfig::DAY_SUNDAY => 'recurring_orders.day.sunday',
            SharedOrderExperienceManagementConfig::DAY_MONDAY => 'recurring_orders.day.monday',
            SharedOrderExperienceManagementConfig::DAY_FRIDAY => 'recurring_orders.day.friday',
        ], $weekDayGlossaryKeys);
    }

    /**
     * The picker renders the choices in array order, so the form field inherits the configured order.
     */
    public function testBuildsChoicesInConfigOrder(): void
    {
        // Arrange
        $mapper = $this->createMapper([
            SharedOrderExperienceManagementConfig::DAY_SUNDAY,
            SharedOrderExperienceManagementConfig::DAY_MONDAY,
        ]);

        // Act
        $weekDayChoices = $mapper->mapWeekDaysToChoices();

        // Assert
        $this->assertSame([
            'recurring_orders.day.sunday' => '7',
            'recurring_orders.day.monday' => '1',
        ], $weekDayChoices);
    }

    public function testDefaultConfigOffersEveryWeekDayMondayToSunday(): void
    {
        // Act
        $weekDayGlossaryKeys = (new WeekDayGlossaryKeyMapper(new OrderExperienceManagementConfig()))
            ->mapWeekDaysToGlossaryKeys();

        // Assert
        $this->assertSame(SharedOrderExperienceManagementConfig::SUPPORTED_WEEK_DAYS, array_keys($weekDayGlossaryKeys));
    }

    /**
     * @param array<int> $supportedWeekDays
     */
    protected function createMapper(array $supportedWeekDays): WeekDayGlossaryKeyMapper
    {
        $configMock = $this->createMock(OrderExperienceManagementConfig::class);
        $configMock->method('getSupportedWeekDays')->willReturn($supportedWeekDays);

        return new WeekDayGlossaryKeyMapper($configMock);
    }
}
