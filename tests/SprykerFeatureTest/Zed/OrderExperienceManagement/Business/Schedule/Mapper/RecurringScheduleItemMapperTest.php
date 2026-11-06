<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Schedule\Mapper;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ConfigurableBundleTemplateTransfer;
use Generated\Shared\Transfer\ConfiguredBundleTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Mapper\RecurringScheduleItemMapper;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Schedule
 * @group Mapper
 * @group RecurringScheduleItemMapperTest
 * Add your own group annotations below this line
 */
class RecurringScheduleItemMapperTest extends Unit
{
    protected const int ID_RECURRING_SCHEDULE = 7;

    protected const string CONFIGURABLE_BUNDLE_NAME = 'configurable_bundle.templates.all-in.name';

    public function testMapItemToRecurringScheduleItemCastsStringReferencePricesToInt(): void
    {
        // Arrange - the calculator can populate the core (non-strict) ItemTransfer prices as strings,
        // while the strict RecurringScheduleItemTransfer stores them as int; the mapper must normalize.
        $itemTransfer = (new ItemTransfer())->fromArray([
            ItemTransfer::SKU => 'sku-1',
            ItemTransfer::QUANTITY => 1,
            ItemTransfer::UNIT_GROSS_PRICE => '500',
            ItemTransfer::UNIT_NET_PRICE => '450',
        ], true);

        // Act
        $recurringScheduleItemTransfer = $this->createMapper()->mapItemToRecurringScheduleItem(
            $itemTransfer,
            static::ID_RECURRING_SCHEDULE,
            [],
        );

        // Assert
        $this->assertSame(500, $recurringScheduleItemTransfer->getReferenceGrossPrice());
        $this->assertSame(450, $recurringScheduleItemTransfer->getReferenceNetPrice());
    }

    public function testGivenAnItemOfAConfiguredBundleWhenMapItemToRecurringScheduleItemThenTheConfigurableBundleNameIsMapped(): void
    {
        // Arrange
        $itemTransfer = (new ItemTransfer())
            ->setSku('sku-1')
            ->setQuantity(1)
            ->setConfiguredBundle(
                (new ConfiguredBundleTransfer())
                    ->setTemplate((new ConfigurableBundleTemplateTransfer())->setName(static::CONFIGURABLE_BUNDLE_NAME)),
            );

        // Act
        $recurringScheduleItemTransfer = $this->createMapper()->mapItemToRecurringScheduleItem(
            $itemTransfer,
            static::ID_RECURRING_SCHEDULE,
            [],
        );

        // Assert
        $this->assertSame(static::CONFIGURABLE_BUNDLE_NAME, $recurringScheduleItemTransfer->getConfigurableBundleName());
    }

    public function testGivenAnItemOutsideAConfiguredBundleWhenMapItemToRecurringScheduleItemThenNoConfigurableBundleNameIsMapped(): void
    {
        // Arrange
        $itemTransfer = (new ItemTransfer())
            ->setSku('sku-1')
            ->setQuantity(1);

        // Act
        $recurringScheduleItemTransfer = $this->createMapper()->mapItemToRecurringScheduleItem(
            $itemTransfer,
            static::ID_RECURRING_SCHEDULE,
            [],
        );

        // Assert
        $this->assertNull($recurringScheduleItemTransfer->getConfigurableBundleName());
    }

    protected function createMapper(): RecurringScheduleItemMapper
    {
        $utilEncodingServiceMock = $this->createMock(UtilEncodingServiceInterface::class);
        $utilEncodingServiceMock->method('encodeJson')->willReturn('{}');

        return new RecurringScheduleItemMapper($utilEncodingServiceMock);
    }
}
