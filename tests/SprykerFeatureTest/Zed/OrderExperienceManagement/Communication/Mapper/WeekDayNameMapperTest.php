<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Communication\Mapper;

use Codeception\Test\Unit;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Mapper\WeekDayNameMapper;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Communication
 * @group Mapper
 * @group WeekDayNameMapperTest
 * Add your own group annotations below this line
 */
class WeekDayNameMapperTest extends Unit
{
    public function testMapsEveryWeekDayTheProjectOffersInConfigOrder(): void
    {
        // Arrange
        $configMock = $this->createMock(OrderExperienceManagementConfig::class);
        $configMock->method('getSupportedWeekDays')->willReturn([
            SharedOrderExperienceManagementConfig::DAY_SUNDAY,
            SharedOrderExperienceManagementConfig::DAY_MONDAY,
            SharedOrderExperienceManagementConfig::DAY_FRIDAY,
        ]);

        // Act
        $weekDayNames = (new WeekDayNameMapper($configMock))->mapWeekDaysToNames();

        // Assert
        $this->assertSame([
            SharedOrderExperienceManagementConfig::DAY_SUNDAY => 'Sunday',
            SharedOrderExperienceManagementConfig::DAY_MONDAY => 'Monday',
            SharedOrderExperienceManagementConfig::DAY_FRIDAY => 'Friday',
        ], $weekDayNames);
    }

    public function testDefaultConfigOffersEveryWeekDayMondayToSunday(): void
    {
        // Act
        $weekDayNames = (new WeekDayNameMapper(new OrderExperienceManagementConfig()))->mapWeekDaysToNames();

        // Assert
        $this->assertSame(SharedOrderExperienceManagementConfig::SUPPORTED_WEEK_DAYS, array_keys($weekDayNames));
    }
}
