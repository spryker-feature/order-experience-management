<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Glue\OrderExperienceManagement\Api\Backend\Mapper;

use Codeception\Test\Unit;
use Generated\Api\Backend\OrdersItem;
use Generated\Api\Backend\OrdersItemPackagingAmountBackendObject;
use Generated\Api\Backend\OrdersItemSalesUnitBackendObject;
use Generated\Api\Backend\OrdersTotals;
use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\ExpenseTransfer;
use Generated\Shared\Transfer\ItemStateTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\ProductMeasurementSalesUnitTransfer;
use Generated\Shared\Transfer\ProductMeasurementUnitTransfer;
use Generated\Shared\Transfer\TotalsTransfer;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Mapper\OrderResourceMapper;
use SprykerFeatureTest\Glue\OrderExperienceManagement\OrderExperienceManagementGlueTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Glue
 * @group OrderExperienceManagement
 * @group Api
 * @group Backend
 * @group Mapper
 * @group OrderResourceMapperTest
 * Add your own group annotations below this line
 */
class OrderResourceMapperTest extends Unit
{
    protected OrderExperienceManagementGlueTester $tester;

    protected OrderResourceMapper $orderResourceMapper;

    protected function _before(): void
    {
        $this->orderResourceMapper = $this->tester->createOrderResourceMapper();
    }

    public function testMapOrderTransferToResourceOmitsCommentsRegardlessOfItemRoute(): void
    {
        // Arrange
        $orderTransfer = new OrderTransfer();

        // Act
        $resource = $this->orderResourceMapper->mapOrderTransferToResource($orderTransfer, [], true);

        // Assert
        $this->assertNull($resource->comments);
    }

    public function testMapOrderTransferToResourceMapsOrderLevelFields(): void
    {
        // Arrange
        $orderTransfer = (new OrderTransfer())
            ->setOrderReference('DE--1234')
            ->setCustomerReference('DE--1')
            ->setStore('DE')
            ->setCreatedAt('2026-08-27 11:04:52')
            ->setCompanyUuid('b7c3f1d8-2e94-4a0b-8f61-3c5d9e7a2b40')
            ->setCompanyBusinessUnitUuid('9c1e0b2a-4f3d-4a91-8c77-1d5b6e2f0a34')
            ->setEmail('ada@example.com');

        // Act
        $resource = $this->orderResourceMapper->mapOrderTransferToResource($orderTransfer, [], true);

        // Assert
        $this->assertSame('DE--1234', $resource->orderReference);
        $this->assertSame('DE--1', $resource->customerReference);
        $this->assertSame('DE', $resource->store);
        $this->assertSame('2026-08-27 11:04:52', $resource->createdAt);
        $this->assertSame('b7c3f1d8-2e94-4a0b-8f61-3c5d9e7a2b40', $resource->companyUuid);
        $this->assertSame('9c1e0b2a-4f3d-4a91-8c77-1d5b6e2f0a34', $resource->companyBusinessUnitUuid);
        $this->assertNotNull($resource->customer);
        $this->assertSame('ada@example.com', $resource->customer->getEmail());
    }

    public function testMapOrderTransferToResourceOmitsItemsOnTheCollectionRoute(): void
    {
        // Arrange
        $orderTransfer = (new OrderTransfer())->addItem(new ItemTransfer());

        // Act
        $resource = $this->orderResourceMapper->mapOrderTransferToResource($orderTransfer, [], false);

        // Assert
        $this->assertNull($resource->items);
        $this->assertSame(1, $resource->itemsCount);
    }

    public function testMapOrderTransferToResourceMapsItemsOnTheItemRoute(): void
    {
        // Arrange
        $itemTransfer = (new ItemTransfer())
            ->setUuid('3fa85f64-5717-4562-b3fc-2c963f66afa6')
            ->setSku('123')
            ->setQuantity(2)
            ->setSumPrice(1000)
            ->setIdSalesOrderItem(42);
        $orderTransfer = (new OrderTransfer())->addItem($itemTransfer);

        // Act
        $resource = $this->orderResourceMapper->mapOrderTransferToResource(
            $orderTransfer,
            [42 => ['ship']],
            true,
        );

        // Assert
        $firstItem = $this->getFirstItem($resource->items);
        $this->assertSame('3fa85f64-5717-4562-b3fc-2c963f66afa6', $firstItem->getUuid());
        $this->assertSame(['ship'], $firstItem->getAvailableEvents());
    }

    public function testMapOrderTransferToResourceMapsTheItemNote(): void
    {
        // Arrange
        $itemTransfer = (new ItemTransfer())
            ->setUuid('3fa85f64-5717-4562-b3fc-2c963f66afa6')
            ->setSku('123')
            ->setQuantity(2)
            ->setCartNote('Deliver to gate 3')
            ->setIdSalesOrderItem(42);
        $orderTransfer = (new OrderTransfer())->addItem($itemTransfer);

        // Act
        $resource = $this->orderResourceMapper->mapOrderTransferToResource($orderTransfer, [], true);

        // Assert
        $this->assertSame('Deliver to gate 3', $this->getFirstItem($resource->items)->getCartNote());
    }

    public function testExtractDistinctItemStatesDedupesRepeatedStateNames(): void
    {
        // Arrange
        $orderTransfer = (new OrderTransfer())
            ->addItem((new ItemTransfer())->setState((new ItemStateTransfer())->setName('open')))
            ->addItem((new ItemTransfer())->setState((new ItemStateTransfer())->setName('open')))
            ->addItem((new ItemTransfer())->setState((new ItemStateTransfer())->setName('shipped')));

        // Act
        $resource = $this->orderResourceMapper->mapOrderTransferToResource($orderTransfer, [], true);

        // Assert
        $this->assertSame(['open', 'shipped'], $resource->itemStates);
    }

    public function testExtractAvailableEventsReturnsTheUnionAcrossItemsWithoutDuplicates(): void
    {
        // Arrange
        $firstItemTransfer = (new ItemTransfer())->setIdSalesOrderItem(1);
        $secondItemTransfer = (new ItemTransfer())->setIdSalesOrderItem(2);
        $orderTransfer = (new OrderTransfer())->addItem($firstItemTransfer)->addItem($secondItemTransfer);

        // Act
        $resource = $this->orderResourceMapper->mapOrderTransferToResource(
            $orderTransfer,
            [1 => ['ship', 'cancel'], 2 => ['cancel']],
            true,
        );

        // Assert
        $this->assertSame(['ship', 'cancel'], $resource->availableEvents);
    }

    /**
     * The order-level `TaxTotal` carries no rate; the breakdown is aggregated from each line and
     * expense and must sum back to it exactly.
     */
    public function testMapOrderTransferToResourceGroupsTaxAmountsByRateAcrossItemsAndExpenses(): void
    {
        // Arrange
        $orderTransfer = (new OrderTransfer())
            ->addItem((new ItemTransfer())->setTaxRate(19.0)->setSumTaxAmount(190))
            ->addItem((new ItemTransfer())->setTaxRate(19.0)->setSumTaxAmount(95))
            ->addItem((new ItemTransfer())->setTaxRate(7.0)->setSumTaxAmount(35))
            ->addExpense((new ExpenseTransfer())->setTaxRate(19.0)->setSumTaxAmount(10))
            ->setTotals((new TotalsTransfer())->setTaxTotal(null));

        // Act
        $resource = $this->orderResourceMapper->mapOrderTransferToResource($orderTransfer, [], true);
        $totals = $resource->totals;
        $this->assertInstanceOf(OrdersTotals::class, $totals);
        $taxBreakdown = $totals->getTaxBreakdown();
        $this->assertIsArray($taxBreakdown);

        // Assert
        $this->assertCount(2, $taxBreakdown);
        $this->assertSame(7.0, $taxBreakdown[0]->getTaxRate());
        $this->assertSame(35, $taxBreakdown[0]->getTaxAmount());
        $this->assertSame(19.0, $taxBreakdown[1]->getTaxRate());
        $this->assertSame(295, $taxBreakdown[1]->getTaxAmount());
    }

    public function testMapOrderTransferToResourceDropsALineWithNoTaxRateFromTheBreakdown(): void
    {
        // Arrange
        $orderTransfer = (new OrderTransfer())
            ->addItem((new ItemTransfer())->setTaxRate(null)->setSumTaxAmount(0))
            ->setTotals(new TotalsTransfer());

        // Act
        $resource = $this->orderResourceMapper->mapOrderTransferToResource($orderTransfer, [], true);
        $totals = $resource->totals;
        $this->assertInstanceOf(OrdersTotals::class, $totals);

        // Assert
        $this->assertSame([], $totals->getTaxBreakdown());
    }

    /**
     * A split line carries quantity 1 and the per-package amount already; the division reproduces
     * the per-package figure the caller sent either way.
     */
    public function testMapOrderTransferToResourceDerivesPackagingAmountByDividingByQuantity(): void
    {
        // Arrange
        $itemTransfer = (new ItemTransfer())
            ->setQuantity(4)
            ->setAmount(20)
            ->setAmountSalesUnit(
                (new ProductMeasurementSalesUnitTransfer())->setProductMeasurementUnit(
                    (new ProductMeasurementUnitTransfer())->setCode('kg')->setName('Kilogram'),
                ),
            )
            ->setAmountLeadProduct((new ProductConcreteTransfer())->setSku('lead-sku'));
        $orderTransfer = (new OrderTransfer())->addItem($itemTransfer);

        // Act
        $resource = $this->orderResourceMapper->mapOrderTransferToResource($orderTransfer, [], true);
        $firstItem = $this->getFirstItem($resource->items);
        $packagingAmount = $firstItem->getPackagingAmount();
        $this->assertInstanceOf(OrdersItemPackagingAmountBackendObject::class, $packagingAmount);

        // Assert
        $this->assertSame(5.0, $packagingAmount->getAmount());
        $this->assertSame('kg', $packagingAmount->getSalesUnitCode());
        $this->assertSame('lead-sku', $packagingAmount->getLeadProductSku());
    }

    public function testMapOrderTransferToResourceOmitsPackagingAmountWhenTheItemHasNoAmount(): void
    {
        // Arrange
        $orderTransfer = (new OrderTransfer())->addItem((new ItemTransfer())->setAmount(null));

        // Act
        $resource = $this->orderResourceMapper->mapOrderTransferToResource($orderTransfer, [], true);
        $firstItem = $this->getFirstItem($resource->items);

        // Assert
        $this->assertNull($firstItem->getPackagingAmount());
    }

    /**
     * A zero or absent conversion would divide by zero; the unit is still reported and the amount is
     * simply omitted.
     */
    public function testMapOrderTransferToResourceOmitsSalesUnitAmountWhenConversionIsZero(): void
    {
        // Arrange
        $itemTransfer = (new ItemTransfer())
            ->setQuantity(10)
            ->setQuantitySalesUnit(
                (new ProductMeasurementSalesUnitTransfer())
                    ->setConversion(0.0)
                    ->setProductMeasurementUnit(
                        (new ProductMeasurementUnitTransfer())->setCode('kg')->setName('Kilogram'),
                    ),
            );
        $orderTransfer = (new OrderTransfer())->addItem($itemTransfer);

        // Act
        $resource = $this->orderResourceMapper->mapOrderTransferToResource($orderTransfer, [], true);
        $firstItem = $this->getFirstItem($resource->items);
        $salesUnit = $firstItem->getSalesUnit();
        $this->assertInstanceOf(OrdersItemSalesUnitBackendObject::class, $salesUnit);

        // Assert
        $this->assertSame('kg', $salesUnit->getCode());
        $this->assertNull($salesUnit->getAmount());
    }

    public function testMapOrderTransferToResourceDerivesSalesUnitAmountFromQuantityAndConversion(): void
    {
        // Arrange
        $itemTransfer = (new ItemTransfer())
            ->setQuantity(10)
            ->setQuantitySalesUnit(
                (new ProductMeasurementSalesUnitTransfer())
                    ->setConversion(2.0)
                    ->setProductMeasurementUnit(
                        (new ProductMeasurementUnitTransfer())->setCode('kg')->setName('Kilogram'),
                    ),
            );
        $orderTransfer = (new OrderTransfer())->addItem($itemTransfer);

        // Act
        $resource = $this->orderResourceMapper->mapOrderTransferToResource($orderTransfer, [], true);
        $firstItem = $this->getFirstItem($resource->items);
        $salesUnit = $firstItem->getSalesUnit();
        $this->assertInstanceOf(OrdersItemSalesUnitBackendObject::class, $salesUnit);

        // Assert
        $this->assertSame(5.0, $salesUnit->getAmount());
    }

    /**
     * Only the payment-method sub-transfer Spryker actually populated on THIS payment (e.g.
     * `giftCard`) is detected — `PaymentTransfer` carries one nullable slot per method it can
     * describe, and the rest stay null.
     */
    public function testMapOrderTransferToResourceDetectsThePopulatedPaymentMethodMetaGenerically(): void
    {
        // Arrange
        $paymentTransfer = (new PaymentTransfer())
            ->setPaymentProvider('DummyPayment')
            ->setPaymentMethod('dummyPaymentInvoice')
            ->setAmount(3489);
        $orderTransfer = (new OrderTransfer())->addPayment($paymentTransfer);

        // Act
        $resource = $this->orderResourceMapper->mapOrderTransferToResource($orderTransfer, [], true);

        // Assert
        $this->assertCount(1, $resource->payments);
        $firstPayment = $resource->payments[0];
        $this->assertSame('DummyPayment', $firstPayment->getPaymentProvider());
        $this->assertSame(3489, $firstPayment->getAmount());
        $this->assertSame([], $firstPayment->getMeta());
    }

    public function testMapOrderTransferToResourceMapsBillingAddressFields(): void
    {
        // Arrange
        $orderTransfer = (new OrderTransfer())->setBillingAddress(
            (new AddressTransfer())
                ->setFirstName('Ada')
                ->setLastName('Lovelace')
                ->setCity('Berlin')
                ->setIso2Code('DE'),
        );

        // Act
        $resource = $this->orderResourceMapper->mapOrderTransferToResource($orderTransfer, [], true);

        // Assert
        $this->assertNotNull($resource->billingAddress);
        $this->assertSame('Ada', $resource->billingAddress->getFirstName());
        $this->assertSame('Berlin', $resource->billingAddress->getCity());
    }

    public function testMapOrderTransferToResourceOmitsBillingAddressWhenTheOrderHasNone(): void
    {
        // Arrange
        $orderTransfer = new OrderTransfer();

        // Act
        $resource = $this->orderResourceMapper->mapOrderTransferToResource($orderTransfer, [], true);

        // Assert
        $this->assertNull($resource->billingAddress);
    }

    /**
     * @param array<int, \Generated\Api\Backend\OrdersItem>|null $items
     */
    protected function getFirstItem(?array $items): OrdersItem
    {
        $this->assertIsArray($items);
        $this->assertCount(1, $items);

        return $items[0];
    }
}
