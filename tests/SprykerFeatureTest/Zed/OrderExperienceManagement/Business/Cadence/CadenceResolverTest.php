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

    public function testAreOptionsRequiredIsAnsweredByTheMatchingCadenceTypePlugin(): void
    {
        // Arrange
        $cadenceResolver = $this->createCadenceResolver();

        // Act
        $areOptionsRequiredForByDays = $cadenceResolver->areOptionsRequired(
            SharedOrderExperienceManagementConfig::CADENCE_TYPE_BY_DAYS,
        );
        $areOptionsRequiredForWeekly = $cadenceResolver->areOptionsRequired(
            SharedOrderExperienceManagementConfig::CADENCE_TYPE_WEEKLY,
        );

        // Assert
        $this->assertTrue($areOptionsRequiredForByDays);
        $this->assertFalse($areOptionsRequiredForWeekly);
    }

    public function testIsValueRequiredIsAnsweredByTheMatchingCadenceTypePlugin(): void
    {
        // Arrange
        $cadenceResolver = $this->createCadenceResolver();

        // Act
        $isValueRequired = $cadenceResolver->isValueRequired(
            SharedOrderExperienceManagementConfig::CADENCE_TYPE_WEEKLY,
        );

        // Assert
        $this->assertFalse($isValueRequired);
    }

    /**
     * @dataProvider provideByDaysOptionsValidity
     *
     * @param array<string> $cadenceOptions
     */
    public function testAreOptionsValidDelegatesToTheByDaysCadenceTypePlugin(
        array $cadenceOptions,
        bool $expectedIsValid
    ): void {
        // Act
        $isValid = $this->createCadenceResolver()->areOptionsValid(
            SharedOrderExperienceManagementConfig::CADENCE_TYPE_BY_DAYS,
            $cadenceOptions,
        );

        // Assert
        $this->assertSame($expectedIsValid, $isValid);
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

    public function testAreOptionsValidReturnsTrueForCadenceTypeThatTakesNoOptions(): void
    {
        // Act
        $isValid = $this->createCadenceResolver()->areOptionsValid(
            SharedOrderExperienceManagementConfig::CADENCE_TYPE_WEEKLY,
            [],
        );

        // Assert
        $this->assertTrue($isValid);
    }

    /**
     * A cadence type nobody registered a plugin for has no contract to validate against, so the
     * resolver fails closed rather than waving the value through.
     */
    public function testValidationFailsClosedForAnUnregisteredCadenceType(): void
    {
        // Arrange
        $cadenceResolver = $this->createCadenceResolver();

        // Act
        $isValueValid = $cadenceResolver->isValueValid(static::CADENCE_TYPE_UNREGISTERED, 1);
        $areOptionsValid = $cadenceResolver->areOptionsValid(static::CADENCE_TYPE_UNREGISTERED, ['1']);

        // Assert
        $this->assertFalse($isValueValid);
        $this->assertFalse($areOptionsValid);
    }

    protected function createCadenceResolver(): CadenceResolver
    {
        return new CadenceResolver([
            new WeeklyCadenceTypePlugin(),
            new ByDaysCadenceTypePluginStub(),
        ]);
    }
}
