<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Glue\OrderExperienceManagement\Api\Backend\Processor;

use ApiPlatform\Metadata\Post;
use Codeception\Stub;
use Codeception\Test\Unit;
use Generated\Api\Backend\Orders\OrdersBillingAddressBackendObject;
use Generated\Api\Backend\Orders\OrdersShipmentBackendObject;
use Generated\Api\Backend\Orders\OrdersShipmentShippingAddressBackendObject;
use Generated\Api\Backend\OrdersBackendResource;
use Generated\Api\Backend\OrdersItem;
use Generated\Api\Backend\OrdersItemShipmentBackendObject;
use Generated\Api\Backend\OrdersItemShipmentShippingAddressBackendObject;
use Generated\Shared\Transfer\ItemCollectionTransfer;
use Generated\Shared\Transfer\LocaleTransfer;
use Generated\Shared\Transfer\OrderCollectionTransfer;
use Generated\Shared\Transfer\OrderIntakeItemTransfer;
use Generated\Shared\Transfer\OrderIntakeRequestTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\OrderIntakeValidationIssueTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Spryker\ApiPlatform\Exception\GlueApiException;
use Spryker\Zed\Sales\Business\SalesFacadeInterface;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Exception\OrdersBackendExceptionFactory;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Processor\OrdersBackendProcessor;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Reader\OrderCommentReaderInterface;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Reader\OrderResourceReader;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Reader\OrderResourceReaderInterface;
use SprykerFeature\Glue\OrderExperienceManagement\Dependency\Plugin\OrderResourceExpanderPluginInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementFacadeInterface;
use SprykerFeatureTest\Glue\OrderExperienceManagement\OrderExperienceManagementGlueTester;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Glue
 * @group OrderExperienceManagement
 * @group Api
 * @group Backend
 * @group Processor
 * @group OrdersBackendProcessorTest
 * Add your own group annotations below this line
 */
class OrdersBackendProcessorTest extends Unit
{
    protected OrderExperienceManagementGlueTester $tester;

    protected const string CUSTOMER_REFERENCE = 'DE--1';

    protected const string ORDER_REFERENCE = 'DE--1234';

    protected const string LOCALE_NAME_DE_DE = 'de_DE';

    protected const string BUDGET_UUID = '3fa85f64-5717-4562-b3fc-2c963f66afa6';

    protected const string ORDER_CUSTOM_REFERENCE = 'PO-4711';

    protected const string COMPANY_BUSINESS_UNIT_UUID = '9c1e0b2a-4f3d-4a91-8c77-1d5b6e2f0a34';

    protected const string PRICE_MODE = 'GROSS';

    public function testGivenTypedNestedObjectsWhenProcessPostThenMapsThemOntoOrderIntakeRequest(): void
    {
        // Arrange
        $resource = new OrdersBackendResource();
        $resource->customerReference = static::CUSTOMER_REFERENCE;
        $resource->store = 'DE';
        $resource->currency = 'EUR';
        $resource->paymentMethod = 'dummyPaymentInvoice';
        $resource->items = [];

        $resource->shipment = (new OrdersShipmentBackendObject())
            ->setShipmentMethod('Standard')
            ->setRequestedDeliveryDate('2026-09-04')
            ->setShippingAddress(
                (new OrdersShipmentShippingAddressBackendObject())
                    ->setFirstName('Ada')
                    ->setLastName('Lovelace')
                    ->setZipCode('10115')
                    ->setCity('Berlin')
                    ->setIso2Code('DE'),
            );

        $resource->billingAddress = (new OrdersBillingAddressBackendObject())
            ->setFirstName('Grace')
            ->setLastName('Hopper')
            ->setZipCode('20115')
            ->setCity('Hamburg')
            ->setIso2Code('DE');

        $resource->companyBusinessUnitUuid = '9c1e0b2a-4f3d-4a91-8c77-1d5b6e2f0a34';

        $capturedRequestTransfer = null;

        $facade = Stub::makeEmpty(OrderExperienceManagementFacadeInterface::class, [
            'createOrderFromIntake' => function (OrderIntakeRequestTransfer $orderIntakeRequestTransfer) use (&$capturedRequestTransfer): OrderIntakeResponseTransfer {
                $capturedRequestTransfer = $orderIntakeRequestTransfer;

                return (new OrderIntakeResponseTransfer())
                    ->setIsSuccessful(true)
                    ->setOrderReference('DE--1234')
                    ->setCustomerReference($orderIntakeRequestTransfer->getCustomer()?->getCustomerReference());
            },
        ]);

        $processor = $this->createProcessor($facade);

        // Act
        $processor->process($resource, new Post(class: OrdersBackendResource::class), [], ['request' => new Request()]);

        // Assert
        $this->assertNotNull($capturedRequestTransfer);
        $this->assertSame('Standard', $capturedRequestTransfer->getShipmentMethodName());
        $this->assertSame('2026-09-04', $capturedRequestTransfer->getRequestedDeliveryDate());

        $shippingAddressTransfer = $capturedRequestTransfer->getShippingAddress();
        $this->assertNotNull($shippingAddressTransfer);
        $this->assertSame('Ada', $shippingAddressTransfer->getFirstName());
        $this->assertSame('Lovelace', $shippingAddressTransfer->getLastName());
        $this->assertSame('10115', $shippingAddressTransfer->getZipCode());

        $billingAddressTransfer = $capturedRequestTransfer->getBillingAddress();
        $this->assertNotNull($billingAddressTransfer);
        $this->assertSame('Grace', $billingAddressTransfer->getFirstName());
        $this->assertSame('Hopper', $billingAddressTransfer->getLastName());
        $this->assertSame('20115', $billingAddressTransfer->getZipCode());

        $this->assertSame(static::CUSTOMER_REFERENCE, $capturedRequestTransfer->getCustomer()?->getCustomerReference());
        $this->assertSame(
            '9c1e0b2a-4f3d-4a91-8c77-1d5b6e2f0a34',
            $capturedRequestTransfer->getCompanyBusinessUnitUuid(),
        );

        // The processor must NOT pre-build a company user: only the resolver can turn a business unit
        // into the customer's company user in it, hydrated with the merchant relationships pricing needs.
        $this->assertNull($capturedRequestTransfer->getCustomer()->getCompanyUserTransfer());
    }

    /**
     * Only the code crosses the Glue boundary — the resource declares `cartCodes` as a plain string
     * array. Everything the redemption needs beyond that (applicability, amount, currency) is resolved
     * downstream by CartCodeFacade and the plugin stack it runs, not by this mapping.
     */
    public function testGivenCartCodesWhenProcessPostThenMapsThemOntoOrderIntakeRequest(): void
    {
        // Arrange
        $resource = new OrdersBackendResource();
        $resource->customerReference = static::CUSTOMER_REFERENCE;
        $resource->items = [];
        $resource->cartCodes = ['GC-1234-5678', 'GC-8765-4321'];

        $capturedRequestTransfer = null;

        $facade = Stub::makeEmpty(OrderExperienceManagementFacadeInterface::class, [
            'createOrderFromIntake' => function (OrderIntakeRequestTransfer $orderIntakeRequestTransfer) use (&$capturedRequestTransfer): OrderIntakeResponseTransfer {
                $capturedRequestTransfer = $orderIntakeRequestTransfer;

                return (new OrderIntakeResponseTransfer())->setIsSuccessful(true);
            },
        ]);

        $processor = $this->createProcessor($facade);

        // Act
        $processor->process($resource, new Post(class: OrdersBackendResource::class), [], ['request' => new Request()]);

        // Assert
        $this->assertNotNull($capturedRequestTransfer);
        $this->assertSame(['GC-1234-5678', 'GC-8765-4321'], $capturedRequestTransfer->getCartCodes());
    }

    /**
     * No setter call maps these: they reach the transfer through the scalar pass alone, because the
     * schema and the transfer spell them the same way. `budgetUuid` is the case that matters most —
     * PurchasingControl declares it on both `orders.resource.yml` and `OrderIntakeRequest`, and it
     * arrives without this module naming it, which is what lets a module add a field without
     * touching the processor. The other three are this module's own, and are asserted here because
     * this test is now the only thing standing between them and a silent rename.
     */
    public function testGivenIdenticallyNamedScalarsWhenProcessPostThenCarriesThemOntoOrderIntakeRequest(): void
    {
        // Arrange
        $resource = new OrdersBackendResource();
        $resource->customerReference = static::CUSTOMER_REFERENCE;
        $resource->items = [];
        $resource->budgetUuid = static::BUDGET_UUID;
        $resource->orderCustomReference = static::ORDER_CUSTOM_REFERENCE;
        $resource->companyBusinessUnitUuid = static::COMPANY_BUSINESS_UNIT_UUID;
        $resource->priceMode = static::PRICE_MODE;

        $capturedRequestTransfer = null;

        $facade = Stub::makeEmpty(OrderExperienceManagementFacadeInterface::class, [
            'createOrderFromIntake' => function (OrderIntakeRequestTransfer $orderIntakeRequestTransfer) use (&$capturedRequestTransfer): OrderIntakeResponseTransfer {
                $capturedRequestTransfer = $orderIntakeRequestTransfer;

                return (new OrderIntakeResponseTransfer())->setIsSuccessful(true);
            },
        ]);

        $processor = $this->createProcessor($facade);

        // Act
        $processor->process($resource, new Post(class: OrdersBackendResource::class), [], ['request' => new Request()]);

        // Assert
        $this->assertNotNull($capturedRequestTransfer);
        $this->assertSame(static::BUDGET_UUID, $capturedRequestTransfer->getBudgetUuid());
        $this->assertSame(static::ORDER_CUSTOM_REFERENCE, $capturedRequestTransfer->getOrderCustomReference());
        $this->assertSame(static::COMPANY_BUSINESS_UNIT_UUID, $capturedRequestTransfer->getCompanyBusinessUnitUuid());
        $this->assertSame(static::PRICE_MODE, $capturedRequestTransfer->getPriceMode());
    }

    /**
     * The scalar carry-over runs before the explicit mapping, so a property the two sides spell
     * differently must still end up under the transfer's name — and must not leak in under the
     * schema's.
     */
    public function testGivenAPropertyTheSchemaAndTransferSpellDifferentlyWhenProcessPostThenTheExplicitMappingWins(): void
    {
        // Arrange
        $resource = new OrdersBackendResource();
        $resource->customerReference = static::CUSTOMER_REFERENCE;
        $resource->items = [];
        $resource->store = 'DE';
        $resource->currency = 'EUR';

        $capturedRequestTransfer = null;

        $facade = Stub::makeEmpty(OrderExperienceManagementFacadeInterface::class, [
            'createOrderFromIntake' => function (OrderIntakeRequestTransfer $orderIntakeRequestTransfer) use (&$capturedRequestTransfer): OrderIntakeResponseTransfer {
                $capturedRequestTransfer = $orderIntakeRequestTransfer;

                return (new OrderIntakeResponseTransfer())->setIsSuccessful(true);
            },
        ]);

        $processor = $this->createProcessor($facade);

        // Act
        $processor->process($resource, new Post(class: OrdersBackendResource::class), [], ['request' => new Request()]);

        // Assert
        $this->assertNotNull($capturedRequestTransfer);
        $this->assertSame('DE', $capturedRequestTransfer->getStoreName());
        $this->assertSame('EUR', $capturedRequestTransfer->getCurrencyCode());
    }

    public function testGivenNoCartCodesWhenProcessPostThenMapsAnEmptyCartCodeList(): void
    {
        // Arrange
        $resource = new OrdersBackendResource();
        $resource->customerReference = static::CUSTOMER_REFERENCE;
        $resource->items = [];

        $capturedRequestTransfer = null;

        $facade = Stub::makeEmpty(OrderExperienceManagementFacadeInterface::class, [
            'createOrderFromIntake' => function (OrderIntakeRequestTransfer $orderIntakeRequestTransfer) use (&$capturedRequestTransfer): OrderIntakeResponseTransfer {
                $capturedRequestTransfer = $orderIntakeRequestTransfer;

                return (new OrderIntakeResponseTransfer())->setIsSuccessful(true);
            },
        ]);

        $processor = $this->createProcessor($facade);

        // Act
        $processor->process($resource, new Post(class: OrdersBackendResource::class), [], ['request' => new Request()]);

        // Assert
        $this->assertNotNull($capturedRequestTransfer);
        $this->assertSame([], $capturedRequestTransfer->getCartCodes());
    }

    /**
     * Named `orderLocaleName` on the request transfer — deliberately distinct from the existing
     * `localeName` (resolved from the `Accept-Language` header, used only to translate error
     * messages): this one is the caller's own business data, persisted onto the order and later
     * read by `OrderIntakeWriter::applyOrderLocale()`.
     */
    public function testGivenLocaleWhenProcessPostThenMapsItOntoOrderIntakeRequest(): void
    {
        // Arrange
        $resource = new OrdersBackendResource();
        $resource->customerReference = static::CUSTOMER_REFERENCE;
        $resource->items = [];
        $resource->locale = 'de_DE';

        $capturedRequestTransfer = null;

        $facade = Stub::makeEmpty(OrderExperienceManagementFacadeInterface::class, [
            'createOrderFromIntake' => function (OrderIntakeRequestTransfer $orderIntakeRequestTransfer) use (&$capturedRequestTransfer): OrderIntakeResponseTransfer {
                $capturedRequestTransfer = $orderIntakeRequestTransfer;

                return (new OrderIntakeResponseTransfer())->setIsSuccessful(true);
            },
        ]);

        $processor = $this->createProcessor($facade);

        // Act
        $processor->process($resource, new Post(class: OrdersBackendResource::class), [], ['request' => new Request()]);

        // Assert
        $this->assertNotNull($capturedRequestTransfer);
        $this->assertSame('de_DE', $capturedRequestTransfer->getOrderLocaleName());
    }

    public function testGivenNoShipmentOrAddressesWhenProcessPostThenMapsThemAsNull(): void
    {
        // Arrange
        $resource = new OrdersBackendResource();
        $resource->customerReference = static::CUSTOMER_REFERENCE;
        $resource->items = [];

        $capturedRequestTransfer = null;

        $facade = Stub::makeEmpty(OrderExperienceManagementFacadeInterface::class, [
            'createOrderFromIntake' => function (OrderIntakeRequestTransfer $orderIntakeRequestTransfer) use (&$capturedRequestTransfer): OrderIntakeResponseTransfer {
                $capturedRequestTransfer = $orderIntakeRequestTransfer;

                return (new OrderIntakeResponseTransfer())->setIsSuccessful(true);
            },
        ]);

        $processor = $this->createProcessor($facade);

        // Act
        $processor->process($resource, new Post(class: OrdersBackendResource::class), [], ['request' => new Request()]);

        // Assert
        $this->assertNotNull($capturedRequestTransfer);
        $this->assertNull($capturedRequestTransfer->getShipmentMethodName());
        $this->assertNull($capturedRequestTransfer->getBillingAddress());
        $this->assertNull($capturedRequestTransfer->getShippingAddress());
        $this->assertNull($capturedRequestTransfer->getCustomer()?->getCompanyUserTransfer());
    }

    /**
     * Checkout errors are glossary keys, translated downstream from the locale this processor puts
     * on the request. The negotiation itself belongs to
     * {@see \Spryker\ApiPlatform\EventSubscriber\BackendAcceptLanguageLocaleSubscriber}, which runs
     * once per request and is tested with the header table of its own; all this processor has to do
     * is pass on what the subscriber resolved.
     */
    public function testProcessPostPassesOnTheLocaleResolvedForTheRequest(): void
    {
        // Arrange
        $resource = new OrdersBackendResource();
        $resource->customerReference = static::CUSTOMER_REFERENCE;
        $resource->items = [];

        $capturedRequestTransfer = null;

        $facade = Stub::makeEmpty(OrderExperienceManagementFacadeInterface::class, [
            'createOrderFromIntake' => function (OrderIntakeRequestTransfer $orderIntakeRequestTransfer) use (&$capturedRequestTransfer): OrderIntakeResponseTransfer {
                $capturedRequestTransfer = $orderIntakeRequestTransfer;

                return (new OrderIntakeResponseTransfer())->setIsSuccessful(true);
            },
        ]);

        $processor = $this->createProcessor($facade);

        // Act
        $processor->process(
            $resource,
            new Post(class: OrdersBackendResource::class),
            [],
            ['request' => $this->createRequestWithResolvedLocale(static::LOCALE_NAME_DE_DE)],
        );

        // Assert
        $this->assertNotNull($capturedRequestTransfer);
        $this->assertSame(static::LOCALE_NAME_DE_DE, $capturedRequestTransfer->getLocaleName());
    }

    /**
     * The subscriber only runs on an HTTP request. Reached any other way — a processor invoked
     * directly, or a request that never passed through the kernel — there is nothing to read, so the
     * processor forwards no locale at all and `OrderIntakeMessageTranslator` applies the configured fallback. One
     * owner of the fallback, so the two cannot disagree.
     */
    public function testProcessPostForwardsNoLocaleWhenNoneWasResolvedForTheRequest(): void
    {
        // Arrange
        $resource = new OrdersBackendResource();
        $resource->customerReference = static::CUSTOMER_REFERENCE;
        $resource->items = [];

        $capturedRequestTransfer = null;

        $facade = Stub::makeEmpty(OrderExperienceManagementFacadeInterface::class, [
            'createOrderFromIntake' => function (OrderIntakeRequestTransfer $orderIntakeRequestTransfer) use (&$capturedRequestTransfer): OrderIntakeResponseTransfer {
                $capturedRequestTransfer = $orderIntakeRequestTransfer;

                return (new OrderIntakeResponseTransfer())->setIsSuccessful(true);
            },
        ]);

        $processor = $this->createProcessor($facade);

        // Act
        $processor->process($resource, new Post(class: OrdersBackendResource::class), [], ['request' => new Request()]);

        // Assert
        $this->assertNotNull($capturedRequestTransfer);
        $this->assertNull($capturedRequestTransfer->getLocaleName());
    }

    /**
     * The line items denormalize into the shared canonical `OrdersItem` class, so the processor reads
     * them through getters rather than as request-body maps. This pins that path, including the
     * per-line shipment override and its own shipping address.
     */
    public function testGivenTypedItemsWhenProcessPostThenMapsThemOntoOrderIntakeItems(): void
    {
        // Arrange
        $resource = new OrdersBackendResource();
        $resource->customerReference = static::CUSTOMER_REFERENCE;
        $resource->items = [
            (new OrdersItem())
                ->setSku('001_25904006')
                ->setQuantity(2)
                ->setUnitCustomPrice(25000)
                ->setCartNote('Deliver to gate 3')
                ->setMerchantReference('MER000001')
                ->setProductOfferReference('offer39')
                ->setShipment(
                    (new OrdersItemShipmentBackendObject())
                        ->setShipmentMethod('Express')
                        ->setRequestedDeliveryDate('2026-09-04')
                        ->setShippingAddress(
                            (new OrdersItemShipmentShippingAddressBackendObject())
                                ->setFirstName('Ada')
                                ->setCity('Berlin')
                                ->setZipCode('10115'),
                        ),
                ),
            (new OrdersItem())->setSku('002_25904006')->setQuantity(1),
        ];

        $capturedRequestTransfer = null;

        $facade = Stub::makeEmpty(OrderExperienceManagementFacadeInterface::class, [
            'createOrderFromIntake' => function (OrderIntakeRequestTransfer $orderIntakeRequestTransfer) use (&$capturedRequestTransfer): OrderIntakeResponseTransfer {
                $capturedRequestTransfer = $orderIntakeRequestTransfer;

                return (new OrderIntakeResponseTransfer())->setIsSuccessful(true);
            },
        ]);

        $processor = $this->createProcessor($facade);

        // Act
        $processor->process($resource, new Post(class: OrdersBackendResource::class), [], ['request' => new Request()]);

        // Assert
        $this->assertNotNull($capturedRequestTransfer);
        $this->assertCount(2, $capturedRequestTransfer->getItems());

        /** @var \Generated\Shared\Transfer\OrderIntakeItemTransfer $firstItemTransfer */
        $firstItemTransfer = $capturedRequestTransfer->getItems()->offsetGet(0);
        $this->assertInstanceOf(OrderIntakeItemTransfer::class, $firstItemTransfer);
        $this->assertSame('001_25904006', $firstItemTransfer->getSku());
        $this->assertSame(2, $firstItemTransfer->getQuantity());
        $this->assertSame(25000, $firstItemTransfer->getUnitCustomPrice());
        $this->assertSame('Deliver to gate 3', $firstItemTransfer->getCartNote());
        $this->assertSame('MER000001', $firstItemTransfer->getMerchantReference());
        $this->assertSame('offer39', $firstItemTransfer->getProductOfferReference());
        $this->assertSame('Express', $firstItemTransfer->getShipmentMethodName());
        $this->assertSame('2026-09-04', $firstItemTransfer->getRequestedDeliveryDate());
        $this->assertSame('Berlin', $firstItemTransfer->getShippingAddress()?->getCity());

        // A line without its own shipment falls back to the order-level values, so nothing is set here.
        /** @var \Generated\Shared\Transfer\OrderIntakeItemTransfer $secondItemTransfer */
        $secondItemTransfer = $capturedRequestTransfer->getItems()->offsetGet(1);
        $this->assertSame('002_25904006', $secondItemTransfer->getSku());
        $this->assertNull($secondItemTransfer->getShipmentMethodName());
        $this->assertNull($secondItemTransfer->getShippingAddress());
    }

    /**
     * The response side of the same contract: the resolved lines are echoed back as the canonical
     * class the resource's `items` property is typed to, so the response serializes against the
     * published component schema.
     */
    public function testGivenIntakeResponseWhenProcessPostThenEchoesResolvedItemsAsCanonicalObjects(): void
    {
        // Arrange
        $resource = new OrdersBackendResource();
        $resource->customerReference = static::CUSTOMER_REFERENCE;
        $resource->items = [(new OrdersItem())->setSku('001_25904006')->setQuantity(2)];

        $facade = Stub::makeEmpty(OrderExperienceManagementFacadeInterface::class, [
            'createOrderFromIntake' => fn (): OrderIntakeResponseTransfer => (new OrderIntakeResponseTransfer())
                ->setIsSuccessful(true)
                ->setOrderReference('DE--1234')
                ->addItem(
                    (new OrderIntakeItemTransfer())
                        ->setUuid('3fa85f64-5717-4562-b3fc-2c963f66afa6')
                        ->setSku('001_25904006')
                        ->setQuantity(2)
                        ->setMerchantReference('MER000001'),
                ),
        ]);

        $processor = $this->createProcessor($facade);

        // Act
        $result = $processor->process($resource, new Post(class: OrdersBackendResource::class), [], ['request' => new Request()]);

        // Assert
        $firstItem = $result->items[0] ?? null;
        $this->assertInstanceOf(OrdersItem::class, $firstItem);

        $this->assertContainsOnlyInstancesOf(OrdersItem::class, $result->items);
        $this->assertSame(1, $result->itemsCount);

        // The sales-order-item uuid, which is what every other endpoint addresses a line by. This
        // assertion previously expected the order item REFERENCE here, which is what let the POST
        // response disagree with `GET /orders/{ref}` and hand back a value the transitions endpoint
        // would not accept.
        $this->assertSame('3fa85f64-5717-4562-b3fc-2c963f66afa6', $firstItem->getUuid());
    }

    /**
     * `payments`, `createdAt`, `companyUuid` and `customer` only exist on the placed order — the
     * intake response itself carries none of them. `mapOrderIntakeResponseToResource()` re-reads the
     * order through the same `OrderResourceReader` GET uses specifically so the two responses do not
     * drift apart by omission; each field asserted here was, at some point, missing from that copy.
     */
    public function testGivenAPlacedOrderWhenProcessPostThenReportsTheSameFieldsAsGet(): void
    {
        // Arrange
        $resource = new OrdersBackendResource();
        $resource->customerReference = static::CUSTOMER_REFERENCE;
        $resource->items = [];

        $facade = Stub::makeEmpty(OrderExperienceManagementFacadeInterface::class, [
            'createOrderFromIntake' => fn (): OrderIntakeResponseTransfer => (new OrderIntakeResponseTransfer())
                ->setIsSuccessful(true)
                ->setOrderReference('DE--1234'),
        ]);

        $placedOrderTransfer = (new OrderTransfer())
            ->setOrderReference('DE--1234')
            ->setCustomerReference(static::CUSTOMER_REFERENCE)
            ->setCreatedAt('2026-08-27 11:04:52')
            ->setCompanyUuid('b7c3f1d8-2e94-4a0b-8f61-3c5d9e7a2b40')
            ->setEmail('ada@example.com')
            ->setLocale((new LocaleTransfer())->setLocaleName(static::LOCALE_NAME_DE_DE))
            ->addPayment(
                (new PaymentTransfer())
                    ->setPaymentProvider('DummyPayment')
                    ->setPaymentMethod('dummyPaymentInvoice')
                    ->setAmount(3489),
            );

        $salesFacadeMock = Stub::makeEmpty(SalesFacadeInterface::class, [
            'getOrderCollection' => fn (): OrderCollectionTransfer => (new OrderCollectionTransfer())->addOrder($placedOrderTransfer),
            'getOrderItems' => fn (): ItemCollectionTransfer => new ItemCollectionTransfer(),
        ]);

        $processor = new OrdersBackendProcessor(
            $facade,
            new OrdersBackendExceptionFactory(),
            $this->createOrderResourceReader(
                $facade,
                $salesFacadeMock,
                Stub::makeEmpty(OrderCommentReaderInterface::class),
            ),
            $this->tester->createOrderIntakeRequestMapper(),
            $this->tester->createOrderIntakeResponseMapper(),
        );

        // Act
        $result = $processor->process($resource, new Post(class: OrdersBackendResource::class), [], ['request' => new Request()]);

        // Assert
        $this->assertCount(1, $result->payments);
        $this->assertSame('DummyPayment', $result->payments[0]->getPaymentProvider());
        $this->assertSame(3489, $result->payments[0]->getAmount());
        $this->assertSame('2026-08-27 11:04:52', $result->createdAt);
        $this->assertSame('b7c3f1d8-2e94-4a0b-8f61-3c5d9e7a2b40', $result->companyUuid);
        $this->assertNotNull($result->customer);
        $this->assertSame('ada@example.com', $result->customer->getEmail());
        $this->assertSame('de_DE', $result->locale);
    }

    /**
     * The POST response is assembled from the SUBMITTED resource, not from the re-read one, so a
     * property another module contributed used to need an explicit copy line here — which is how
     * PurchasingControl's `budget` ended up named inside this module. Running the same expander
     * stack over the response instead is what removes that coupling, and this test is what keeps it
     * removed: a plugin's contribution has to reach the POST response without this class knowing
     * what the property is.
     */
    public function testGivenARegisteredExpanderPluginWhenProcessPostThenItsContributionIsOnTheResponse(): void
    {
        // Arrange
        $resource = new OrdersBackendResource();
        $resource->customerReference = static::CUSTOMER_REFERENCE;
        $resource->items = [];

        $facade = Stub::makeEmpty(OrderExperienceManagementFacadeInterface::class, [
            'createOrderFromIntake' => fn (): OrderIntakeResponseTransfer => (new OrderIntakeResponseTransfer())
                ->setIsSuccessful(true)
                ->setOrderReference(static::ORDER_REFERENCE),
        ]);

        $placedOrderTransfer = (new OrderTransfer())
            ->setOrderReference(static::ORDER_REFERENCE)
            ->setCustomerReference(static::CUSTOMER_REFERENCE);

        $processor = new OrdersBackendProcessor(
            $facade,
            new OrdersBackendExceptionFactory(),
            $this->createOrderResourceReader(
                $facade,
                Stub::makeEmpty(SalesFacadeInterface::class, [
                    'getOrderCollection' => fn (): OrderCollectionTransfer => (new OrderCollectionTransfer())->addOrder($placedOrderTransfer),
                    'getOrderItems' => fn (): ItemCollectionTransfer => new ItemCollectionTransfer(),
                ]),
                Stub::makeEmpty(OrderCommentReaderInterface::class),
                [$this->createOrderResourceExpanderPlugin()],
            ),
            $this->tester->createOrderIntakeRequestMapper(),
            $this->tester->createOrderIntakeResponseMapper(),
        );

        // Act
        $result = $processor->process($resource, new Post(class: OrdersBackendResource::class), [], ['request' => new Request()]);

        // Assert
        $this->assertSame(sprintf('expanded:%s', static::ORDER_REFERENCE), $result->orderCustomReference);
    }

    /**
     * The thin fallback answers when the placed order cannot be re-read, and there is no order to
     * derive a contribution from — reporting one anyway would mean reporting something no order
     * says.
     */
    public function testGivenAnUnreadablePlacedOrderWhenProcessPostThenNoExpanderContributionIsReported(): void
    {
        // Arrange
        $resource = new OrdersBackendResource();
        $resource->customerReference = static::CUSTOMER_REFERENCE;
        $resource->items = [];

        $facade = Stub::makeEmpty(OrderExperienceManagementFacadeInterface::class, [
            'createOrderFromIntake' => fn (): OrderIntakeResponseTransfer => (new OrderIntakeResponseTransfer())
                ->setIsSuccessful(true)
                ->setOrderReference(static::ORDER_REFERENCE),
        ]);

        $processor = new OrdersBackendProcessor(
            $facade,
            new OrdersBackendExceptionFactory(),
            $this->createOrderResourceReader(
                $facade,
                Stub::makeEmpty(SalesFacadeInterface::class, [
                    'getOrderCollection' => fn (): OrderCollectionTransfer => new OrderCollectionTransfer(),
                    'getOrderItems' => fn (): ItemCollectionTransfer => new ItemCollectionTransfer(),
                ]),
                Stub::makeEmpty(OrderCommentReaderInterface::class),
                [$this->createOrderResourceExpanderPlugin()],
            ),
            $this->tester->createOrderIntakeRequestMapper(),
            $this->tester->createOrderIntakeResponseMapper(),
        );

        // Act
        $result = $processor->process($resource, new Post(class: OrdersBackendResource::class), [], ['request' => new Request()]);

        // Assert
        $this->assertNull($result->orderCustomReference);
    }

    /**
     * Only the MESSAGES of the rejected response reach the caller — the `field` each issue carries is
     * dropped by the flattening. That is fine for a message that names its own path ("… is
     * required."), but a lookup failure names a value, not a place: "Product with SKU X was not
     * found." on a fifty-line order leaves the caller matching SKUs by hand. Prefix those with the
     * field, in the `field => message` shape the Symfony validator's own errors already use here.
     */
    public function testProcessPostPrefixesTheFieldOnAnIssueWhoseMessageDoesNotNameIt(): void
    {
        // Arrange
        $facade = Stub::makeEmpty(OrderExperienceManagementFacadeInterface::class, [
            'createOrderFromIntake' => fn (): OrderIntakeResponseTransfer => (new OrderIntakeResponseTransfer())
                ->setIsSuccessful(false)
                ->setProcessingResult('rejected')
                ->addValidationIssue(
                    (new OrderIntakeValidationIssueTransfer())
                        ->setField('items[1].sku')
                        ->setMessage('Product with SKU "nope" was not found.'),
                )
                ->addValidationIssue(
                    (new OrderIntakeValidationIssueTransfer())
                        ->setField('shipment.shippingAddress')
                        ->setMessage('"shipment.shippingAddress" is required.'),
                ),
        ]);

        // Act
        try {
            $this->createProcessor($facade)->process(
                new OrdersBackendResource(),
                new Post(class: OrdersBackendResource::class),
                [],
                ['request' => new Request()],
            );
            $this->fail('Expected an HttpException to be thrown.');
        } catch (HttpException $httpException) {
            // Assert — the first is prefixed, the second is left alone because it already names its
            // field. The exception's own message carries only the first issue; every issue (this one
            // included) is also reported via `errors[]`, which is where a multi-issue rejection is
            // meant to be read from.
            $this->assertSame('items[1].sku => Product with SKU "nope" was not found.', $httpException->getMessage());

            $errors = $httpException instanceof GlueApiException ? $httpException->getErrors() : [];
            $this->assertCount(2, $errors);
            $this->assertSame('items[1].sku => Product with SKU "nope" was not found.', $errors[0]['detail']);
            $this->assertSame('"shipment.shippingAddress" is required.', $errors[1]['detail']);
        }
    }

    /**
     * Stands in for {@see \Spryker\ApiPlatform\EventSubscriber\BackendAcceptLanguageLocaleSubscriber},
     * which sets this attribute on every Backend API request before any processor runs.
     */
    protected function createRequestWithResolvedLocale(string $localeName): Request
    {
        $request = new Request();
        $request->attributes->set('LocaleTransfer', (new LocaleTransfer())->setLocaleName($localeName));

        return $request;
    }

    /**
     * The real exception factory is used rather than a stub: `processPost()` throws its
     * return value directly, so a stubbed factory returning null would fatal instead of raising the
     * `HttpException` these tests assert on.
     *
     * `getOrderCollection()`/`getOrderItems()` are stubbed to real (empty) collection transfers
     * rather than left to `Stub::makeEmpty()`'s default: a POST response naming an `orderReference`
     * makes the processor re-read the placed order via `OrderResourceReader::findOrderByReference()`,
     * which iterates `$orderCollectionTransfer->getOrders()` — a bare `Stub::makeEmpty()` return
     * leaves that ArrayObject property uninitialised (the class is never constructed), which crashes
     * the `foreach` rather than simply yielding zero orders, so the response falls into the thin
     * fallback these tests do not otherwise exercise.
     */
    protected function createProcessor(
        OrderExperienceManagementFacadeInterface $orderExperienceManagementFacade,
    ): OrdersBackendProcessor {
        return new OrdersBackendProcessor(
            $orderExperienceManagementFacade,
            new OrdersBackendExceptionFactory(),
            $this->createOrderResourceReader(
                $orderExperienceManagementFacade,
                Stub::makeEmpty(SalesFacadeInterface::class, [
                    'getOrderCollection' => fn (): OrderCollectionTransfer => new OrderCollectionTransfer(),
                    'getOrderItems' => fn (): ItemCollectionTransfer => new ItemCollectionTransfer(),
                ]),
                Stub::makeEmpty(OrderCommentReaderInterface::class),
            ),
            $this->tester->createOrderIntakeRequestMapper(),
            $this->tester->createOrderIntakeResponseMapper(),
        );
    }

    /**
     * Builds the same `OrderResourceReader` the container wires in production — `OrdersBackendProcessor`
     * takes it as a single constructor dependency now, so a test that needs a specific
     * `SalesFacadeInterface`/`OrderCommentReaderInterface` stub or expander plugin assembles it here
     * rather than passing them to the processor directly.
     *
     * @param array<\SprykerFeature\Glue\OrderExperienceManagement\Dependency\Plugin\OrderResourceExpanderPluginInterface> $orderResourceExpanderPlugins
     */
    protected function createOrderResourceReader(
        OrderExperienceManagementFacadeInterface $orderExperienceManagementFacade,
        SalesFacadeInterface $salesFacade,
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
}
