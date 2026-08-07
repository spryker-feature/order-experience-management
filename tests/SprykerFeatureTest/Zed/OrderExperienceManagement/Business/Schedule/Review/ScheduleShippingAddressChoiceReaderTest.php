<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Schedule\Review;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleShippingAddressChoiceTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\PlaceableQuoteDeserializerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment\AddedItemShippingAddressResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment\ScheduleShippingAddressChoiceReader;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Schedule
 * @group Review
 * @group ScheduleShippingAddressChoiceReaderTest
 * Add your own group annotations below this line
 */
class ScheduleShippingAddressChoiceReaderTest extends Unit
{
    protected const string QUOTE_DATA = '{"customer":{"email":"sonia@acme.com"}}';

    protected const string COMPANY_UNIT_ADDRESS_KEY = 'company_unit_address:13';

    protected const string SCHEDULE_ADDRESS_KEY = 'schedule:abc123';

    /**
     * The resolver returns a map keyed by choice key; the response transfer needs a plain list.
     */
    public function testReturnsTheChoicesAsAList(): void
    {
        // Arrange
        $scheduleShippingAddressChoiceReader = new ScheduleShippingAddressChoiceReader(
            $this->createDeserializerMock(1),
            $this->createResolverMock([
                static::COMPANY_UNIT_ADDRESS_KEY => (new RecurringScheduleShippingAddressChoiceTransfer())
                    ->setKey(static::COMPANY_UNIT_ADDRESS_KEY),
                static::SCHEDULE_ADDRESS_KEY => (new RecurringScheduleShippingAddressChoiceTransfer())
                    ->setKey(static::SCHEDULE_ADDRESS_KEY),
            ]),
        );

        // Act
        $choiceTransfers = $scheduleShippingAddressChoiceReader->getChoices($this->createSchedule(static::QUOTE_DATA));

        // Assert
        $this->assertSame([0, 1], array_keys($choiceTransfers));
        $this->assertSame(static::COMPANY_UNIT_ADDRESS_KEY, $choiceTransfers[0]->getKey());
        $this->assertSame(static::SCHEDULE_ADDRESS_KEY, $choiceTransfers[1]->getKey());
    }

    /**
     * @dataProvider provideScheduleWithoutQuoteData
     */
    public function testSkipsTheDeserializationWithoutQuoteData(?string $quoteData): void
    {
        // Arrange
        $scheduleShippingAddressChoiceReader = new ScheduleShippingAddressChoiceReader(
            $this->createDeserializerMock(0),
            $this->createUnusedResolverMock(),
        );

        // Act
        $choiceTransfers = $scheduleShippingAddressChoiceReader->getChoices($this->createSchedule($quoteData));

        // Assert
        $this->assertSame([], $choiceTransfers);
    }

    /**
     * @return array<string, array<?string>>
     */
    public function provideScheduleWithoutQuoteData(): array
    {
        return [
            'no quote data' => [null],
            'empty quote data' => [''],
        ];
    }

    public function testReturnsNothingWhenTheResolverOffersNoChoice(): void
    {
        // Arrange
        $scheduleShippingAddressChoiceReader = new ScheduleShippingAddressChoiceReader(
            $this->createDeserializerMock(1),
            $this->createResolverMock([]),
        );

        // Act
        $choiceTransfers = $scheduleShippingAddressChoiceReader->getChoices($this->createSchedule(static::QUOTE_DATA));

        // Assert
        $this->assertSame([], $choiceTransfers);
    }

    public function testPassesTheDeserializedQuoteToTheResolver(): void
    {
        // Arrange
        $quoteTransfer = new QuoteTransfer();
        $recurringScheduleTransfer = $this->createSchedule(static::QUOTE_DATA);

        $placeableQuoteDeserializerMock = $this->createMock(PlaceableQuoteDeserializerInterface::class);
        $placeableQuoteDeserializerMock
            ->method('deserialize')
            ->with(static::QUOTE_DATA)
            ->willReturn($quoteTransfer);

        $addedItemShippingAddressResolverMock = $this->createMock(AddedItemShippingAddressResolverInterface::class);
        $addedItemShippingAddressResolverMock
            ->expects($this->once())
            ->method('getOwnedAddressChoices')
            ->with($recurringScheduleTransfer, $quoteTransfer)
            ->willReturn([]);

        $scheduleShippingAddressChoiceReader = new ScheduleShippingAddressChoiceReader(
            $placeableQuoteDeserializerMock,
            $addedItemShippingAddressResolverMock,
        );

        // Act
        $choiceTransfers = $scheduleShippingAddressChoiceReader->getChoices($recurringScheduleTransfer);

        // Assert
        $this->assertSame([], $choiceTransfers);
    }

    protected function createSchedule(?string $quoteData): RecurringScheduleTransfer
    {
        return (new RecurringScheduleTransfer())->setQuoteData($quoteData);
    }

    protected function createDeserializerMock(int $expectedCallCount): PlaceableQuoteDeserializerInterface
    {
        $placeableQuoteDeserializerMock = $this->createMock(PlaceableQuoteDeserializerInterface::class);
        $placeableQuoteDeserializerMock
            ->expects($this->exactly($expectedCallCount))
            ->method('deserialize')
            ->willReturn(new QuoteTransfer());

        return $placeableQuoteDeserializerMock;
    }

    /**
     * @param array<string, \Generated\Shared\Transfer\RecurringScheduleShippingAddressChoiceTransfer> $choiceTransfers
     */
    protected function createResolverMock(array $choiceTransfers): AddedItemShippingAddressResolverInterface
    {
        $addedItemShippingAddressResolverMock = $this->createMock(AddedItemShippingAddressResolverInterface::class);
        $addedItemShippingAddressResolverMock->method('getOwnedAddressChoices')->willReturn($choiceTransfers);

        return $addedItemShippingAddressResolverMock;
    }

    protected function createUnusedResolverMock(): AddedItemShippingAddressResolverInterface
    {
        $addedItemShippingAddressResolverMock = $this->createMock(AddedItemShippingAddressResolverInterface::class);
        $addedItemShippingAddressResolverMock->expects($this->never())->method('getOwnedAddressChoices');

        return $addedItemShippingAddressResolverMock;
    }
}
