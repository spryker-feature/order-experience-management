<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Cadence;

use Codeception\Test\Unit;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence\WeekDayOptionsValidator;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Cadence
 * @group WeekDayOptionsValidatorTest
 * Add your own group annotations below this line
 */
class WeekDayOptionsValidatorTest extends Unit
{
    /**
     * @dataProvider provideWeekDayValidity
     *
     * @param array<string> $cadenceOptions
     */
    public function testAreWeekDaysValid(array $cadenceOptions, bool $expectedIsValid): void
    {
        // Act
        $isValid = (new WeekDayOptionsValidator())->areWeekDaysValid($cadenceOptions);

        // Assert
        $this->assertSame($expectedIsValid, $isValid);
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function provideWeekDayValidity(): array
    {
        return [
            'single valid day' => [['1'], true],
            'several valid days' => [['1', '4', '7'], true],
            'empty selection' => [[], false],
            'zero is not an ISO weekday' => [['0'], false],
            'eight is not an ISO weekday' => [['8'], false],
            'non-numeric value' => [['monday'], false],
            'one invalid value poisons the whole set' => [['1', '9'], false],
        ];
    }

    public function testEverySupportedWeekDayIsAcceptedAsValid(): void
    {
        // Arrange
        $cadenceOptions = array_map('strval', SharedOrderExperienceManagementConfig::SUPPORTED_WEEK_DAYS);

        // Act
        $isValid = (new WeekDayOptionsValidator())->areWeekDaysValid($cadenceOptions);

        // Assert
        $this->assertTrue($isValid);
    }
}
