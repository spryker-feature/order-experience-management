<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Intake\Expander;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\OrderIntakeItemTransfer;
use Generated\Shared\Transfer\OrderIntakeRequestTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\OrderIntakeValidationIssueTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\ShipmentMethodTransfer;
use Spryker\Zed\Shipment\Business\ShipmentFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Expander\OrderIntakeShipmentExpander;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Business
 * @group Intake
 * @group Expander
 * @group OrderIntakeShipmentExpanderTest
 * Add your own group annotations below this line
 */
class OrderIntakeShipmentExpanderTest extends Unit
{
    protected const string ORDER_SHIPMENT_METHOD = 'Standard';

    protected const int ID_ORDER_SHIPMENT_METHOD = 1;

    protected const string ITEM_SHIPMENT_METHOD = 'Express';

    protected const int ID_ITEM_SHIPMENT_METHOD = 2;

    protected const string ORDER_DELIVERY_DATE = '2026-09-04';

    protected const string ITEM_DELIVERY_DATE = '2026-09-01';

    /**
     * The order-level values are the default for every line, which is what puts un-overridden lines
     * on one delivery: they end up carrying the same method, address and date, and that triple is
     * what `ItemsGrouper` hashes.
     */
    public function testExpandShipmentAppliesTheOrderLevelDeliveryToItemsThatDoNotOverrideIt(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote(2);
        $orderIntakeRequestTransfer = $this->createOrderIntakeRequest([new OrderIntakeItemTransfer(), new OrderIntakeItemTransfer()]);

        // Act
        $quoteTransfer = $this->createExpander()->expandShipment(
            $orderIntakeRequestTransfer,
            $quoteTransfer,
            new OrderIntakeResponseTransfer(),
        );

        // Assert
        foreach ($quoteTransfer->getItems() as $itemTransfer) {
            $shipmentTransfer = $itemTransfer->getShipmentOrFail();

            $this->assertSame(static::ID_ORDER_SHIPMENT_METHOD, $shipmentTransfer->getMethodOrFail()->getIdShipmentMethod());
            $this->assertSame(static::ORDER_DELIVERY_DATE, $shipmentTransfer->getRequestedDeliveryDate());
            $this->assertSame($quoteTransfer->getShippingAddress(), $shipmentTransfer->getShippingAddress());
        }
    }

    /**
     * An overriding line gets its own method, date and address object — three different values, so
     * the grouper puts it on a delivery of its own.
     */
    public function testExpandShipmentAppliesAPerItemOverrideToThatItemOnly(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote(2);
        $overrideAddressTransfer = (new AddressTransfer())->setCity('Hamburg');
        $orderIntakeRequestTransfer = $this->createOrderIntakeRequest([
            new OrderIntakeItemTransfer(),
            (new OrderIntakeItemTransfer())
                ->setShipmentMethodName(static::ITEM_SHIPMENT_METHOD)
                ->setRequestedDeliveryDate(static::ITEM_DELIVERY_DATE)
                ->setShippingAddress($overrideAddressTransfer),
        ]);

        // Act
        $quoteTransfer = $this->createExpander()->expandShipment(
            $orderIntakeRequestTransfer,
            $quoteTransfer,
            new OrderIntakeResponseTransfer(),
        );

        // Assert
        $inheritedShipmentTransfer = $quoteTransfer->getItems()->offsetGet(0)->getShipmentOrFail();
        $overriddenShipmentTransfer = $quoteTransfer->getItems()->offsetGet(1)->getShipmentOrFail();

        $this->assertSame(static::ID_ORDER_SHIPMENT_METHOD, $inheritedShipmentTransfer->getMethodOrFail()->getIdShipmentMethod());
        $this->assertSame(static::ID_ITEM_SHIPMENT_METHOD, $overriddenShipmentTransfer->getMethodOrFail()->getIdShipmentMethod());
        $this->assertSame(static::ITEM_DELIVERY_DATE, $overriddenShipmentTransfer->getRequestedDeliveryDate());
        $this->assertSame('Hamburg', $overriddenShipmentTransfer->getShippingAddressOrFail()->getCity());
    }

    /**
     * Guards the missing delivery cost: the method found by name carries no store-currency price, and
     * the cart plugin that would normally create the shipment expense never runs for intake. Handing
     * the quote to the platform is the only thing that puts an expense on the order.
     */
    public function testExpandShipmentHandsTheQuoteToThePlatformToAttachShipmentExpenses(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote(1);
        $expandedQuoteTransfer = (new QuoteTransfer())->setPriceMode('GROSS_MODE');

        // Built here rather than through the shared helper: that one already stubs
        // `expandQuoteWithShipmentGroups`, and PHPUnit honours the first stub for a method.
        $shipmentFacadeMock = $this->createMock(ShipmentFacadeInterface::class);
        $shipmentFacadeMock->method('findShipmentMethodByName')->willReturn(
            (new ShipmentMethodTransfer())
                ->setName(static::ORDER_SHIPMENT_METHOD)
                ->setIdShipmentMethod(static::ID_ORDER_SHIPMENT_METHOD),
        );
        $shipmentFacadeMock->method('isShipmentMethodActive')->willReturn(true);
        $shipmentFacadeMock->expects($this->once())
            ->method('expandQuoteWithShipmentGroups')
            ->with($quoteTransfer)
            ->willReturn($expandedQuoteTransfer);

        // Act
        $resultQuoteTransfer = (new OrderIntakeShipmentExpander($shipmentFacadeMock))->expandShipment(
            $this->createOrderIntakeRequest([new OrderIntakeItemTransfer()]),
            $quoteTransfer,
            new OrderIntakeResponseTransfer(),
        );

        // Assert
        $this->assertSame($expandedQuoteTransfer, $resultQuoteTransfer);
    }

    /**
     * An unresolvable method must not reach the platform: the quote has items with no shipment, and
     * the caller gets the issue instead.
     */
    public function testExpandShipmentDoesNotExpandWhenTheShipmentMethodIsUnknown(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote(1);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $shipmentFacadeMock = $this->createMock(ShipmentFacadeInterface::class);
        $shipmentFacadeMock->method('findShipmentMethodByName')->willReturn(null);
        $shipmentFacadeMock->expects($this->never())->method('expandQuoteWithShipmentGroups');

        // Act
        (new OrderIntakeShipmentExpander($shipmentFacadeMock))->expandShipment(
            $this->createOrderIntakeRequest([new OrderIntakeItemTransfer()]),
            $quoteTransfer,
            $orderIntakeResponseTransfer,
        );

        // Assert
        $this->assertCount(1, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertSame(
            static::ORDER_SHIPMENT_METHOD,
            $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0)->getParameters()['%shipmentMethodName%'],
        );
        // The same path OrderIntakeRequestValidator uses for the same field, not a bare 'shipmentMethod'.
        $this->assertSame(
            'shipment.shipmentMethod',
            $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0)->getField(),
        );
    }

    /**
     * An unknown method named by a LINE has to name that line. Reporting it against the order-level
     * `shipment.shipmentMethod` sends the caller to a field that is perfectly fine.
     */
    public function testExpandShipmentNamesTheOffendingLineWhenAPerItemMethodIsUnknown(): void
    {
        // Arrange — line 0 inherits the (valid) order-level method, line 1 names an unknown one.
        $quoteTransfer = $this->createQuote(2);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act — the shared mock knows the order-level and item-level methods; 'Teleport' is neither.
        $this->createExpander()->expandShipment(
            $this->createOrderIntakeRequest([
                new OrderIntakeItemTransfer(),
                (new OrderIntakeItemTransfer())->setShipmentMethodName('Teleport'),
            ]),
            $quoteTransfer,
            $orderIntakeResponseTransfer,
        );

        // Assert
        $this->assertCount(1, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertSame(
            'items[1].shipment.shipmentMethod',
            $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0)->getField(),
        );
    }

    /**
     * `expandQuoteWithShipmentGroups()` prices the shipment expenses, and the platform's calculation
     * requires every line to carry a unit price to do it (`ItemSubtotalAggregator::requireUnitPrice()`).
     * A response that already carries issues is one where a line may be unpriced — an unknown SKU is
     * never expanded, never priced — so making that call throws `RequiredTransferPropertyException`
     * from deep inside Calculation, BEFORE OrderIntakeWriter reaches the gate that would have turned
     * those issues into a clean 422. The order cannot be placed either way; the expenses are what is
     * skipped, not the reporting.
     */
    public function testExpandShipmentSkipsThePlatformCallWhenTheResponseAlreadyCarriesIssues(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote(1);
        $orderIntakeResponseTransfer = (new OrderIntakeResponseTransfer())
            ->addValidationIssue(
                (new OrderIntakeValidationIssueTransfer())
                    ->setField('items[0].sku')
                    ->setMessage('Product with SKU "nope" was not found.'),
            );

        $shipmentFacadeMock = $this->createMock(ShipmentFacadeInterface::class);
        $shipmentFacadeMock->method('findShipmentMethodByName')->willReturn(
            (new ShipmentMethodTransfer())->setName(static::ORDER_SHIPMENT_METHOD)->setIdShipmentMethod(static::ID_ORDER_SHIPMENT_METHOD),
        );
        $shipmentFacadeMock->method('isShipmentMethodActive')->willReturn(true);
        $shipmentFacadeMock->expects($this->never())->method('expandQuoteWithShipmentGroups');

        // Act
        $quoteTransfer = (new OrderIntakeShipmentExpander($shipmentFacadeMock))->expandShipment(
            $this->createOrderIntakeRequest([new OrderIntakeItemTransfer()]),
            $quoteTransfer,
            $orderIntakeResponseTransfer,
        );

        // Assert — the per-item shipments are still resolved, so a caller inspecting the quote sees
        // the same state; only the expense call is skipped, and no new issue is invented.
        $this->assertNotNull($quoteTransfer->getItems()->offsetGet(0)->getShipment());
        $this->assertCount(1, $orderIntakeResponseTransfer->getValidationIssues());
    }

    protected function createQuote(int $itemCount): QuoteTransfer
    {
        $quoteTransfer = (new QuoteTransfer())->setShippingAddress((new AddressTransfer())->setCity('Berlin'));

        for ($i = 0; $i < $itemCount; $i++) {
            $quoteTransfer->addItem(new ItemTransfer());
        }

        return $quoteTransfer;
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\OrderIntakeItemTransfer> $orderIntakeItemTransfers
     */
    protected function createOrderIntakeRequest(array $orderIntakeItemTransfers): OrderIntakeRequestTransfer
    {
        $orderIntakeRequestTransfer = (new OrderIntakeRequestTransfer())
            ->setShipmentMethodName(static::ORDER_SHIPMENT_METHOD)
            ->setRequestedDeliveryDate(static::ORDER_DELIVERY_DATE);

        foreach ($orderIntakeItemTransfers as $orderIntakeItemTransfer) {
            $orderIntakeRequestTransfer->addItem($orderIntakeItemTransfer);
        }

        return $orderIntakeRequestTransfer;
    }

    protected function createExpander(): OrderIntakeShipmentExpander
    {
        return new OrderIntakeShipmentExpander($this->createShipmentFacadeMock());
    }

    /**
     * `expandQuoteWithShipmentGroups` returns the quote untouched so the per-item assertions above
     * can inspect what this class actually set.
     */
    protected function createShipmentFacadeMock(): ShipmentFacadeInterface
    {
        $shipmentMethodTransfersByName = [
            static::ORDER_SHIPMENT_METHOD => (new ShipmentMethodTransfer())
                ->setName(static::ORDER_SHIPMENT_METHOD)
                ->setIdShipmentMethod(static::ID_ORDER_SHIPMENT_METHOD),
            static::ITEM_SHIPMENT_METHOD => (new ShipmentMethodTransfer())
                ->setName(static::ITEM_SHIPMENT_METHOD)
                ->setIdShipmentMethod(static::ID_ITEM_SHIPMENT_METHOD),
        ];

        $shipmentFacadeMock = $this->createMock(ShipmentFacadeInterface::class);
        $shipmentFacadeMock->method('findShipmentMethodByName')
            ->willReturnCallback(
                static fn (string $name): ?ShipmentMethodTransfer => $shipmentMethodTransfersByName[$name] ?? null,
            );
        $shipmentFacadeMock->method('isShipmentMethodActive')->willReturn(true);
        $shipmentFacadeMock->method('expandQuoteWithShipmentGroups')->willReturnArgument(0);

        return $shipmentFacadeMock;
    }
}
