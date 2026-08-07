<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Schedule\Review;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;
use JsonException;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Mapper\AddedItemShippingAddressMapperInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment\ScheduleAddressReader;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Schedule
 * @group Review
 * @group ScheduleAddressReaderTest
 * Add your own group annotations below this line
 */
class ScheduleAddressReaderTest extends Unit
{
    protected const string STORED_ADDRESS1 = 'Julie-Wolfthorn-Str.';

    protected const string QUOTE_ADDRESS1 = 'Kirncher Str.';

    public function testReturnsStoredLineAddressesBeforeQuoteItemAddresses(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createScheduleWithItemData(
            $this->encodeItemData(['address1' => static::STORED_ADDRESS1]),
        );
        $scheduleQuoteTransfer = $this->createQuoteWithShippingAddress(static::QUOTE_ADDRESS1);

        // Act
        $addressTransfers = $this->createReader()->getAddressTransfers($recurringScheduleTransfer, $scheduleQuoteTransfer);

        // Assert
        $this->assertSame(
            [static::STORED_ADDRESS1, static::QUOTE_ADDRESS1],
            array_map(
                static fn (AddressTransfer $addressTransfer): ?string => $addressTransfer->getAddress1(),
                $addressTransfers,
            ),
        );
    }

    /**
     * @dataProvider provideItemDataWithoutAnAddress
     */
    public function testSkipsAScheduleLineWithoutAStoredAddress(?string $itemData): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createScheduleWithItemData($itemData);

        // Act
        $addressTransfers = $this->createReader()->getAddressTransfers($recurringScheduleTransfer, new QuoteTransfer());

        // Assert
        $this->assertSame([], $addressTransfers);
    }

    /**
     * @return array<string, array<?string>>
     */
    public function provideItemDataWithoutAnAddress(): array
    {
        return [
            'no item data' => [null],
            'empty item data' => [''],
            'no shipment' => ['{"sku":"215_123"}'],
            'shipment without an address' => ['{"shipment":{"method":{"idShipmentMethod":1}}}'],
            'empty address' => ['{"shipment":{"shippingAddress":[]}}'],
            'address is not an array' => ['{"shipment":{"shippingAddress":"Kirncher Str."}}'],
        ];
    }

    /**
     * The stored payload is written by this module, so malformed JSON is a defect worth surfacing rather than
     * silently dropping the address the buyer already ships to.
     */
    public function testThrowsOnMalformedStoredItemData(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createScheduleWithItemData('{"shipment":');

        // Assert
        $this->expectException(JsonException::class);

        // Act
        $this->createReader()->getAddressTransfers($recurringScheduleTransfer, new QuoteTransfer());
    }

    public function testSkipsAQuoteItemWithoutAShipment(): void
    {
        // Arrange
        $scheduleQuoteTransfer = (new QuoteTransfer())->addItem(new ItemTransfer());

        // Act
        $addressTransfers = $this->createReader()->getAddressTransfers(new RecurringScheduleTransfer(), $scheduleQuoteTransfer);

        // Assert
        $this->assertSame([], $addressTransfers);
    }

    public function testSkipsAQuoteItemWhoseShipmentHasNoAddress(): void
    {
        // Arrange
        $scheduleQuoteTransfer = (new QuoteTransfer())->addItem(
            (new ItemTransfer())->setShipment(new ShipmentTransfer()),
        );

        // Act
        $addressTransfers = $this->createReader()->getAddressTransfers(new RecurringScheduleTransfer(), $scheduleQuoteTransfer);

        // Assert
        $this->assertSame([], $addressTransfers);
    }

    /**
     * Only the fields the buyer actually set are forwarded, so an untouched transfer contributes no defaults.
     */
    public function testPassesOnlyTheModifiedQuoteAddressFieldsToTheMapper(): void
    {
        // Arrange
        $addedItemShippingAddressMapperMock = $this->createMock(AddedItemShippingAddressMapperInterface::class);
        $addedItemShippingAddressMapperMock
            ->expects($this->once())
            ->method('mapStoredAddressDataToAddressTransfer')
            ->with(['address1' => static::QUOTE_ADDRESS1], $this->anything())
            ->willReturn(new AddressTransfer());

        $scheduleAddressReader = new ScheduleAddressReader($addedItemShippingAddressMapperMock);

        // Act
        $addressTransfers = $scheduleAddressReader->getAddressTransfers(
            new RecurringScheduleTransfer(),
            $this->createQuoteWithShippingAddress(static::QUOTE_ADDRESS1),
        );

        // Assert
        $this->assertCount(1, $addressTransfers);
    }

    public function testReturnsNothingForAScheduleAndQuoteWithoutItems(): void
    {
        // Act
        $addressTransfers = $this->createReader()->getAddressTransfers(new RecurringScheduleTransfer(), new QuoteTransfer());

        // Assert
        $this->assertSame([], $addressTransfers);
    }

    protected function createReader(): ScheduleAddressReader
    {
        $addedItemShippingAddressMapperMock = $this->createMock(AddedItemShippingAddressMapperInterface::class);
        $addedItemShippingAddressMapperMock
            ->method('mapStoredAddressDataToAddressTransfer')
            ->willReturnCallback(
                static fn (array $addressData): AddressTransfer => (new AddressTransfer())
                    ->setAddress1($addressData['address1'] ?? null),
            );

        return new ScheduleAddressReader($addedItemShippingAddressMapperMock);
    }

    protected function createScheduleWithItemData(?string $itemData): RecurringScheduleTransfer
    {
        return (new RecurringScheduleTransfer())->addItem(
            (new RecurringScheduleItemTransfer())->setItemData($itemData),
        );
    }

    protected function createQuoteWithShippingAddress(string $address1): QuoteTransfer
    {
        return (new QuoteTransfer())->addItem(
            (new ItemTransfer())->setShipment(
                (new ShipmentTransfer())->setShippingAddress((new AddressTransfer())->setAddress1($address1)),
            ),
        );
    }

    /**
     * @param array<string, mixed> $addressData
     */
    protected function encodeItemData(array $addressData): string
    {
        return json_encode([
            ItemTransfer::SHIPMENT => [ShipmentTransfer::SHIPPING_ADDRESS => $addressData],
        ], JSON_THROW_ON_ERROR);
    }
}
