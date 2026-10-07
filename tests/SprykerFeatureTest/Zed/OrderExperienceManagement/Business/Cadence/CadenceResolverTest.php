<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Cadence;

use Codeception\Test\Unit;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence\CadenceResolver;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\EveryNWeeksCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\WeeklyCadenceTypePlugin;
use SprykerFeatureTest\Zed\OrderExperienceManagement\ByDaysCadenceTypePluginStub;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Cadence
 * @group CadenceResolverTest
 * Add your own group annotations below this line
 */
class CadenceResolverTest extends Unit
{
    protected const string CADENCE_TYPE_UNREGISTERED = 'quarterly';

    public function testValidationResponseReportsTheRequiredCadenceFieldsOfTheMatchingPlugin(): void
    {
        // Arrange
        $cadenceResolver = $this->createCadenceResolver();

        // Act
        $byDaysResponseTransfer = $cadenceResolver->isCadenceValid(
            SharedOrderExperienceManagementConfig::CADENCE_TYPE_BY_DAYS,
            null,
            ['1'],
        );
        $everyNWeeksResponseTransfer = $cadenceResolver->isCadenceValid(
            SharedOrderExperienceManagementConfig::CADENCE_TYPE_EVERY_N_WEEKS,
            3,
            [],
        );
        $weeklyResponseTransfer = $cadenceResolver->isCadenceValid(
            SharedOrderExperienceManagementConfig::CADENCE_TYPE_WEEKLY,
            null,
            [],
        );

        // Assert
        $this->assertSame(
            [SharedOrderExperienceManagementConfig::CADENCE_FIELD_CADENCE_OPTIONS],
            $byDaysResponseTransfer->getRequiredCadenceFields(),
        );
        $this->assertSame(
            [SharedOrderExperienceManagementConfig::CADENCE_FIELD_CADENCE_VALUE],
            $everyNWeeksResponseTransfer->getRequiredCadenceFields(),
        );
        $this->assertSame([], $weeklyResponseTransfer->getRequiredCadenceFields());
    }

    /**
     * @dataProvider provideByDaysOptionsValidity
     *
     * @param array<string> $cadenceOptions
     */
    public function testIsCadenceValidDelegatesToTheByDaysCadenceTypePlugin(
        array $cadenceOptions,
        bool $expectedIsSuccess
    ): void {
        // Act
        $cadenceValidationResponseTransfer = $this->createCadenceResolver()->isCadenceValid(
            SharedOrderExperienceManagementConfig::CADENCE_TYPE_BY_DAYS,
            null,
            $cadenceOptions,
        );

        // Assert
        $this->assertSame($expectedIsSuccess, $cadenceValidationResponseTransfer->getIsSuccess());
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function provideByDaysOptionsValidity(): array
    {
        return [
            'single valid day' => [['1'], true],
            'several valid days' => [['1', '4', '7'], true],
            'empty selection' => [[], false],
            'value outside the ISO weekday range' => [['8'], false],
        ];
    }

    public function testIsCadenceValidCarriesTheFailureMessageFromThePlugin(): void
    {
        // Act
        $cadenceValidationResponseTransfer = $this->createCadenceResolver()->isCadenceValid(
            SharedOrderExperienceManagementConfig::CADENCE_TYPE_EVERY_N_WEEKS,
            null,
            [],
        );

        // Assert
        $this->assertFalse($cadenceValidationResponseTransfer->getIsSuccess());
        $this->assertSame(
            'recurring_orders.cadence.validation.value_required',
            $cadenceValidationResponseTransfer->getMessage(),
        );
    }

    public function testIsCadenceValidReturnsSuccessForCadenceTypeThatTakesNoParameters(): void
    {
        // Act
        $cadenceValidationResponseTransfer = $this->createCadenceResolver()->isCadenceValid(
            SharedOrderExperienceManagementConfig::CADENCE_TYPE_WEEKLY,
            null,
            [],
        );

        // Assert
        $this->assertTrue($cadenceValidationResponseTransfer->getIsSuccess());
    }

    public function testValidationFailsClosedForAnUnregisteredCadenceType(): void
    {
        // Act
        $cadenceValidationResponseTransfer = $this->createCadenceResolver()->isCadenceValid(
            static::CADENCE_TYPE_UNREGISTERED,
            1,
            ['1'],
        );

        // Assert
        $this->assertFalse($cadenceValidationResponseTransfer->getIsSuccess());
        $this->assertSame([], $cadenceValidationResponseTransfer->getRequiredCadenceFields());
    }

    protected function createCadenceResolver(): CadenceResolver
    {
        return new CadenceResolver([
            new WeeklyCadenceTypePlugin(),
            new EveryNWeeksCadenceTypePlugin(),
            new ByDaysCadenceTypePluginStub(),
        ]);
    }
}
