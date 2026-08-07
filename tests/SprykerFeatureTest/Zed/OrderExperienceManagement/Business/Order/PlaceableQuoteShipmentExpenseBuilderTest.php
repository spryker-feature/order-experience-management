<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Order;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ExpenseTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\ShipmentMethodTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;
use Spryker\Service\Shipment\ShipmentServiceInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\PlaceableQuoteShipmentExpenseBuilder;
use SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Business
 * @group Order
 * @group PlaceableQuoteShipmentExpenseBuilderTest
 * Add your own group annotations below this line
 */
class PlaceableQuoteShipmentExpenseBuilderTest extends Unit
{
    protected const string SHIPMENT_EXPENSE_TYPE = 'SHIPMENT_EXPENSE_TYPE';

    protected const string PRICE_MODE_NET = 'NET_MODE';

    protected const string PRICE_MODE_GROSS = 'GROSS_MODE';

    protected const int ID_SHIPMENT_METHOD_EXPRESS = 2;

    protected const string SHIPMENT_METHOD_NAME_EXPRESS = 'Express';

    protected OrderExperienceManagementBusinessTester $tester;

    public function testAppendsExpenseForItemShipmentGroupWithoutExpense(): void
    {
        // Arrange — an added item carries a shipment method whose expense is absent from the quote data.
        $quoteTransfer = (new QuoteTransfer())
            ->setPriceMode(static::PRICE_MODE_NET)
            ->addItem((new ItemTransfer())->setShipment($this->createShipment(static::ID_SHIPMENT_METHOD_EXPRESS)));

        $recurringScheduleTransfer = (new RecurringScheduleTransfer())->addItem(
            (new RecurringScheduleItemTransfer())
                ->setIdShipmentMethod(static::ID_SHIPMENT_METHOD_EXPRESS)
                ->setShipmentUnitNetPrice(590)
                ->setShipmentUnitGrossPrice(590),
        );

        // Act
        $quoteTransfer = $this->createBuilder()->appendMissingShipmentExpenses($quoteTransfer, $recurringScheduleTransfer);

        // Assert
        $this->assertCount(1, $quoteTransfer->getExpenses());

        $expenseTransfer = $quoteTransfer->getExpenses()->offsetGet(0);
        $this->assertSame(static::SHIPMENT_EXPENSE_TYPE, $expenseTransfer->getType());
        $this->assertSame(static::SHIPMENT_METHOD_NAME_EXPRESS, $expenseTransfer->getName());
        $this->assertSame(590, $expenseTransfer->getUnitNetPrice());
        $this->assertSame(0, $expenseTransfer->getUnitGrossPrice());
        $this->assertSame(590, $expenseTransfer->getSumNetPrice());
        $this->assertNotNull($expenseTransfer->getShipment());
    }

    public function testDoesNotDuplicateExpenseForCoveredShipmentGroup(): void
    {
        // Arrange — the original checkout expense for the same shipment group survived in the quote data.
        $shipmentTransfer = $this->createShipment(static::ID_SHIPMENT_METHOD_EXPRESS);

        $quoteTransfer = (new QuoteTransfer())
            ->setPriceMode(static::PRICE_MODE_NET)
            ->addExpense((new ExpenseTransfer())->setType(static::SHIPMENT_EXPENSE_TYPE)->setShipment($shipmentTransfer))
            ->addItem((new ItemTransfer())->setShipment($shipmentTransfer));

        // Act
        $quoteTransfer = $this->createBuilder()->appendMissingShipmentExpenses($quoteTransfer, new RecurringScheduleTransfer());

        // Assert
        $this->assertCount(1, $quoteTransfer->getExpenses());
    }

    public function testAppendsSingleExpenseForItemsSharingShipmentGroup(): void
    {
        // Arrange
        $quoteTransfer = (new QuoteTransfer())
            ->setPriceMode(static::PRICE_MODE_GROSS)
            ->addItem((new ItemTransfer())->setShipment($this->createShipment(static::ID_SHIPMENT_METHOD_EXPRESS)))
            ->addItem((new ItemTransfer())->setShipment($this->createShipment(static::ID_SHIPMENT_METHOD_EXPRESS)));

        // Act
        $quoteTransfer = $this->createBuilder()->appendMissingShipmentExpenses($quoteTransfer, new RecurringScheduleTransfer());

        // Assert
        $this->assertCount(1, $quoteTransfer->getExpenses());
    }

    public function testSkipsItemsWithoutShipmentMethod(): void
    {
        // Arrange
        $quoteTransfer = (new QuoteTransfer())
            ->setPriceMode(static::PRICE_MODE_GROSS)
            ->addItem(new ItemTransfer())
            ->addItem((new ItemTransfer())->setShipment(new ShipmentTransfer()));

        // Act
        $quoteTransfer = $this->createBuilder()->appendMissingShipmentExpenses($quoteTransfer, new RecurringScheduleTransfer());

        // Assert
        $this->assertCount(0, $quoteTransfer->getExpenses());
    }

    public function testFallsBackToMethodStorePriceWhenScheduleStoresNoShipmentPrice(): void
    {
        // Arrange
        $quoteTransfer = (new QuoteTransfer())
            ->setPriceMode(static::PRICE_MODE_GROSS)
            ->addItem((new ItemTransfer())->setShipment($this->createShipment(static::ID_SHIPMENT_METHOD_EXPRESS, 790)));

        // Act
        $quoteTransfer = $this->createBuilder()->appendMissingShipmentExpenses($quoteTransfer, new RecurringScheduleTransfer());

        // Assert
        $expenseTransfer = $quoteTransfer->getExpenses()->offsetGet(0);
        $this->assertSame(790, $expenseTransfer->getUnitGrossPrice());
        $this->assertSame(0, $expenseTransfer->getUnitNetPrice());
    }

    protected function createShipment(int $idShipmentMethod, ?int $storeCurrencyPrice = null): ShipmentTransfer
    {
        return (new ShipmentTransfer())->setMethod(
            (new ShipmentMethodTransfer())
                ->setIdShipmentMethod($idShipmentMethod)
                ->setName(static::SHIPMENT_METHOD_NAME_EXPRESS)
                ->setStoreCurrencyPrice($storeCurrencyPrice),
        );
    }

    protected function createBuilder(): PlaceableQuoteShipmentExpenseBuilder
    {
        $shipmentServiceMock = $this->createMock(ShipmentServiceInterface::class);
        $shipmentServiceMock->method('getShipmentHashKey')->willReturnCallback(
            static fn (ShipmentTransfer $shipmentTransfer): string => sprintf(
                'hash-%d',
                (int)$shipmentTransfer->getMethod()?->getIdShipmentMethod(),
            ),
        );

        return new PlaceableQuoteShipmentExpenseBuilder($shipmentServiceMock);
    }
}
