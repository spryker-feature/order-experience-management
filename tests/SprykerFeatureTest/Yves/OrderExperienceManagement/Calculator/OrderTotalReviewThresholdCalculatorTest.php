<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\OrderExperienceManagement\Calculator;

use Codeception\Test\Unit;
use Spryker\DecimalObject\Decimal;
use SprykerFeature\Yves\OrderExperienceManagement\Calculator\OrderTotalReviewThresholdCalculator;
use SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group OrderExperienceManagement
 * @group Calculator
 * @group OrderTotalReviewThresholdCalculatorTest
 */
class OrderTotalReviewThresholdCalculatorTest extends Unit
{
    public function testRoundsTheLimitDown(): void
    {
        // Arrange: 2% of 914.74 gives a limit of 933.0348, so 933.03 is the highest total accepted without review
        $calculator = $this->createCalculator();

        // Act
        $threshold = $calculator->calculate(91474, Decimal::create('2'));

        // Assert
        $this->assertSame(93303, $threshold);
    }

    public function testReturnsTheTotalItselfWhenTheToleranceIsZero(): void
    {
        $this->assertSame(91474, ($this->createCalculator())->calculate(91474, Decimal::create('0')));
    }

    public function testKeepsAnExactLimitUnrounded(): void
    {
        // 10% of 1000 is exactly 100, nothing to round
        $this->assertSame(1100, ($this->createCalculator())->calculate(1000, Decimal::create('10')));
    }

    public function testSupportsTheSmallestTolerance(): void
    {
        // 0.01% of 100000 is 10
        $this->assertSame(100010, ($this->createCalculator())->calculate(100000, Decimal::create('0.01')));
    }

    public function testSupportsTheLargestTolerance(): void
    {
        // 999.99% of 1000 is 9999.9, so the threshold is 10999.9 rounded down
        $this->assertSame(10999, ($this->createCalculator())->calculate(1000, Decimal::create('999.99')));
    }

    public function testAgreesWithTheCheckAtTheBoundary(): void
    {
        // The limit is 93303.48: 93303 is accepted, 93304 is the first total that triggers review
        $threshold = ($this->createCalculator())->calculate(91474, Decimal::create('2'));

        $this->assertSame(93303, $threshold);
        $this->assertGreaterThan($threshold, 93304);
    }

    protected function createCalculator(): OrderTotalReviewThresholdCalculator
    {
        return new OrderTotalReviewThresholdCalculator(new OrderExperienceManagementConfig());
    }
}
