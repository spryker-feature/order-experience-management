<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Cadence;

use Codeception\Test\Unit;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence\WeekDayOptionsReader;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Cadence
 * @group WeekDayOptionsReaderTest
 * Add your own group annotations below this line
 */
class WeekDayOptionsReaderTest extends Unit
{
    public function testExtractWeekDaysSortsAndDeduplicates(): void
    {
        // Act
        $weekDays = (new WeekDayOptionsReader())->extractWeekDays(['4', '1', '4']);

        // Assert
        $this->assertSame([1, 4], $weekDays);
    }

    public function testExtractWeekDaysSkipsValuesOutsideTheSupportedRange(): void
    {
        // Act
        $weekDays = (new WeekDayOptionsReader())->extractWeekDays(['0', '3', '8', 'monday', '']);

        // Assert
        $this->assertSame([3], $weekDays);
    }

    public function testExtractWeekDaysReturnsEmptyArrayForEntirelyUnrelatedOptions(): void
    {
        // Act
        $weekDays = (new WeekDayOptionsReader())->extractWeekDays(['first-of-month', 'last-of-month']);

        // Assert
        $this->assertSame([], $weekDays);
    }
}
