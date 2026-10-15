<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Communication\Formatter;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Spryker\DecimalObject\Decimal;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Formatter\PriceToleranceLabelFormatter;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Communication
 * @group Formatter
 * @group PriceToleranceLabelFormatterTest
 * Add your own group annotations below this line
 */
class PriceToleranceLabelFormatterTest extends Unit
{
    /**
     * @see \Spryker\Shared\Price\PriceConfig::PRICE_MODE_NET
     *
     * @var string
     */
    protected const PRICE_MODE_NET = 'NET_MODE';

    /**
     * @see \Spryker\Shared\Price\PriceConfig::PRICE_MODE_GROSS
     *
     * @var string
     */
    protected const PRICE_MODE_GROSS = 'GROSS_MODE';

    /**
     * @var string
     */
    protected const CURRENCY_ISO_CODE = 'EUR';

    public function testFormatsTheItemToleranceAsPercentAndUnitPriceThreshold(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule(static::PRICE_MODE_GROSS);
        $recurringScheduleItemTransfer = (new RecurringScheduleItemTransfer())
            ->setReferenceGrossPrice(29847)
            ->setPriceTolerancePercent(Decimal::create('0.25'));

        // Act
        $label = $this->createFormatter()->formatItemTolerance($recurringScheduleItemTransfer, $recurringScheduleTransfer);

        // Assert
        $this->assertSame('0.25% (299.21 EUR)', $label);
    }

    public function testTrimsTrailingZerosFromTheStoredPercent(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule(static::PRICE_MODE_GROSS);
        $recurringScheduleItemTransfer = (new RecurringScheduleItemTransfer())
            ->setReferenceGrossPrice(100000)
            ->setPriceTolerancePercent(Decimal::create('10.00'));

        // Act
        $label = $this->createFormatter()->formatItemTolerance($recurringScheduleItemTransfer, $recurringScheduleTransfer);

        // Assert
        $this->assertSame('10% (1,100.00 EUR)', $label);
    }

    public function testUsesTheNetPriceInNetMode(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule(static::PRICE_MODE_NET);
        $recurringScheduleItemTransfer = (new RecurringScheduleItemTransfer())
            ->setReferenceGrossPrice(20000)
            ->setReferenceNetPrice(10000)
            ->setPriceTolerancePercent(Decimal::create('10'));

        // Act
        $label = $this->createFormatter()->formatItemTolerance($recurringScheduleItemTransfer, $recurringScheduleTransfer);

        // Assert
        $this->assertSame('10% (110.00 EUR)', $label);
    }

    public function testPrefersTheNextDeliveryPriceOverTheReferencePrice(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule(static::PRICE_MODE_GROSS);
        $recurringScheduleItemTransfer = (new RecurringScheduleItemTransfer())
            ->setReferenceGrossPrice(10000)
            ->setNextDeliveryGrossPrice(20000)
            ->setPriceTolerancePercent(Decimal::create('10'));

        // Act
        $label = $this->createFormatter()->formatItemTolerance($recurringScheduleItemTransfer, $recurringScheduleTransfer);

        // Assert
        $this->assertSame('10% (220.00 EUR)', $label);
    }

    public function testReturnsAnEmptyItemLabelWhenProductPriceChangesAreIgnored(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule(static::PRICE_MODE_GROSS)
            ->setPriceTolerancePercent(Decimal::create('10'))
            ->setIsProductPriceChangeIgnored(true);
        $recurringScheduleItemTransfer = (new RecurringScheduleItemTransfer())
            ->setReferenceGrossPrice(10000)
            ->setPriceTolerancePercent(Decimal::create('1'));

        // Act
        $label = $this->createFormatter()->formatItemTolerance($recurringScheduleItemTransfer, $recurringScheduleTransfer);

        // Assert
        $this->assertSame('', $label);
    }

    public function testFormatsTheItemToleranceWhenProductPriceChangesAreIgnoredWithoutAnOrderTolerance(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule(static::PRICE_MODE_GROSS)
            ->setPriceTolerancePercent(Decimal::create('0'))
            ->setIsProductPriceChangeIgnored(true);
        $recurringScheduleItemTransfer = (new RecurringScheduleItemTransfer())
            ->setReferenceGrossPrice(10000)
            ->setPriceTolerancePercent(Decimal::create('1'));

        // Act
        $label = $this->createFormatter()->formatItemTolerance($recurringScheduleItemTransfer, $recurringScheduleTransfer);

        // Assert
        $this->assertSame('1% (101.00 EUR)', $label);
    }

    /**
     * @dataProvider provideUnsetItemTolerances
     */
    public function testReturnsAnEmptyLabelWhenTheItemHasNoTolerance(?Decimal $tolerancePercent): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule(static::PRICE_MODE_GROSS);
        $recurringScheduleItemTransfer = (new RecurringScheduleItemTransfer())
            ->setReferenceGrossPrice(10000)
            ->setPriceTolerancePercent($tolerancePercent);

        // Act
        $label = $this->createFormatter()->formatItemTolerance($recurringScheduleItemTransfer, $recurringScheduleTransfer);

        // Assert
        $this->assertSame('', $label);
    }

    /**
     * @return iterable<string, array<?\Spryker\DecimalObject\Decimal>>
     */
    public function provideUnsetItemTolerances(): iterable
    {
        yield 'null' => [null];
        yield 'zero' => [Decimal::create('0')];
        yield 'zero with scale' => [Decimal::create('0.00')];
    }

    public function testFallsBackToThePercentAloneWhenNoUnitPriceIsKnown(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule(static::PRICE_MODE_GROSS);
        $recurringScheduleItemTransfer = (new RecurringScheduleItemTransfer())
            ->setPriceTolerancePercent(Decimal::create('0.25'));

        // Act
        $label = $this->createFormatter()->formatItemTolerance($recurringScheduleItemTransfer, $recurringScheduleTransfer);

        // Assert
        $this->assertSame('0.25%', $label);
    }

    public function testFormatsTheOrderToleranceAsPercentOnly(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule(static::PRICE_MODE_GROSS)
            ->setPriceTolerancePercent(Decimal::create('0.25'));

        // Act
        $label = $this->createFormatter()->formatOrderTolerance($recurringScheduleTransfer);

        // Assert
        $this->assertSame('0.25%', $label);
    }

    /**
     * @dataProvider provideUnsetItemTolerances
     */
    public function testReportsTheOrderToleranceAsOffWhenNotSet(?Decimal $tolerancePercent): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule(static::PRICE_MODE_GROSS)
            ->setPriceTolerancePercent($tolerancePercent);

        // Act
        $label = $this->createFormatter()->formatOrderTolerance($recurringScheduleTransfer);

        // Assert
        $this->assertSame('Off', $label);
    }

    public function testReportsTheStateAsOnForAnOrderLevelToleranceAlone(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule(static::PRICE_MODE_GROSS)
            ->setPriceTolerancePercent(Decimal::create('2'))
            ->addItem((new RecurringScheduleItemTransfer())->setPriceTolerancePercent(Decimal::create('0')));

        // Act
        $state = $this->createFormatter()->formatToleranceState($recurringScheduleTransfer);

        // Assert
        $this->assertSame('On', $state);
    }

    public function testReportsTheStateAsOnForAnItemLevelToleranceAlone(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule(static::PRICE_MODE_GROSS)
            ->setPriceTolerancePercent(Decimal::create('0'))
            ->addItem((new RecurringScheduleItemTransfer())->setPriceTolerancePercent(Decimal::create('0')))
            ->addItem((new RecurringScheduleItemTransfer())->setPriceTolerancePercent(Decimal::create('0.25')));

        // Act
        $state = $this->createFormatter()->formatToleranceState($recurringScheduleTransfer);

        // Assert
        $this->assertSame('On', $state);
    }

    public function testReportsTheStateAsOffWhenNeitherLevelHasATolerance(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule(static::PRICE_MODE_GROSS)
            ->setPriceTolerancePercent(Decimal::create('0'))
            ->addItem((new RecurringScheduleItemTransfer())->setPriceTolerancePercent(Decimal::create('0')))
            ->addItem((new RecurringScheduleItemTransfer())->setPriceTolerancePercent(null));

        // Act
        $state = $this->createFormatter()->formatToleranceState($recurringScheduleTransfer);

        // Assert
        $this->assertSame('Off', $state);
    }

    public function testFormatsEveryItemToleranceKeyedByTheItemKey(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule(static::PRICE_MODE_GROSS)
            ->addItem(
                (new RecurringScheduleItemTransfer())
                    ->setReferenceGrossPrice(100000)
                    ->setPriceTolerancePercent(Decimal::create('10.00')),
            )
            ->addItem(
                (new RecurringScheduleItemTransfer())
                    ->setReferenceGrossPrice(29847)
                    ->setPriceTolerancePercent(Decimal::create('0')),
            );

        // Act
        $itemToleranceLabels = $this->createFormatter()->formatItemTolerances($recurringScheduleTransfer);

        // Assert
        $this->assertSame([0 => '10% (1,100.00 EUR)', 1 => ''], $itemToleranceLabels);
    }

    public function testFormatsNoItemToleranceForAScheduleWithoutItems(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule(static::PRICE_MODE_GROSS);

        // Act
        $itemToleranceLabels = $this->createFormatter()->formatItemTolerances($recurringScheduleTransfer);

        // Assert
        $this->assertSame([], $itemToleranceLabels);
    }

    protected function createFormatter(): PriceToleranceLabelFormatter
    {
        return new PriceToleranceLabelFormatter();
    }

    protected function createSchedule(string $priceMode): RecurringScheduleTransfer
    {
        return (new RecurringScheduleTransfer())
            ->setPriceMode($priceMode)
            ->setCurrencyIsoCode(static::CURRENCY_ISO_CODE);
    }
}
