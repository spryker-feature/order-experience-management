<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Cadence\Validator;

use Codeception\Test\Unit;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence\Validator\WeekIntervalValidator;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Cadence
 * @group Validator
 * @group WeekIntervalValidatorTest
 * Add your own group annotations below this line
 */
class WeekIntervalValidatorTest extends Unit
{
    /**
     * @dataProvider provideIntervalValidity
     */
    public function testIsIntervalValid(?int $cadenceValue, bool $expectedIsSuccess): void
    {
        // Act
        $cadenceValidationResponseTransfer = (new WeekIntervalValidator())->isIntervalValid($cadenceValue);

        // Assert
        $this->assertSame($expectedIsSuccess, $cadenceValidationResponseTransfer->getIsSuccess());
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function provideIntervalValidity(): array
    {
        return [
            'single week' => [1, true],
            'several weeks' => [12, true],
            'missing interval' => [null, false],
            'zero weeks' => [0, false],
            'negative interval' => [-1, false],
        ];
    }

    public function testReportsTheValueRequiredMessageAndTheRequiredCadenceField(): void
    {
        // Act
        $cadenceValidationResponseTransfer = (new WeekIntervalValidator())->isIntervalValid(null);

        // Assert
        $this->assertSame('recurring_orders.cadence.validation.value_required', $cadenceValidationResponseTransfer->getMessage());
        $this->assertSame(
            [SharedOrderExperienceManagementConfig::CADENCE_FIELD_CADENCE_VALUE],
            $cadenceValidationResponseTransfer->getRequiredCadenceFields(),
        );
    }
}
