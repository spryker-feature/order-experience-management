<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Cadence\Validator;

use Codeception\Test\Unit;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence\Validator\WeekDayOptionsValidator;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Cadence
 * @group Validator
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
    public function testAreWeekDaysValid(array $cadenceOptions, bool $expectedIsSuccess): void
    {
        // Act
        $cadenceValidationResponseTransfer = $this->createValidator()->areWeekDaysValid($cadenceOptions);

        // Assert
        $this->assertSame($expectedIsSuccess, $cadenceValidationResponseTransfer->getIsSuccess());
    }

    public function testReportsTheDaysRequiredMessageAndTheRequiredCadenceField(): void
    {
        // Act
        $cadenceValidationResponseTransfer = $this->createValidator()->areWeekDaysValid([]);

        // Assert
        $this->assertSame('recurring_orders.cadence.validation.days_required', $cadenceValidationResponseTransfer->getMessage());
        $this->assertSame(
            [SharedOrderExperienceManagementConfig::CADENCE_FIELD_CADENCE_OPTIONS],
            $cadenceValidationResponseTransfer->getRequiredCadenceFields(),
        );
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
        $cadenceValidationResponseTransfer = $this->createValidator()->areWeekDaysValid($cadenceOptions);

        // Assert
        $this->assertTrue($cadenceValidationResponseTransfer->getIsSuccess());
    }

    public function testRejectsAWeekDayTheProjectDoesNotOffer(): void
    {
        // Arrange
        $validator = $this->createValidator([
            SharedOrderExperienceManagementConfig::DAY_MONDAY,
            SharedOrderExperienceManagementConfig::DAY_FRIDAY,
        ]);

        // Act
        $cadenceValidationResponseTransfer = $validator->areWeekDaysValid([(string)SharedOrderExperienceManagementConfig::DAY_TUESDAY]);

        // Assert
        $this->assertFalse($cadenceValidationResponseTransfer->getIsSuccess());
    }

    public function testAcceptsAWeekDayTheProjectOffers(): void
    {
        // Arrange
        $validator = $this->createValidator([
            SharedOrderExperienceManagementConfig::DAY_MONDAY,
            SharedOrderExperienceManagementConfig::DAY_FRIDAY,
        ]);

        // Act
        $cadenceValidationResponseTransfer = $validator->areWeekDaysValid([(string)SharedOrderExperienceManagementConfig::DAY_FRIDAY]);

        // Assert
        $this->assertTrue($cadenceValidationResponseTransfer->getIsSuccess());
    }

    /**
     * @param array<int>|null $supportedWeekDays
     */
    protected function createValidator(?array $supportedWeekDays = null): WeekDayOptionsValidator
    {
        if ($supportedWeekDays === null) {
            return new WeekDayOptionsValidator(new OrderExperienceManagementConfig());
        }

        $configMock = $this->createMock(OrderExperienceManagementConfig::class);
        $configMock->method('getSupportedWeekDays')->willReturn($supportedWeekDays);

        return new WeekDayOptionsValidator($configMock);
    }
}
