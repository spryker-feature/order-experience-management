<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Schedule\Mapper;

use Codeception\Test\Unit;
use DateTimeImmutable;
use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringOrderSettingsTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence\CadenceResolver;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Date\FirstTriggerDateResolver;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Mapper\RecurringScheduleMapper;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\WeeklyCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Schedule
 * @group Mapper
 * @group RecurringScheduleMapperTest
 * Add your own group annotations below this line
 */
class RecurringScheduleMapperTest extends Unit
{
    protected const string DATE_FORMAT = 'Y-m-d';

    protected const string LOCALE_NAME = 'de_DE';

    /**
     * One weekly cadence interval, matching WeeklyCadenceTypePlugin.
     */
    protected const string ONE_CADENCE_INTERVAL = '+7 days';

    public function testUsesChosenFutureStartDateAsFirstTrigger(): void
    {
        // Arrange
        $startDate = (new DateTimeImmutable('+30 days'))->format(static::DATE_FORMAT);
        $quoteTransfer = $this->createQuoteTransfer($startDate);

        // Act
        $recurringScheduleTransfer = $this->createMapper()->mapQuoteToRecurringSchedule(
            $quoteTransfer,
            new CheckoutResponseTransfer(),
            static::LOCALE_NAME,
        );

        // Assert - a future start date is the first recurring delivery itself, not one cadence later.
        $this->assertSame($startDate, $recurringScheduleTransfer->getFirstTriggerDate());
        $this->assertSame($startDate, $recurringScheduleTransfer->getNextTriggerDate());
    }

    public function testAdvancesFirstTriggerByOneCadenceIntervalWhenStartDateIsToday(): void
    {
        // Arrange
        $startDate = (new DateTimeImmutable('today'))->format(static::DATE_FORMAT);
        $expectedFirstTriggerDate = (new DateTimeImmutable('today'))->modify(static::ONE_CADENCE_INTERVAL)->format(static::DATE_FORMAT);
        $quoteTransfer = $this->createQuoteTransfer($startDate);

        // Act
        $recurringScheduleTransfer = $this->createMapper()->mapQuoteToRecurringSchedule(
            $quoteTransfer,
            new CheckoutResponseTransfer(),
            static::LOCALE_NAME,
        );

        // Assert - the order placed at checkout covers today, so the first recurring delivery is one interval away.
        $this->assertSame($expectedFirstTriggerDate, $recurringScheduleTransfer->getFirstTriggerDate());
        $this->assertSame($expectedFirstTriggerDate, $recurringScheduleTransfer->getNextTriggerDate());
        $this->assertGreaterThan($startDate, $recurringScheduleTransfer->getFirstTriggerDate());
    }

    public function testFallsBackToTodayPlusCadenceWhenStartDateIsNotProvided(): void
    {
        // Arrange
        $expectedFirstTriggerDate = (new DateTimeImmutable('today'))->modify(static::ONE_CADENCE_INTERVAL)->format(static::DATE_FORMAT);
        $quoteTransfer = $this->createQuoteTransfer(null);

        // Act
        $recurringScheduleTransfer = $this->createMapper()->mapQuoteToRecurringSchedule(
            $quoteTransfer,
            new CheckoutResponseTransfer(),
            static::LOCALE_NAME,
        );

        // Assert - the checkout validator rejects an empty start date, so this branch is defensive only.
        $this->assertSame($expectedFirstTriggerDate, $recurringScheduleTransfer->getFirstTriggerDate());
        $this->assertSame($expectedFirstTriggerDate, $recurringScheduleTransfer->getNextTriggerDate());
    }

    protected function createQuoteTransfer(?string $startDate): QuoteTransfer
    {
        $recurringOrderSettingsTransfer = (new RecurringOrderSettingsTransfer())
            ->setCadenceType(SharedOrderExperienceManagementConfig::CADENCE_TYPE_WEEKLY)
            ->setStartDate($startDate);

        return (new QuoteTransfer())
            ->setCustomer((new CustomerTransfer())->setIdCustomer(1))
            ->setStore((new StoreTransfer())->setName('DE'))
            ->setCurrency((new CurrencyTransfer())->setCode('EUR'))
            ->setPriceMode('GROSS_MODE')
            ->setRecurringOrderSettings($recurringOrderSettingsTransfer);
    }

    protected function createMapper(): RecurringScheduleMapper
    {
        $cadenceResolver = new CadenceResolver([new WeeklyCadenceTypePlugin()]);

        $utilEncodingServiceMock = $this->createMock(UtilEncodingServiceInterface::class);
        $utilEncodingServiceMock->method('encodeJson')->willReturn('{}');

        $configMock = $this->createMock(OrderExperienceManagementConfig::class);
        $configMock->method('getDefaultScheduleStatus')->willReturn(SharedOrderExperienceManagementConfig::STATUS_DRAFT);
        $configMock->method('getDefaultNotificationWindowHours')->willReturn(48);

        return new RecurringScheduleMapper(
            $cadenceResolver,
            $utilEncodingServiceMock,
            $configMock,
            new FirstTriggerDateResolver($cadenceResolver),
        );
    }
}
