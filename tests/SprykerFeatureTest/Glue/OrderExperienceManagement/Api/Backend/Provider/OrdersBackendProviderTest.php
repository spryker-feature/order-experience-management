<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Glue\OrderExperienceManagement\Api\Backend\Provider;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Codeception\Stub;
use Codeception\Test\Unit;
use Generated\Api\Backend\OrdersBackendResource;
use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\CalculatedDiscountTransfer;
use Generated\Shared\Transfer\CommentTransfer;
use Generated\Shared\Transfer\ExpenseTransfer;
use Generated\Shared\Transfer\ItemCollectionTransfer;
use Generated\Shared\Transfer\ItemStateTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\LocaleTransfer;
use Generated\Shared\Transfer\OrderCollectionTransfer;
use Generated\Shared\Transfer\OrderCriteriaTransfer;
use Generated\Shared\Transfer\OrderItemFilterTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\ProductMeasurementBaseUnitTransfer;
use Generated\Shared\Transfer\ProductMeasurementSalesUnitTransfer;
use Generated\Shared\Transfer\ProductMeasurementUnitTransfer;
use Generated\Shared\Transfer\ProductOptionTransfer;
use Generated\Shared\Transfer\ShipmentMethodTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;
use Generated\Shared\Transfer\TaxTotalTransfer;
use Generated\Shared\Transfer\TotalsTransfer;
use Spryker\ApiPlatform\Exception\GlueApiException;
use Spryker\ApiPlatform\ResponseTransform\PaginationLinksTransform;
use Spryker\DecimalObject\Decimal;
use Spryker\Zed\Sales\Business\SalesFacadeInterface;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Exception\OrdersBackendExceptionFactory;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Provider\OrdersBackendProvider;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Reader\OrderCommentReaderInterface;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Reader\OrderResourceReader;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Reader\OrderResourceReaderInterface;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Validator\OrdersPayloadShapeValidator;
use SprykerFeature\Glue\OrderExperienceManagement\Dependency\Plugin\OrderResourceExpanderPluginInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementFacadeInterface;
use SprykerFeatureTest\Glue\OrderExperienceManagement\OrderExperienceManagementGlueTester;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Glue
 * @group OrderExperienceManagement
 * @group Api
 * @group Backend
 * @group Provider
 * @group OrdersBackendProviderTest
 * Add your own group annotations below this line
 */
class OrdersBackendProviderTest extends Unit
{
    protected OrderExperienceManagementGlueTester $tester;

    protected const string CUSTOMER_REFERENCE = 'DE--1';

    protected const string LOCALE_NAME_DE_DE = 'de_DE';

    protected const string ORDER_REFERENCE = 'DE--1234';

    protected const string ORDER_REFERENCE_SECOND = 'DE--1235';

    protected const int ID_SALES_ORDER = 1;

    protected const string OMS_PROCESS_NAME = 'Test01';

    protected const string COMPANY_BUSINESS_UNIT_UUID = '9c1e0b2a-4f3d-4a91-8c77-1d5b6e2f0a34';

    protected const string COMPANY_UUID = 'b7c3f1d8-2e94-4a0b-8f61-3c5d9e7a2b40';

    protected const string COMMENT_MESSAGE = 'Customer asked to hold the shipment until Friday.';

    protected const string COMMENT_USERNAME = 'Ada Operator';

    protected const string COMMENT_CREATED_AT = '2026-09-09 11:04:12.000000';

    protected const string QUERY_PARAM_FILTER = 'filter';

    protected const string FILTER_KEY_PREFIX = 'orders.';

    protected const string FILTER_ORDER_REFERENCE = 'orderReference';

    protected const string FILTER_CUSTOMER_REFERENCE = 'customerReference';

    protected const string FILTER_STORE_NAME = 'storeName';

    protected const string FILTER_ITEM_STATE = 'itemState';

    protected const string FILTER_CREATED_AT_FROM = 'createdAtFrom';

    protected const string FILTER_CREATED_AT_TO = 'createdAtTo';

    protected const string UNSUPPORTED_FILTER_FIELD = 'itemStte';

    protected const string UNPREFIXED_FILTER_KEY = 'itemState';

    protected const string CREATED_AT_FROM = '2026-01-01 00:00:00';

    protected const string CREATED_AT_TO = '2026-12-31 23:59:59';

    protected const string UNPARSABLE_DATE = 'sasdasd';

    public function testGivenOrderWithCustomerAndBillingAddressWhenProvideCollectionThenMapsBothOntoResource(): void
    {
        // Arrange
        $orderTransfer = (new OrderTransfer())
            ->setOrderReference('DE--1234')
            ->setCustomerReference(static::CUSTOMER_REFERENCE)
            ->setEmail('ada@example.com')
            ->setSalutation('Ms')
            ->setFirstName('Ada')
            ->setLastName('Lovelace')
            ->setBillingAddress(
                (new AddressTransfer())
                    ->setSalutation('Ms')
                    ->setFirstName('Ada')
                    ->setLastName('Lovelace')
                    ->setCompany('Analytical Engines Ltd')
                    ->setAddress1('Julie-Wolfthorn-Strasse')
                    ->setAddress2('1')
                    ->setZipCode('10115')
                    ->setCity('Berlin')
                    ->setIso2Code('DE')
                    ->setPhone('+49123456789'),
            );

        $orderCollectionTransfer = (new OrderCollectionTransfer())->addOrder($orderTransfer);

        $salesFacade = Stub::makeEmpty(SalesFacadeInterface::class, [
            'getOrderCollection' => fn (OrderCriteriaTransfer $orderCriteriaTransfer): OrderCollectionTransfer => $orderCollectionTransfer,
        ]);

        $provider = new OrdersBackendProvider(
            $salesFacade,
            $this->createOrderResourceReader(
                $salesFacade,
                Stub::makeEmpty(OrderExperienceManagementFacadeInterface::class, ['getAvailableOrderItemTransitions' => []]),
                $this->createOrderCommentReaderStub(),
            ),
            new OrdersPayloadShapeValidator(),
            new OrdersBackendExceptionFactory(),
        );

        // Act
        $resources = $this->provideOrders($provider, new GetCollection(class: OrdersBackendResource::class), ['request' => new Request()]);

        // Assert
        $this->assertCount(1, $resources);
        $resource = $resources[0];

        $this->assertNotNull($resource->customer);
        $this->assertSame('ada@example.com', $resource->customer->getEmail());
        $this->assertSame('Ms', $resource->customer->getSalutation());
        $this->assertSame('Ada', $resource->customer->getFirstName());
        $this->assertSame('Lovelace', $resource->customer->getLastName());
        $this->assertSame(static::CUSTOMER_REFERENCE, $resource->customerReference);

        $this->assertNotNull($resource->billingAddress);
        $this->assertSame('Ms', $resource->billingAddress->getSalutation());
        $this->assertSame('Ada', $resource->billingAddress->getFirstName());
        $this->assertSame('Lovelace', $resource->billingAddress->getLastName());
        $this->assertSame('Analytical Engines Ltd', $resource->billingAddress->getCompany());
        $this->assertSame('Julie-Wolfthorn-Strasse', $resource->billingAddress->getAddress1());
        $this->assertSame('1', $resource->billingAddress->getAddress2());
        $this->assertNull($resource->billingAddress->getAddress3());
        $this->assertSame('10115', $resource->billingAddress->getZipCode());
        $this->assertSame('Berlin', $resource->billingAddress->getCity());
        $this->assertSame('DE', $resource->billingAddress->getIso2Code());
        $this->assertSame('+49123456789', $resource->billingAddress->getPhone());
    }

    public function testGivenOrderWithoutBillingAddressWhenProvideCollectionThenBillingAddressIsNull(): void
    {
        // Arrange
        $orderTransfer = (new OrderTransfer())
            ->setOrderReference('DE--1235')
            ->setCustomerReference(static::CUSTOMER_REFERENCE);

        $orderCollectionTransfer = (new OrderCollectionTransfer())->addOrder($orderTransfer);

        $salesFacade = Stub::makeEmpty(SalesFacadeInterface::class, [
            'getOrderCollection' => fn (OrderCriteriaTransfer $orderCriteriaTransfer): OrderCollectionTransfer => $orderCollectionTransfer,
        ]);

        $provider = new OrdersBackendProvider(
            $salesFacade,
            $this->createOrderResourceReader(
                $salesFacade,
                Stub::makeEmpty(OrderExperienceManagementFacadeInterface::class, ['getAvailableOrderItemTransitions' => []]),
                $this->createOrderCommentReaderStub(),
            ),
            new OrdersPayloadShapeValidator(),
            new OrdersBackendExceptionFactory(),
        );

        // Act
        $resources = $this->provideOrders($provider, new GetCollection(class: OrdersBackendResource::class), ['request' => new Request()]);

        // Assert
        $this->assertNull($resources[0]->billingAddress);
    }

    public function testGivenOrderWithItemsWhenProvideCollectionThenItemsAreOmittedButItemsCountIsReported(): void
    {
        // Arrange
        $orderCollectionTransfer = (new OrderCollectionTransfer())->addOrder($this->createOrderTransferWithTwoItems());

        $provider = $this->createProvider($orderCollectionTransfer);

        // Act
        $resources = $this->provideOrders($provider, new GetCollection(class: OrdersBackendResource::class), ['request' => new Request()]);

        // Assert
        $this->assertNull(
            $resources[0]->items,
            'items must stay null on the collection so skip_null_values drops the key entirely.',
        );
        $this->assertSame(2, $resources[0]->itemsCount);
    }

    public function testGivenOrderWithItemsWhenProvideCollectionThenItemDerivedFieldsAreStillReported(): void
    {
        // Arrange
        $orderCollectionTransfer = (new OrderCollectionTransfer())->addOrder($this->createOrderTransferWithTwoItems());

        $provider = $this->createProvider($orderCollectionTransfer);

        // Act
        $resources = $this->provideOrders($provider, new GetCollection(class: OrdersBackendResource::class), ['request' => new Request()]);

        // Assert
        $this->assertSame(['paid', 'shipped'], $resources[0]->itemStates);
    }

    public function testGivenOrderWithItemsWhenProvideItemThenItemsAreReturnedAlongsideItemsCount(): void
    {
        // Arrange
        $orderCollectionTransfer = (new OrderCollectionTransfer())->addOrder($this->createOrderTransferWithTwoItems());

        $provider = $this->createProvider($orderCollectionTransfer);

        // Act
        $resource = $this->provideOrder($provider, new Get(class: OrdersBackendResource::class), ['orderReference' => static::ORDER_REFERENCE], ['request' => new Request()]);

        // Assert — the lines are the shared canonical OrdersItem objects the resource property is
        // typed to, not plain maps, so they serialize against the published component schema.
        $items = $this->getItems($resource);

        $this->assertCount(2, $items);
        $this->assertSame(2, $resource->itemsCount);
        $this->assertSame('001_25904006', $items[0]->getSku());
    }

    /**
     * The order-level `availableEvents` is a union, so it cannot say WHICH lines an event is
     * legal for. Two items in different states get their own sets here, resolved off the same
     * one-call manual-events map.
     */
    public function testGivenItemsInDifferentStatesWhenProvideItemThenEachLineReportsItsOwnAvailableEvents(): void
    {
        // Arrange
        $orderCollectionTransfer = (new OrderCollectionTransfer())->addOrder($this->createOrderTransferWithTwoItems());

        $provider = $this->createProvider($orderCollectionTransfer, null, [
            1 => ['ship', 'cancel'],
            2 => ['refund'],
        ]);

        // Act
        $resource = $this->provideOrder($provider, new Get(class: OrdersBackendResource::class), ['orderReference' => static::ORDER_REFERENCE], ['request' => new Request()]);

        // Assert — each line carries the events legal for ITS state, keyed by idSalesOrderItem.
        $items = $this->getItems($resource);

        $this->assertSame(['ship', 'cancel'], $items[0]->getAvailableEvents());
        $this->assertSame(['refund'], $items[1]->getAvailableEvents());

        // And the order-level field stays the union over the lines.
        $this->assertSame(['ship', 'cancel', 'refund'], $resource->availableEvents);
    }

    /**
     * A line in a terminal state has no legal event. It must report an empty set rather than
     * inheriting the order-level union, which would tell a caller an event is firable on a line
     * where it is not.
     */
    public function testGivenAnItemWithNoLegalEventsWhenProvideItemThenItsAvailableEventsAreEmpty(): void
    {
        // Arrange
        $orderCollectionTransfer = (new OrderCollectionTransfer())->addOrder($this->createOrderTransferWithTwoItems());

        $provider = $this->createProvider($orderCollectionTransfer, null, [1 => ['ship']]);

        // Act
        $resource = $this->provideOrder($provider, new Get(class: OrdersBackendResource::class), ['orderReference' => static::ORDER_REFERENCE], ['request' => new Request()]);

        // Assert — item 2 is absent from the map entirely, which is how OMS reports "nothing legal".
        $items = $this->getItems($resource);

        $this->assertSame(['ship'], $items[0]->getAvailableEvents());
        $this->assertSame([], $items[1]->getAvailableEvents());
    }

    /**
     * Both are columns on `spy_sales_order`, so `SalesOrderMapper`'s whole-row copy puts them on the
     * transfer for free. `companyBusinessUnitUuid` is the same field a POST accepts, and echoing it
     * is what makes a write-only selector unnecessary — the caller can confirm which organisation
     * the order was priced for.
     */
    public function testGivenOrderPlacedForABusinessUnitWhenProvideItemThenCompanyContextIsReturned(): void
    {
        // Arrange
        $orderTransfer = (new OrderTransfer())
            ->setOrderReference(static::ORDER_REFERENCE)
            ->setCustomerReference(static::CUSTOMER_REFERENCE)
            ->setCompanyBusinessUnitUuid(static::COMPANY_BUSINESS_UNIT_UUID)
            ->setCompanyUuid(static::COMPANY_UUID);

        $provider = $this->createProvider((new OrderCollectionTransfer())->addOrder($orderTransfer));

        // Act
        $resource = $this->provideOrder($provider, new Get(class: OrdersBackendResource::class), ['orderReference' => static::ORDER_REFERENCE], ['request' => new Request()]);

        // Assert
        $this->assertSame(static::COMPANY_BUSINESS_UNIT_UUID, $resource->companyBusinessUnitUuid);
        $this->assertSame(static::COMPANY_UUID, $resource->companyUuid);
    }

    /**
     * An order placed by a customer with no company user has both columns null. They must stay null
     * rather than becoming empty strings, so `skip_null_values` drops them from the response instead
     * of advertising a company context that does not exist.
     */
    public function testGivenOrderPlacedWithoutACompanyUserWhenProvideItemThenCompanyContextIsNull(): void
    {
        // Arrange
        $orderTransfer = (new OrderTransfer())
            ->setOrderReference(static::ORDER_REFERENCE)
            ->setCustomerReference(static::CUSTOMER_REFERENCE);

        $provider = $this->createProvider((new OrderCollectionTransfer())->addOrder($orderTransfer));

        // Act
        $resource = $this->provideOrder($provider, new Get(class: OrdersBackendResource::class), ['orderReference' => static::ORDER_REFERENCE], ['request' => new Request()]);

        // Assert
        $this->assertNull($resource->companyBusinessUnitUuid);
        $this->assertNull($resource->companyUuid);
    }

    public function testGivenItemWithShipmentWhenProvideItemThenPerLineShipmentIsMappedAsTypedObject(): void
    {
        // Arrange — a line carrying its own delivery override.
        $orderTransfer = (new OrderTransfer())
            ->setOrderReference(static::ORDER_REFERENCE)
            ->setCustomerReference(static::CUSTOMER_REFERENCE)
            ->addItem(
                (new ItemTransfer())
                    ->setIdSalesOrderItem(1)
                    ->setSku('001_25904006')
                    ->setQuantity(1)
                    ->setState((new ItemStateTransfer())->setName('paid'))
                    ->setShipment(
                        (new ShipmentTransfer())
                            ->setMethod((new ShipmentMethodTransfer())->setName('Express'))
                            ->setRequestedDeliveryDate('2026-09-04')
                            ->setShippingAddress((new AddressTransfer())->setCity('Berlin')->setZipCode('10115')),
                    ),
            );

        $provider = $this->createProvider((new OrderCollectionTransfer())->addOrder($orderTransfer));

        // Act
        $resource = $this->provideOrder($provider, new Get(class: OrdersBackendResource::class), ['orderReference' => static::ORDER_REFERENCE], ['request' => new Request()]);

        // Assert
        $shipment = $this->getItems($resource)[0]->getShipment();
        $this->assertNotNull($shipment);
        $this->assertSame('Express', $shipment->getShipmentMethod());
        $this->assertSame('2026-09-04', $shipment->getRequestedDeliveryDate());
        $this->assertSame('Berlin', $shipment->getShippingAddress()?->getCity());
    }

    public function testGivenItemWithoutShipmentWhenProvideItemThenPerLineShipmentIsNull(): void
    {
        // Arrange
        $provider = $this->createProvider(
            (new OrderCollectionTransfer())->addOrder($this->createOrderTransferWithTwoItems()),
        );

        // Act
        $resource = $this->provideOrder($provider, new Get(class: OrdersBackendResource::class), ['orderReference' => static::ORDER_REFERENCE], ['request' => new Request()]);

        // Assert — an absent shipment stays absent rather than becoming an empty object.
        $this->assertNull($this->getItems($resource)[0]->getShipment());
    }

    /**
     * `itemsCount` is counted off the loaded items, so it must be reported on the collection route
     * even though the items themselves are never mapped onto the resource there.
     */
    public function testGivenOrderWithoutItemsWhenProvideCollectionThenItemsCountIsZero(): void
    {
        // Arrange
        $orderTransfer = (new OrderTransfer())
            ->setOrderReference(static::ORDER_REFERENCE)
            ->setCustomerReference(static::CUSTOMER_REFERENCE);

        $orderCollectionTransfer = (new OrderCollectionTransfer())->addOrder($orderTransfer);

        $provider = $this->createProvider($orderCollectionTransfer);

        // Act
        $resources = $this->provideOrders($provider, new GetCollection(class: OrdersBackendResource::class), ['request' => new Request()]);

        // Assert
        $this->assertSame(0, $resources[0]->itemsCount);
        $this->assertNull($resources[0]->items);
    }

    /**
     * Mapped field by field rather than dumped, and named after the storefront `RestOrderTotals`
     * attributes. `refundTotal` and `priceToPay` used to ship undeclared through `toArray()`;
     * `refundTotal` in particular sums each line's REFUNDABLE amount, so on an untouched order it
     * equals the grand total and reads as "fully refunded".
     */
    public function testGivenOrderWhenProvideItemThenMapsTheStorefrontTotalsFieldSetOnly(): void
    {
        // Arrange
        $orderTransfer = $this->createOrderTransferWithTwoItems()->setTotals(
            (new TotalsTransfer())
                ->setSubtotal(43322)
                ->setExpenseTotal(980)
                ->setDiscountTotal(13110)
                ->setTaxTotal((new TaxTotalTransfer())->setAmount(4980))
                ->setGrandTotal(31192)
                ->setCanceledTotal(0)
                ->setRemunerationTotal(0)
                ->setRefundTotal(31192),
        );

        $provider = $this->createProvider((new OrderCollectionTransfer())->addOrder($orderTransfer));

        // Act
        $resource = $this->provideOrder($provider, new Get(class: OrdersBackendResource::class), ['orderReference' => static::ORDER_REFERENCE], ['request' => new Request()]);

        // Assert — the tax aggregate is flattened to its amount; an order can mix rates, so the
        // order-level TaxTotal never carries one.
        $totals = $resource->totals;
        $this->assertNotNull($totals);

        $this->assertSame(43322, $totals->getSubtotal());
        $this->assertSame(4980, $totals->getTaxTotal());
        $this->assertSame(0, $totals->getCanceledTotal());
        $this->assertSame(31192, $totals->getGrandTotal());
        $this->assertArrayNotHasKey('priceToPay', $totals->toArray());

        // The platform's `refundTotal` under a name that says what it holds. It is the matched pair of
        // `canceledTotal` — `sumPriceToPayAggregation - canceledAmount` summed — so on an untouched
        // order it equals the grand total, and falls as parts of the order are canceled.
        $this->assertSame(31192, $totals->getRefundableTotal());
        $this->assertArrayNotHasKey('refundTotal', $totals->toArray());
    }

    /**
     * An order can mix rates — a 19% line beside a 0% one — which is why the order-level `TaxTotal`
     * carries no rate. The split is aggregated from where the rate actually lives, and sums back to
     * `taxTotal` exactly.
     */
    public function testGivenMixedTaxRatesWhenProvideItemThenSplitsTheTaxTotalPerRate(): void
    {
        // Arrange
        $orderTransfer = (new OrderTransfer())
            ->setOrderReference(static::ORDER_REFERENCE)
            ->setCustomerReference(static::CUSTOMER_REFERENCE)
            ->setTotals((new TotalsTransfer())->setTaxTotal((new TaxTotalTransfer())->setAmount(752)))
            ->addItem((new ItemTransfer())->setSku('a')->setQuantity(1)->setTaxRate(19.00)->setSumTaxAmount(592))
            ->addItem((new ItemTransfer())->setSku('b')->setQuantity(1)->setTaxRate(7.00)->setSumTaxAmount(0))
            ->addItem((new ItemTransfer())->setSku('c')->setQuantity(1)->setTaxRate(19.00)->setSumTaxAmount(0))
            // A line with no rate at all belongs in no bucket; inventing a 0% one would report a rate
            // the order does not carry.
            ->addItem((new ItemTransfer())->setSku('d')->setQuantity(1)->setSumTaxAmount(0))
            ->addExpense((new ExpenseTransfer())->setName('Standard')->setTaxRate(7.00)->setSumTaxAmount(160));

        $provider = $this->createProvider((new OrderCollectionTransfer())->addOrder($orderTransfer));

        // Act
        $resource = $this->provideOrder($provider, new Get(class: OrdersBackendResource::class), ['orderReference' => static::ORDER_REFERENCE], ['request' => new Request()]);

        // Assert — ascending by rate, so a list row does not reorder between requests.
        $taxBreakdown = $resource->totals?->getTaxBreakdown() ?? [];

        $this->assertCount(2, $taxBreakdown);
        $this->assertSame(7.0, $taxBreakdown[0]->getTaxRate());
        $this->assertSame(160, $taxBreakdown[0]->getTaxAmount());
        $this->assertSame(19.0, $taxBreakdown[1]->getTaxRate());
        $this->assertSame(592, $taxBreakdown[1]->getTaxAmount());
        $this->assertSame(
            $resource->totals?->getTaxTotal(),
            array_sum(array_map(static fn ($entry): int => (int)$entry->getTaxAmount(), $taxBreakdown)),
        );
    }

    /**
     * The expense lines the Back Office prints between the subtotal and the discount row, and the
     * "Discounts & Vouchers" box beside them. Both on the COLLECTION route too — a list page shows
     * them per row — which works because both routes ask for `withOrderExpanderPlugins`.
     */
    public function testGivenOrderWhenProvideCollectionThenMapsExpensesAndCalculatedDiscounts(): void
    {
        // Arrange
        $orderTransfer = $this->createOrderTransferWithTwoItems()
            ->addExpense(
                (new ExpenseTransfer())
                    ->setType('SHIPMENT_EXPENSE_TYPE')
                    ->setName('Standard')
                    ->setSumPrice(490)
                    ->setTaxRate(19.00)
                    ->setSumTaxAmount(0)
                    ->setSumDiscountAmountAggregation(490)
                    ->setSumPriceToPayAggregation(0)
                    ->setCanceledAmount(0),
            )
            ->addCalculatedDiscount(
                (new CalculatedDiscountTransfer())
                    ->setDisplayName('Free standard delivery')
                    ->setVoucherCode('summer-2026')
                    ->setQuantity(2)
                    ->setSumAmount(980),
            );

        $provider = $this->createProvider((new OrderCollectionTransfer())->addOrder($orderTransfer));

        // Act
        $resources = $this->provideOrders($provider, new GetCollection(class: OrdersBackendResource::class), ['request' => new Request()]);

        // Assert
        $this->assertCount(1, $resources[0]->expenses);
        $this->assertSame('Standard', $resources[0]->expenses[0]->getName());
        // A DECIMAL column arrives from Propel as a string; the generated property is a ?float, and
        // assigning the string is a TypeError this stack reports as an opaque 404.
        $this->assertSame(19.0, $resources[0]->expenses[0]->getTaxRate());
        $this->assertSame(490, $resources[0]->expenses[0]->getSumDiscountAmountAggregation());

        $this->assertCount(1, $resources[0]->calculatedDiscounts);
        $this->assertSame('Free standard delivery', $resources[0]->calculatedDiscounts[0]->getDisplayName());
        $this->assertSame('summer-2026', $resources[0]->calculatedDiscounts[0]->getVoucherCode());
        $this->assertSame(980, $resources[0]->calculatedDiscounts[0]->getSumAmount());
    }

    /**
     * `payments` is read straight off `OrderTransfer.payments` — already populated for free by
     * `SalesPaymentOrderExpanderPlugin` via `withOrderExpanderPlugins`, no extra facade call. This is
     * how a caller finds out how much is left for the main payment method once a gift card applied:
     * the non-GiftCard row's `amount` is already net of it, computed once at placement.
     */
    public function testGivenOrderWithMultiplePaymentsWhenProvideItemThenPaymentsAreMappedWithProviderMethodAndAmount(): void
    {
        // Arrange
        $orderTransfer = (new OrderTransfer())
            ->setOrderReference(static::ORDER_REFERENCE)
            ->setCustomerReference(static::CUSTOMER_REFERENCE)
            ->addPayment(
                (new PaymentTransfer())
                    ->setPaymentProvider('GiftCard')
                    ->setPaymentMethod('GiftCard')
                    ->setAmount(5000),
            )
            ->addPayment(
                (new PaymentTransfer())
                    ->setPaymentProvider('DummyPayment')
                    ->setPaymentMethod('dummyPaymentInvoice')
                    ->setAmount(3489),
            );

        $provider = $this->createProvider((new OrderCollectionTransfer())->addOrder($orderTransfer));

        // Act
        $resource = $this->provideOrder($provider, new Get(class: OrdersBackendResource::class), ['orderReference' => static::ORDER_REFERENCE], ['request' => new Request()]);

        // Assert
        $this->assertCount(2, $resource->payments);
        $this->assertSame('GiftCard', $resource->payments[0]->getPaymentProvider());
        $this->assertSame(5000, $resource->payments[0]->getAmount());
        $this->assertSame('DummyPayment', $resource->payments[1]->getPaymentProvider());
        $this->assertSame('dummyPaymentInvoice', $resource->payments[1]->getPaymentMethod());
        $this->assertSame(3489, $resource->payments[1]->getAmount());
    }

    /**
     * `fkLocale` is the module's own additive property on core's `Order` transfer, populated
     * generically the same way every other plain column on it is — see the transfer XML comment.
     * The resource reports the locale by NAME, not the raw id, so this is one extra lookup.
     */
    public function testGivenOrderWithALocaleWhenProvideItemThenLocaleIsMappedByName(): void
    {
        // Arrange
        $orderTransfer = (new OrderTransfer())
            ->setOrderReference(static::ORDER_REFERENCE)
            ->setCustomerReference(static::CUSTOMER_REFERENCE)
            ->setLocale((new LocaleTransfer())->setLocaleName(static::LOCALE_NAME_DE_DE));

        $salesFacade = Stub::makeEmpty(SalesFacadeInterface::class, [
            'getOrderCollection' => fn (): OrderCollectionTransfer => (new OrderCollectionTransfer())->addOrder($orderTransfer),
            'getOrderItems' => fn (): ItemCollectionTransfer => new ItemCollectionTransfer(),
        ]);

        $provider = new OrdersBackendProvider(
            $salesFacade,
            $this->createOrderResourceReader(
                $salesFacade,
                Stub::makeEmpty(OrderExperienceManagementFacadeInterface::class, ['getAvailableOrderItemTransitions' => []]),
                $this->createOrderCommentReaderStub(),
            ),
            new OrdersPayloadShapeValidator(),
            new OrdersBackendExceptionFactory(),
        );

        // Act
        $resource = $this->provideOrder($provider, new Get(class: OrdersBackendResource::class), ['orderReference' => static::ORDER_REFERENCE], ['request' => new Request()]);

        // Assert
        $this->assertSame('de_DE', $resource->locale);
    }

    /**
     * The whole point of the expander stack: a module contributing a property to the `orders` schema
     * gets it reported without OrderExperienceManagement naming that property anywhere. Before this
     * hook existed, PurchasingControl's `budget` was mapped inline in `OrderResourceReader`.
     */
    public function testGivenARegisteredExpanderPluginWhenProvideItemThenItsContributionIsReported(): void
    {
        // Arrange
        $orderTransfer = (new OrderTransfer())
            ->setOrderReference(static::ORDER_REFERENCE)
            ->setCustomerReference(static::CUSTOMER_REFERENCE);

        $provider = $this->createProvider(
            (new OrderCollectionTransfer())->addOrder($orderTransfer),
            null,
            [],
            [$this->createOrderResourceExpanderPlugin()],
        );

        // Act
        $resource = $this->provideOrder($provider, new Get(class: OrdersBackendResource::class), ['orderReference' => static::ORDER_REFERENCE], ['request' => new Request()]);

        // Assert
        $this->assertSame(sprintf('expanded:%s', static::ORDER_REFERENCE), $resource->orderCustomReference);
    }

    /**
     * The collection route builds its resources through the same mapping, so a contribution cannot
     * be present on one read route and missing on the other.
     */
    public function testGivenARegisteredExpanderPluginWhenProvideCollectionThenEveryOrderCarriesItsContribution(): void
    {
        // Arrange
        $orderCollectionTransfer = (new OrderCollectionTransfer())
            ->addOrder((new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE))
            ->addOrder((new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE_SECOND));

        $provider = $this->createProvider($orderCollectionTransfer, null, [], [$this->createOrderResourceExpanderPlugin()]);

        // Act
        $resources = $this->provideOrders($provider, new GetCollection(class: OrdersBackendResource::class), ['request' => new Request()]);

        // Assert
        $this->assertCount(2, $resources);
        $this->assertSame(sprintf('expanded:%s', static::ORDER_REFERENCE), $resources[0]->orderCustomReference);
        $this->assertSame(sprintf('expanded:%s', static::ORDER_REFERENCE_SECOND), $resources[1]->orderCustomReference);
    }

    /**
     * Plugins are a chain, not a choice: each one receives what the previous returned, so two
     * modules contributing to the same order both end up on it.
     */
    public function testGivenSeveralRegisteredExpanderPluginsWhenProvideItemThenEachOneSeesThePreviousResult(): void
    {
        // Arrange
        $orderTransfer = (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE);

        $secondPlugin = new class implements OrderResourceExpanderPluginInterface {
            public function expand(
                OrdersBackendResource $ordersBackendResource,
                OrderTransfer $orderTransfer,
            ): OrdersBackendResource {
                $ordersBackendResource->orderCustomReference .= ':second';

                return $ordersBackendResource;
            }
        };

        $provider = $this->createProvider(
            (new OrderCollectionTransfer())->addOrder($orderTransfer),
            null,
            [],
            [$this->createOrderResourceExpanderPlugin(), $secondPlugin],
        );

        // Act
        $resource = $this->provideOrder($provider, new Get(class: OrdersBackendResource::class), ['orderReference' => static::ORDER_REFERENCE], ['request' => new Request()]);

        // Assert
        $this->assertSame(sprintf('expanded:%s:second', static::ORDER_REFERENCE), $resource->orderCustomReference);
    }

    public function testGivenOrderWithNoLocaleWhenProvideItemThenLocaleIsNull(): void
    {
        // Arrange
        $orderTransfer = (new OrderTransfer())
            ->setOrderReference(static::ORDER_REFERENCE)
            ->setCustomerReference(static::CUSTOMER_REFERENCE);

        $provider = $this->createProvider((new OrderCollectionTransfer())->addOrder($orderTransfer));

        // Act
        $resource = $this->provideOrder($provider, new Get(class: OrdersBackendResource::class), ['orderReference' => static::ORDER_REFERENCE], ['request' => new Request()]);

        // Assert
        $this->assertNull($resource->locale);
    }

    /**
     * The per-rate tax detail and the cancel/refund figures live on the line, because an order can
     * mix rates and cancel part of itself. `items[].calculatedDiscounts` is the per-line half of the
     * order-level list.
     */
    public function testGivenItemWhenProvideItemThenMapsTaxCancellationAndPerLineDiscounts(): void
    {
        // Arrange
        $orderTransfer = (new OrderTransfer())
            ->setOrderReference(static::ORDER_REFERENCE)
            ->setCustomerReference(static::CUSTOMER_REFERENCE)
            ->addItem(
                (new ItemTransfer())
                    ->setIdSalesOrderItem(1)
                    ->setSku('001_25904006')
                    ->setQuantity(1)
                    ->setState((new ItemStateTransfer())->setName('paid'))
                    ->setTaxRate(19.00)
                    ->setSumTaxAmount(322)
                    ->setRefundableAmount(2016)
                    ->setCanceledAmount(0)
                    ->addCalculatedDiscount(
                        (new CalculatedDiscountTransfer())
                            ->setDisplayName('20% off cameras')
                            ->setQuantity(1)
                            ->setUnitAmount(560)
                            ->setSumAmount(560),
                    ),
            );

        $provider = $this->createProvider((new OrderCollectionTransfer())->addOrder($orderTransfer));

        // Act
        $resource = $this->provideOrder($provider, new Get(class: OrdersBackendResource::class), ['orderReference' => static::ORDER_REFERENCE], ['request' => new Request()]);

        // Assert
        $item = $this->getItems($resource)[0];
        $this->assertSame(19.0, $item->getTaxRate());
        $this->assertSame(322, $item->getSumTaxAmount());
        $this->assertSame(2016, $item->getRefundableAmount());
        $this->assertSame(0, $item->getCanceledAmount());
        $this->assertCount(1, $item->getCalculatedDiscounts());
        $this->assertSame(560, $item->getCalculatedDiscounts()[0]->getSumAmount());
    }

    protected function createOrderTransferWithTwoItems(): OrderTransfer
    {
        return (new OrderTransfer())
            ->setOrderReference(static::ORDER_REFERENCE)
            // The manual-events read is keyed off the order id, so a fixture without one never
            // reaches OmsFacade at all.
            ->setIdSalesOrder(static::ID_SALES_ORDER)
            ->setCustomerReference(static::CUSTOMER_REFERENCE)
            ->addItem(
                (new ItemTransfer())
                    ->setIdSalesOrderItem(1)
                    ->setSku('001_25904006')
                    ->setQuantity(2)
                    ->setProcess(static::OMS_PROCESS_NAME)
                    ->setState((new ItemStateTransfer())->setName('paid')),
            )
            ->addItem(
                (new ItemTransfer())
                    ->setIdSalesOrderItem(2)
                    ->setSku('002_25904006')
                    ->setQuantity(1)
                    ->setProcess(static::OMS_PROCESS_NAME)
                    ->setState((new ItemStateTransfer())->setName('shipped')),
            );
    }

    /**
     * The whole reason the item route makes a second read: `ProductOptionsOrderItemExpanderPlugin`
     * runs in the order-ITEM expander stack, which `getOrderCollection()` never executes.
     */
    public function testGivenExpandedItemsWhenProvideItemThenProductOptionsAreReturned(): void
    {
        // Arrange
        $orderCollectionTransfer = (new OrderCollectionTransfer())->addOrder(
            (new OrderTransfer())
                ->setOrderReference(static::ORDER_REFERENCE)
                ->addItem((new ItemTransfer())->setIdSalesOrderItem(1)->setSku('sku-1')->setQuantity(2)),
        );

        $itemCollectionTransfer = (new ItemCollectionTransfer())->addItem(
            (new ItemTransfer())
                ->setIdSalesOrderItem(1)
                ->setSku('sku-1')
                ->setQuantity(2)
                ->addProductOption(
                    (new ProductOptionTransfer())
                        ->setSku('OP_gift_wrapping')
                        ->setGroupName('Gift wrapping')
                        ->setValue('Premium paper')
                        ->setUnitPrice(500)
                        ->setSumPrice(1000)
                        ->setTaxRate(19.00),
                ),
        );

        $provider = $this->createProvider($orderCollectionTransfer, $itemCollectionTransfer);

        // Act
        $resource = $this->provideOrder($provider, new Get(class: OrdersBackendResource::class), ['orderReference' => static::ORDER_REFERENCE], ['request' => new Request()]);

        // Assert
        $productOption = $this->getItems($resource)[0]->getProductOptions()[0];

        $this->assertSame('OP_gift_wrapping', $productOption->getSku());
        $this->assertSame('Gift wrapping', $productOption->getGroupName());
        $this->assertSame('Premium paper', $productOption->getValue());
        $this->assertSame(500, $productOption->getUnitPrice());
        $this->assertSame(1000, $productOption->getSumPrice());
        $this->assertSame(19.0, $productOption->getTaxRate());
    }

    /**
     * `amount` is the figure the caller submitted, reconstructed from the persisted snapshot:
     * `quantity / conversion`. A line POSTed as 2 METR at conversion 5 reads back as 2.
     */
    public function testGivenExpandedItemsWhenProvideItemThenTheSalesUnitIsReturnedWithTheSubmittedAmount(): void
    {
        // Arrange
        $orderCollectionTransfer = (new OrderCollectionTransfer())->addOrder(
            (new OrderTransfer())
                ->setOrderReference(static::ORDER_REFERENCE)
                ->addItem((new ItemTransfer())->setIdSalesOrderItem(1)->setSku('215_123')->setQuantity(10)),
        );

        $itemCollectionTransfer = (new ItemCollectionTransfer())->addItem(
            (new ItemTransfer())
                ->setIdSalesOrderItem(1)
                ->setSku('215_123')
                ->setQuantity(10)
                ->setQuantitySalesUnit(
                    (new ProductMeasurementSalesUnitTransfer())
                        ->setConversion(5.0)
                        ->setPrecision(100)
                        ->setProductMeasurementUnit(
                            (new ProductMeasurementUnitTransfer())->setCode('METR')->setName('Metre'),
                        )
                        ->setProductMeasurementBaseUnit(
                            (new ProductMeasurementBaseUnitTransfer())->setProductMeasurementUnit(
                                (new ProductMeasurementUnitTransfer())->setName('Item'),
                            ),
                        ),
                ),
        );

        $provider = $this->createProvider($orderCollectionTransfer, $itemCollectionTransfer);

        // Act
        $resource = $this->provideOrder($provider, new Get(class: OrdersBackendResource::class), ['orderReference' => static::ORDER_REFERENCE], ['request' => new Request()]);

        // Assert
        $salesUnit = $this->getItems($resource)[0]->getSalesUnit();
        $this->assertNotNull($salesUnit);

        $this->assertSame('METR', $salesUnit->getCode());
        $this->assertSame('Metre', $salesUnit->getName());
        $this->assertSame('Item', $salesUnit->getBaseUnitName());
        $this->assertSame(5.0, $salesUnit->getConversion());
        $this->assertSame(100, $salesUnit->getPrecision());
        $this->assertSame(2.0, $salesUnit->getAmount());
    }

    /**
     * The per-package figure is reproduced as `amount / quantity`, which is right whether or not
     * checkout split the line — a split line carries quantity 1 and the per-package amount already.
     */
    public function testGivenExpandedItemsWhenProvideItemThenThePackagingAmountIsReportedPerPackage(): void
    {
        // Arrange
        $orderCollectionTransfer = (new OrderCollectionTransfer())->addOrder(
            (new OrderTransfer())
                ->setOrderReference(static::ORDER_REFERENCE)
                ->addItem((new ItemTransfer())->setIdSalesOrderItem(1)->setSku('218_1234')->setQuantity(3)),
        );

        $itemCollectionTransfer = (new ItemCollectionTransfer())->addItem(
            (new ItemTransfer())
                ->setIdSalesOrderItem(1)
                ->setSku('218_1234')
                ->setQuantity(3)
                ->setAmount(Decimal::create(750))
                ->setAmountLeadProduct((new ProductConcreteTransfer())->setSku('218_123'))
                ->setAmountSalesUnit(
                    (new ProductMeasurementSalesUnitTransfer())->setProductMeasurementUnit(
                        (new ProductMeasurementUnitTransfer())->setCode('KILO')->setName('Kilo'),
                    ),
                ),
        );

        $provider = $this->createProvider($orderCollectionTransfer, $itemCollectionTransfer);

        // Act
        $resource = $this->provideOrder($provider, new Get(class: OrdersBackendResource::class), ['orderReference' => static::ORDER_REFERENCE], ['request' => new Request()]);

        // Assert
        $packagingAmount = $this->getItems($resource)[0]->getPackagingAmount();
        $this->assertNotNull($packagingAmount);

        $this->assertSame(250.0, $packagingAmount->getAmount());
        $this->assertSame('KILO', $packagingAmount->getSalesUnitCode());
        $this->assertSame('Kilo', $packagingAmount->getSalesUnitName());
        $this->assertSame('218_123', $packagingAmount->getLeadProductSku());
    }

    /**
     * A product that is not sold as a package carries no amount, and `skip_null_values` drops the key.
     */
    public function testGivenAnItemWithoutAnAmountWhenProvideItemThenPackagingAmountIsNull(): void
    {
        // Arrange
        $orderCollectionTransfer = (new OrderCollectionTransfer())->addOrder(
            (new OrderTransfer())
                ->setOrderReference(static::ORDER_REFERENCE)
                ->addItem((new ItemTransfer())->setIdSalesOrderItem(1)->setSku('sku-1')->setQuantity(1)),
        );

        $itemCollectionTransfer = (new ItemCollectionTransfer())->addItem(
            (new ItemTransfer())->setIdSalesOrderItem(1)->setSku('sku-1')->setQuantity(1),
        );

        $provider = $this->createProvider($orderCollectionTransfer, $itemCollectionTransfer);

        // Act
        $resource = $this->provideOrder($provider, new Get(class: OrdersBackendResource::class), ['orderReference' => static::ORDER_REFERENCE], ['request' => new Request()]);

        // Assert
        $this->assertNull($this->getItems($resource)[0]->getPackagingAmount());
    }

    /**
     * A product sold in base units has no sales unit, and `skip_null_values` then drops the key.
     */
    public function testGivenAnItemWithoutASalesUnitWhenProvideItemThenSalesUnitIsNull(): void
    {
        // Arrange
        $orderCollectionTransfer = (new OrderCollectionTransfer())->addOrder(
            (new OrderTransfer())
                ->setOrderReference(static::ORDER_REFERENCE)
                ->addItem((new ItemTransfer())->setIdSalesOrderItem(1)->setSku('sku-1')->setQuantity(1)),
        );

        $itemCollectionTransfer = (new ItemCollectionTransfer())->addItem(
            (new ItemTransfer())->setIdSalesOrderItem(1)->setSku('sku-1')->setQuantity(1),
        );

        $provider = $this->createProvider($orderCollectionTransfer, $itemCollectionTransfer);

        // Act
        $resource = $this->provideOrder($provider, new Get(class: OrdersBackendResource::class), ['orderReference' => static::ORDER_REFERENCE], ['request' => new Request()]);

        // Assert
        $this->assertNull($this->getItems($resource)[0]->getSalesUnit());
    }

    /**
     * `calculatedDiscounts` is attached by the ORDER hydration stack, so it exists only on the
     * collection's items and has to survive the swap.
     */
    public function testGivenExpandedItemsWhenProvideItemThenLineDiscountsFromOrderHydrationSurvive(): void
    {
        // Arrange
        $orderCollectionTransfer = (new OrderCollectionTransfer())->addOrder(
            (new OrderTransfer())
                ->setOrderReference(static::ORDER_REFERENCE)
                ->addItem(
                    (new ItemTransfer())
                        ->setIdSalesOrderItem(1)
                        ->setSku('sku-1')
                        ->setQuantity(1)
                        ->addCalculatedDiscount(
                            (new CalculatedDiscountTransfer())->setDisplayName('Spring sale')->setSumAmount(250),
                        ),
                ),
        );

        $itemCollectionTransfer = (new ItemCollectionTransfer())->addItem(
            (new ItemTransfer())->setIdSalesOrderItem(1)->setSku('sku-1')->setQuantity(1),
        );

        $provider = $this->createProvider($orderCollectionTransfer, $itemCollectionTransfer);

        // Act
        $resource = $this->provideOrder($provider, new Get(class: OrdersBackendResource::class), ['orderReference' => static::ORDER_REFERENCE], ['request' => new Request()]);

        // Assert
        $this->assertSame('Spring sale', $this->getItems($resource)[0]->getCalculatedDiscounts()[0]->getDisplayName());
    }

    /**
     * A line the second read does not return is kept as the collection had it rather than dropped.
     */
    public function testGivenAnItemMissingFromTheSecondReadWhenProvideItemThenTheOriginalLineIsKept(): void
    {
        // Arrange
        $orderCollectionTransfer = (new OrderCollectionTransfer())->addOrder(
            (new OrderTransfer())
                ->setOrderReference(static::ORDER_REFERENCE)
                ->addItem((new ItemTransfer())->setIdSalesOrderItem(1)->setSku('sku-1')->setQuantity(1))
                ->addItem((new ItemTransfer())->setIdSalesOrderItem(2)->setSku('sku-2')->setQuantity(1)),
        );

        $itemCollectionTransfer = (new ItemCollectionTransfer())->addItem(
            (new ItemTransfer())->setIdSalesOrderItem(1)->setSku('sku-1')->setQuantity(1),
        );

        $provider = $this->createProvider($orderCollectionTransfer, $itemCollectionTransfer);

        // Act
        $resource = $this->provideOrder($provider, new Get(class: OrdersBackendResource::class), ['orderReference' => static::ORDER_REFERENCE], ['request' => new Request()]);

        // Assert
        $items = $this->getItems($resource);

        $this->assertCount(2, $items);
        $this->assertSame('sku-2', $items[1]->getSku());
    }

    /**
     * The collection route stays thin: no per-line option detail, and no second read to get it.
     */
    public function testGivenCollectionRouteWhenProvideCollectionThenNoOrderItemsReadIsMade(): void
    {
        // Arrange
        $orderCollectionTransfer = (new OrderCollectionTransfer())->addOrder(
            (new OrderTransfer())->addItem((new ItemTransfer())->setIdSalesOrderItem(1)->setSku('sku-1')->setQuantity(1)),
        );

        $salesFacadeMock = $this->createMock(SalesFacadeInterface::class);
        $salesFacadeMock->method('getOrderCollection')->willReturn($orderCollectionTransfer);
        $salesFacadeMock->expects($this->never())->method('getOrderItems');

        $provider = new OrdersBackendProvider(
            $salesFacadeMock,
            $this->createOrderResourceReader(
                $salesFacadeMock,
                Stub::makeEmpty(OrderExperienceManagementFacadeInterface::class, ['getAvailableOrderItemTransitions' => []]),
                $this->createOrderCommentReaderStub(),
            ),
            new OrdersPayloadShapeValidator(),
            new OrdersBackendExceptionFactory(),
        );

        // Act
        $resources = $this->provideOrders($provider, new GetCollection(class: OrdersBackendResource::class), ['request' => new Request()]);

        // Assert
        $this->assertNull($resources[0]->items);
    }

    public function testGivenLimitAboveOperationMaximumWhenProvideCollectionThenLimitIsClampedToOperationMaximum(): void
    {
        // Arrange
        $capturedOrderCriteriaTransfer = null;

        $salesFacade = Stub::makeEmpty(SalesFacadeInterface::class, [
            'getOrderCollection' => function (OrderCriteriaTransfer $orderCriteriaTransfer) use (&$capturedOrderCriteriaTransfer): OrderCollectionTransfer {
                $capturedOrderCriteriaTransfer = $orderCriteriaTransfer;

                return new OrderCollectionTransfer();
            },
        ]);

        $provider = new OrdersBackendProvider(
            $salesFacade,
            $this->createOrderResourceReader(
                $salesFacade,
                Stub::makeEmpty(OrderExperienceManagementFacadeInterface::class, ['getAvailableOrderItemTransitions' => []]),
                $this->createOrderCommentReaderStub(),
            ),
            new OrdersPayloadShapeValidator(),
            new OrdersBackendExceptionFactory(),
        );

        // Act
        $this->provideOrders($provider, new GetCollection(class: OrdersBackendResource::class, paginationMaximumItemsPerPage: 100), ['request' => new Request(['page' => ['limit' => '999']])]);

        // Assert
        $this->assertNotNull($capturedOrderCriteriaTransfer);
        $this->assertSame(100, $capturedOrderCriteriaTransfer->getPaginationOrFail()->getLimitOrFail());
    }

    public function testGivenLimitWithinOperationMaximumWhenProvideCollectionThenLimitIsNotClamped(): void
    {
        // Arrange
        $capturedOrderCriteriaTransfer = null;

        $salesFacade = Stub::makeEmpty(SalesFacadeInterface::class, [
            'getOrderCollection' => function (OrderCriteriaTransfer $orderCriteriaTransfer) use (&$capturedOrderCriteriaTransfer): OrderCollectionTransfer {
                $capturedOrderCriteriaTransfer = $orderCriteriaTransfer;

                return new OrderCollectionTransfer();
            },
        ]);

        $provider = new OrdersBackendProvider(
            $salesFacade,
            $this->createOrderResourceReader(
                $salesFacade,
                Stub::makeEmpty(OrderExperienceManagementFacadeInterface::class, ['getAvailableOrderItemTransitions' => []]),
                $this->createOrderCommentReaderStub(),
            ),
            new OrdersPayloadShapeValidator(),
            new OrdersBackendExceptionFactory(),
        );

        // Act
        $this->provideOrders($provider, new GetCollection(class: OrdersBackendResource::class, paginationMaximumItemsPerPage: 100), ['request' => new Request(['page' => ['limit' => '50']])]);

        // Assert
        $this->assertNotNull($capturedOrderCriteriaTransfer);
        $this->assertSame(50, $capturedOrderCriteriaTransfer->getPaginationOrFail()->getLimitOrFail());
    }

    public function testGivenOrdersCollectionWhenProvideCollectionThenCollectionPaginationIsPublishedForResponseTransform(): void
    {
        // Arrange
        $orderCollectionTransfer = (new OrderCollectionTransfer())->addOrder(
            (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE)->setCustomerReference(static::CUSTOMER_REFERENCE),
        );

        $salesFacade = Stub::makeEmpty(SalesFacadeInterface::class, [
            'getOrderCollection' => function (OrderCriteriaTransfer $orderCriteriaTransfer) use ($orderCollectionTransfer): OrderCollectionTransfer {
                $orderCriteriaTransfer->getPaginationOrFail()->setNbResults(1);

                return $orderCollectionTransfer;
            },
        ]);

        $provider = new OrdersBackendProvider(
            $salesFacade,
            $this->createOrderResourceReader(
                $salesFacade,
                Stub::makeEmpty(OrderExperienceManagementFacadeInterface::class, ['getAvailableOrderItemTransitions' => []]),
                $this->createOrderCommentReaderStub(),
            ),
            new OrdersPayloadShapeValidator(),
            new OrdersBackendExceptionFactory(),
        );

        $request = new Request(['page' => ['limit' => '25', 'offset' => '0']]);

        // Act
        $this->provideOrders($provider, new GetCollection(class: OrdersBackendResource::class, paginationItemsPerPage: 25, paginationMaximumItemsPerPage: 100), ['request' => $request]);

        // Assert
        $pagination = $request->attributes->get(PaginationLinksTransform::REQUEST_ATTRIBUTE_PAGINATION);

        $this->assertIsArray($pagination);
        $this->assertSame(1, $pagination['numFound']);
        $this->assertSame(25, $pagination['currentItemsPerPage']);
    }

    /**
     * Comments are the one field the item route READS rather than reads off the order transfer, so
     * what is asserted here is that the extra read happens and lands on the resource.
     */
    public function testProvideItemReportsOrderComments(): void
    {
        // Arrange
        $orderTransfer = (new OrderTransfer())
            ->setIdSalesOrder(42)
            ->setOrderReference(static::ORDER_REFERENCE)
            ->setCustomerReference(static::CUSTOMER_REFERENCE);

        $salesFacade = Stub::makeEmpty(SalesFacadeInterface::class, [
            'getOrderCollection' => fn (): OrderCollectionTransfer => (new OrderCollectionTransfer())->addOrder($orderTransfer),
            'getOrderItems' => fn (): ItemCollectionTransfer => new ItemCollectionTransfer(),
        ]);

        $provider = new OrdersBackendProvider(
            $salesFacade,
            $this->createOrderResourceReader(
                $salesFacade,
                Stub::makeEmpty(OrderExperienceManagementFacadeInterface::class, ['getAvailableOrderItemTransitions' => []]),
                $this->createOrderCommentReaderStub([
                    (new CommentTransfer())
                        ->setMessage(static::COMMENT_MESSAGE)
                        ->setUsername(static::COMMENT_USERNAME)
                        ->setCreatedAt(static::COMMENT_CREATED_AT),
                ]),
            ),
            new OrdersPayloadShapeValidator(),
            new OrdersBackendExceptionFactory(),
        );

        // Act
        $resource = $this->provideOrder($provider, new Get(class: OrdersBackendResource::class), ['orderReference' => static::ORDER_REFERENCE], ['request' => new Request()]);

        // Assert
        $comments = $resource->comments;
        $this->assertIsArray($comments);

        $this->assertCount(1, $comments);
        $this->assertSame(static::COMMENT_MESSAGE, $comments[0]->getMessage());
        $this->assertSame(static::COMMENT_USERNAME, $comments[0]->getUsername());
        $this->assertSame(static::COMMENT_CREATED_AT, $comments[0]->getCreatedAt());
    }

    /**
     * Reading comments costs one query per order, so the collection route must not ask for them at
     * all — `comments` stays null and `skip_null_values` drops the key. A reader stub that fails the
     * test when called is what proves the read is skipped rather than merely unserialised.
     */
    public function testProvideCollectionOmitsOrderComments(): void
    {
        // Arrange
        $orderTransfer = (new OrderTransfer())
            ->setIdSalesOrder(42)
            ->setOrderReference(static::ORDER_REFERENCE)
            ->setCustomerReference(static::CUSTOMER_REFERENCE);

        $salesFacade = Stub::makeEmpty(SalesFacadeInterface::class, [
            'getOrderCollection' => fn (): OrderCollectionTransfer => (new OrderCollectionTransfer())->addOrder($orderTransfer),
        ]);

        $provider = new OrdersBackendProvider(
            $salesFacade,
            $this->createOrderResourceReader(
                $salesFacade,
                Stub::makeEmpty(OrderExperienceManagementFacadeInterface::class, ['getAvailableOrderItemTransitions' => []]),
                Stub::makeEmpty(OrderCommentReaderInterface::class, [
                    'getCommentsByIdSalesOrder' => function (): array {
                        $this->fail('The orders collection must not read comments.');
                    },
                ]),
            ),
            new OrdersPayloadShapeValidator(),
            new OrdersBackendExceptionFactory(),
        );

        // Act
        $resources = $this->provideOrders($provider, new GetCollection(class: OrdersBackendResource::class), ['request' => new Request()]);

        // Assert
        $this->assertCount(1, $resources);
        $this->assertNull($resources[0]->comments);
    }

    public function testGivenUnparsableCreatedAtToWhenProvideCollectionThenBadRequestIsThrown(): void
    {
        // Arrange
        $provider = $this->createProvider(new OrderCollectionTransfer());
        $request = $this->createFilterRequest([static::FILTER_CREATED_AT_TO => static::UNPARSABLE_DATE]);

        // Act
        try {
            $provider->provide(new GetCollection(class: OrdersBackendResource::class), [], ['request' => $request]);
        } catch (GlueApiException $glueApiException) {
            // Assert
            $this->assertSame(Response::HTTP_BAD_REQUEST, $glueApiException->getStatusCode());
            $this->assertStringContainsString(static::FILTER_CREATED_AT_TO, $glueApiException->getMessage());

            return;
        }

        $this->fail('An unparsable createdAtTo filter value must be rejected with a 400, not reach the persistence layer.');
    }

    /**
     * @see testGivenUnparsableCreatedAtToWhenProvideCollectionThenBadRequestIsThrown
     */
    public function testGivenUnparsableCreatedAtFromWhenProvideCollectionThenBadRequestIsThrown(): void
    {
        // Arrange
        $provider = $this->createProvider(new OrderCollectionTransfer());
        $request = $this->createFilterRequest([static::FILTER_CREATED_AT_FROM => static::UNPARSABLE_DATE]);

        // Act
        try {
            $provider->provide(new GetCollection(class: OrdersBackendResource::class), [], ['request' => $request]);
        } catch (GlueApiException $glueApiException) {
            // Assert
            $this->assertSame(Response::HTTP_BAD_REQUEST, $glueApiException->getStatusCode());
            $this->assertStringContainsString(static::FILTER_CREATED_AT_FROM, $glueApiException->getMessage());

            return;
        }

        $this->fail('An unparsable createdAtFrom filter value must be rejected with a 400, not reach the persistence layer.');
    }

    public function testGivenParsableCreatedAtRangeWhenProvideCollectionThenBoundsReachTheCriteria(): void
    {
        // Arrange
        $capturedOrderCriteriaTransfer = null;

        $salesFacade = Stub::makeEmpty(SalesFacadeInterface::class, [
            'getOrderCollection' => function (OrderCriteriaTransfer $orderCriteriaTransfer) use (&$capturedOrderCriteriaTransfer): OrderCollectionTransfer {
                $capturedOrderCriteriaTransfer = $orderCriteriaTransfer;

                return new OrderCollectionTransfer();
            },
        ]);

        $provider = new OrdersBackendProvider(
            $salesFacade,
            $this->createOrderResourceReader(
                $salesFacade,
                Stub::makeEmpty(OrderExperienceManagementFacadeInterface::class, ['getAvailableOrderItemTransitions' => []]),
                $this->createOrderCommentReaderStub(),
            ),
            new OrdersPayloadShapeValidator(),
            new OrdersBackendExceptionFactory(),
        );

        $request = $this->createFilterRequest([
            static::FILTER_CREATED_AT_FROM => static::CREATED_AT_FROM,
            static::FILTER_CREATED_AT_TO => static::CREATED_AT_TO,
        ]);

        // Act
        $this->provideOrders($provider, new GetCollection(class: OrdersBackendResource::class), ['request' => $request]);

        // Assert
        $this->assertNotNull($capturedOrderCriteriaTransfer);
        $orderConditionsTransfer = $capturedOrderCriteriaTransfer->getOrderConditionsOrFail();
        $this->assertSame(static::CREATED_AT_FROM, $orderConditionsTransfer->getCreatedAtFrom());
        $this->assertSame(static::CREATED_AT_TO, $orderConditionsTransfer->getCreatedAtTo());
    }

    public function testGivenFilterKeyWithoutResourcePrefixWhenProvideCollectionThenBadRequestIsThrown(): void
    {
        // Arrange
        $provider = $this->createProvider(new OrderCollectionTransfer());
        $request = new Request([
            static::QUERY_PARAM_FILTER => [static::UNPREFIXED_FILTER_KEY => 'paid'],
        ]);

        // Act
        try {
            $provider->provide(new GetCollection(class: OrdersBackendResource::class), [], ['request' => $request]);
        } catch (GlueApiException $glueApiException) {
            // Assert
            $this->assertSame(Response::HTTP_BAD_REQUEST, $glueApiException->getStatusCode());
            $this->assertStringContainsString(static::UNPREFIXED_FILTER_KEY, $glueApiException->getMessage());

            return;
        }

        $this->fail('A filter key missing the "orders." prefix must be rejected, not silently dropped.');
    }

    public function testGivenUnsupportedFilterFieldWhenProvideCollectionThenBadRequestIsThrown(): void
    {
        // Arrange
        $provider = $this->createProvider(new OrderCollectionTransfer());
        $request = $this->createFilterRequest([static::UNSUPPORTED_FILTER_FIELD => 'paid']);

        // Act
        try {
            $provider->provide(new GetCollection(class: OrdersBackendResource::class), [], ['request' => $request]);
        } catch (GlueApiException $glueApiException) {
            // Assert
            $this->assertSame(Response::HTTP_BAD_REQUEST, $glueApiException->getStatusCode());
            $this->assertStringContainsString(static::UNSUPPORTED_FILTER_FIELD, $glueApiException->getMessage());
            $this->assertStringContainsString(
                static::FILTER_ITEM_STATE,
                $glueApiException->getMessage(),
                'The error must list the supported fields so a typo is self-correcting.',
            );

            return;
        }

        $this->fail('A typo in a filter field must be rejected — answering 200 with the full collection reads as success.');
    }

    public function testGivenNonScalarFilterValueWhenProvideCollectionThenBadRequestIsThrown(): void
    {
        // Arrange
        $provider = $this->createProvider(new OrderCollectionTransfer());
        $request = $this->createFilterRequest([static::FILTER_ITEM_STATE => ['paid', 'shipped']]);

        // Act
        try {
            $provider->provide(new GetCollection(class: OrdersBackendResource::class), [], ['request' => $request]);
        } catch (GlueApiException $glueApiException) {
            // Assert
            $this->assertSame(Response::HTTP_BAD_REQUEST, $glueApiException->getStatusCode());
            $this->assertStringContainsString(static::FILTER_ITEM_STATE, $glueApiException->getMessage());

            return;
        }

        $this->fail('Multi-value is expressed by comma-separating one scalar, so an array value must be rejected.');
    }

    public function testGivenScalarFilterParameterWhenProvideCollectionThenBadRequestIsThrown(): void
    {
        // Arrange
        $provider = $this->createProvider(new OrderCollectionTransfer());
        $request = new Request([static::QUERY_PARAM_FILTER => 'paid']);

        // Act
        try {
            $provider->provide(new GetCollection(class: OrdersBackendResource::class), [], ['request' => $request]);
        } catch (GlueApiException $glueApiException) {
            // Assert
            $this->assertSame(Response::HTTP_BAD_REQUEST, $glueApiException->getStatusCode());

            return;
        }

        $this->fail('`?filter=paid` is not a filter bag and must produce a 400 through the Glue envelope.');
    }

    public function testGivenCommaSeparatedFiltersWhenProvideCollectionThenEveryValueReachesTheCriteria(): void
    {
        // Arrange
        $capturedOrderCriteriaTransfer = null;
        $provider = $this->createCriteriaCapturingProvider($capturedOrderCriteriaTransfer);

        $request = $this->createFilterRequest([
            static::FILTER_ITEM_STATE => 'paid,shipped',
            static::FILTER_ORDER_REFERENCE => 'DE--1,DE--2',
            static::FILTER_CUSTOMER_REFERENCE => 'DE--customer-1',
            static::FILTER_STORE_NAME => 'DE',
        ]);

        // Act
        $this->provideOrders($provider, new GetCollection(class: OrdersBackendResource::class), ['request' => $request]);

        // Assert
        $this->assertNotNull($capturedOrderCriteriaTransfer);
        $orderConditionsTransfer = $capturedOrderCriteriaTransfer->getOrderConditionsOrFail();

        $this->assertSame(['paid', 'shipped'], $orderConditionsTransfer->getItemStates());
        $this->assertSame(['DE--1', 'DE--2'], $orderConditionsTransfer->getOrderReferences());
        $this->assertSame(['DE--customer-1'], $orderConditionsTransfer->getCustomerReferences());
        $this->assertSame(['DE'], $orderConditionsTransfer->getStoreNames());
    }

    public function testGivenAMisshapenPayloadWhenProvidePostThenItIsRejectedWithTheOffendingPath(): void
    {
        // Arrange
        $provider = $this->createProvider(new OrderCollectionTransfer());
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], '{"data":{"type":"orders","attributes":{"items":"abc"}}}');

        // Act
        try {
            $provider->provide(new Post(class: OrdersBackendResource::class), [], ['request' => $request]);
            $this->fail('A misshapen payload must be rejected before it is deserialized.');
        } catch (GlueApiException $glueApiException) {
            // Assert
            $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $glueApiException->getStatusCode());
            $this->assertSame('"items" must be an array of objects.', $glueApiException->getMessage());
        }
    }

    public function testGivenAWellShapedPayloadWhenProvidePostThenNothingIsProvided(): void
    {
        // Arrange
        $provider = $this->createProvider(new OrderCollectionTransfer());
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], '{"data":{"type":"orders","attributes":{"items":[{"sku":"001","quantity":1}]}}}');

        // Act
        $result = $provider->provide(new Post(class: OrdersBackendResource::class), [], ['request' => $request]);

        // Assert
        $this->assertNull($result);
    }

    public function testGivenNoFilterParameterWhenProvideCollectionThenNoConditionsAreApplied(): void
    {
        // Arrange
        $capturedOrderCriteriaTransfer = null;
        $provider = $this->createCriteriaCapturingProvider($capturedOrderCriteriaTransfer);

        // Act
        $this->provideOrders($provider, new GetCollection(class: OrdersBackendResource::class), ['request' => new Request()]);

        // Assert
        $this->assertNotNull($capturedOrderCriteriaTransfer);
        $orderConditionsTransfer = $capturedOrderCriteriaTransfer->getOrderConditionsOrFail();

        $this->assertCount(0, $orderConditionsTransfer->getItemStates());
        $this->assertCount(0, $orderConditionsTransfer->getOrderReferences());
        $this->assertNull($orderConditionsTransfer->getCreatedAtFrom());
    }

    /**
     * Filters travel as `filter[orders.<field>]`, so the test builds the nested bag PHP's query
     * parser would have produced rather than re-encoding the bracket syntax by hand.
     *
     * @param array<string, mixed> $filters
     */
    protected function createFilterRequest(array $filters): Request
    {
        $filterBag = [];

        foreach ($filters as $field => $value) {
            $filterBag[static::FILTER_KEY_PREFIX . $field] = $value;
        }

        return new Request([static::QUERY_PARAM_FILTER => $filterBag]);
    }

    protected function createCriteriaCapturingProvider(?OrderCriteriaTransfer &$capturedOrderCriteriaTransfer): OrdersBackendProvider
    {
        $salesFacade = Stub::makeEmpty(SalesFacadeInterface::class, [
            'getOrderCollection' => function (OrderCriteriaTransfer $orderCriteriaTransfer) use (&$capturedOrderCriteriaTransfer): OrderCollectionTransfer {
                $capturedOrderCriteriaTransfer = $orderCriteriaTransfer;

                return new OrderCollectionTransfer();
            },
        ]);

        return new OrdersBackendProvider(
            $salesFacade,
            $this->createOrderResourceReader(
                $salesFacade,
                Stub::makeEmpty(OrderExperienceManagementFacadeInterface::class, ['getAvailableOrderItemTransitions' => []]),
                $this->createOrderCommentReaderStub(),
            ),
            new OrdersPayloadShapeValidator(),
            new OrdersBackendExceptionFactory(),
        );
    }

    /**
     * @param array<int, array<string>> $availableTransitionsByOrderItemId Keyed by idSalesOrderItem.
     * @param array<\SprykerFeature\Glue\OrderExperienceManagement\Dependency\Plugin\OrderResourceExpanderPluginInterface> $orderResourceExpanderPlugins
     */
    protected function createProvider(
        OrderCollectionTransfer $orderCollectionTransfer,
        ?ItemCollectionTransfer $itemCollectionTransfer = null,
        array $availableTransitionsByOrderItemId = [],
        array $orderResourceExpanderPlugins = [],
    ): OrdersBackendProvider {
        $salesFacade = Stub::makeEmpty(SalesFacadeInterface::class, [
            'getOrderCollection' => fn (OrderCriteriaTransfer $orderCriteriaTransfer): OrderCollectionTransfer => $orderCollectionTransfer,
            'getOrderItems' => fn (OrderItemFilterTransfer $orderItemFilterTransfer): ItemCollectionTransfer => $itemCollectionTransfer ?? new ItemCollectionTransfer(),
        ]);

        return new OrdersBackendProvider(
            $salesFacade,
            $this->createOrderResourceReader(
                $salesFacade,
                Stub::makeEmpty(OrderExperienceManagementFacadeInterface::class, [
                    'getAvailableOrderItemTransitions' => fn (ItemCollectionTransfer $itemCollectionTransfer): array => $availableTransitionsByOrderItemId,
                ]),
                $this->createOrderCommentReaderStub(),
                $orderResourceExpanderPlugins,
            ),
            new OrdersPayloadShapeValidator(),
            new OrdersBackendExceptionFactory(),
        );
    }

    /**
     * Builds the same `OrderResourceReader` the container wires in production — `OrdersBackendProvider`
     * takes it as a single constructor dependency now, so a test that needs a specific
     * `SalesFacadeInterface`/`OrderExperienceManagementFacadeInterface`/`OrderCommentReaderInterface`
     * stub or expander plugin assembles it here rather than passing them to the provider directly.
     *
     * @param array<\SprykerFeature\Glue\OrderExperienceManagement\Dependency\Plugin\OrderResourceExpanderPluginInterface> $orderResourceExpanderPlugins
     */
    protected function createOrderResourceReader(
        SalesFacadeInterface $salesFacade,
        OrderExperienceManagementFacadeInterface $orderExperienceManagementFacade,
        OrderCommentReaderInterface $orderCommentReader,
        array $orderResourceExpanderPlugins = [],
    ): OrderResourceReaderInterface {
        return new OrderResourceReader(
            $salesFacade,
            $orderExperienceManagementFacade,
            $orderCommentReader,
            $this->tester->createOrderResourceMapper(),
            $orderResourceExpanderPlugins,
        );
    }

    /**
     * Stands in for a module that contributes a property of its own to the `orders` schema — the
     * position PurchasingControl's budget plugin occupies — without this module's tests having to
     * know what that property means.
     */
    protected function createOrderResourceExpanderPlugin(): OrderResourceExpanderPluginInterface
    {
        return new class implements OrderResourceExpanderPluginInterface {
            public function expand(
                OrdersBackendResource $ordersBackendResource,
                OrderTransfer $orderTransfer,
            ): OrdersBackendResource {
                $ordersBackendResource->orderCustomReference = sprintf(
                    'expanded:%s',
                    (string)$orderTransfer->getOrderReference(),
                );

                return $ordersBackendResource;
            }
        };
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\CommentTransfer> $commentTransfers
     */
    protected function createOrderCommentReaderStub(array $commentTransfers = []): OrderCommentReaderInterface
    {
        return Stub::makeEmpty(OrderCommentReaderInterface::class, [
            'getCommentsByIdSalesOrder' => fn (int $idSalesOrder): array => $commentTransfers,
        ]);
    }

    /**
     * `ProviderInterface::provide()` is contractually typed `object|array|null` (API Platform's
     * generic state-provider contract), so every direct call site in this file would otherwise
     * force PHPStan to treat the result as that union rather than the concrete resource this
     * module's `Get` route always returns. Narrowing once here, instead of at each of the ~30 call
     * sites, is what keeps the assertions below type-safe without repeating the assertion at every
     * test.
     *
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    protected function provideOrder(OrdersBackendProvider $provider, Get $operation, array $uriVariables, array $context): OrdersBackendResource
    {
        $resource = $provider->provide($operation, $uriVariables, $context);
        $this->assertInstanceOf(OrdersBackendResource::class, $resource);

        return $resource;
    }

    /**
     * The `GetCollection` counterpart of {@see provideOrder()} — narrows the same
     * `object|array|null` contract down to the concrete resource array this module's collection
     * route always returns.
     *
     * @param array<string, mixed> $context
     *
     * @return array<\Generated\Api\Backend\OrdersBackendResource>
     */
    protected function provideOrders(OrdersBackendProvider $provider, GetCollection $operation, array $context): array
    {
        $resources = $provider->provide($operation, [], $context);
        $this->assertIsArray($resources);
        $this->assertContainsOnlyInstancesOf(OrdersBackendResource::class, $resources);

        return $resources;
    }

    /**
     * `OrdersBackendResource::$items` is `?array` because the collection route always leaves it
     * null (see {@see testGivenOrderWithItemsWhenProvideCollectionThenItemsAreOmittedButItemsCountIsReported}).
     * Every test reaching this helper is on the single-order route, which always populates it.
     *
     * @return array<\Generated\Api\Backend\OrdersItem>
     */
    protected function getItems(OrdersBackendResource $resource): array
    {
        $this->assertIsArray($resource->items);

        return $resource->items;
    }
}
