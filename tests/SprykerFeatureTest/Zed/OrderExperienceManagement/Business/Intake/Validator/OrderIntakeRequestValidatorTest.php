<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Intake\Validator;

use ArrayObject;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\OrderIntakeItemTransfer;
use Generated\Shared\Transfer\OrderIntakeRequestTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\OrderIntakeSalesUnitTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Validator\OrderIntakeRequestValidator;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Business
 * @group Intake
 * @group Validator
 * @group OrderIntakeRequestValidatorTest
 * Add your own group annotations below this line
 */
class OrderIntakeRequestValidatorTest extends Unit
{
    protected const string SKU = '131_24872891';

    public function testValidateAcceptsAMinimalPayload(): void
    {
        // Arrange
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createValidator()->validate($this->createValidRequest(), $orderIntakeResponseTransfer);

        // Assert
        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
    }

    /**
     * Each of these used to answer 500. `unitCustomPrice` is deliberately absent — it is optional now, and
     * an omitted one is resolved from the catalogue by OrderIntakePriceResolver. Only the fields
     * `orders.validation.yml` cannot reach are here; store, currency and the billing address are declared there and covered by the Symfony
     * validator before a request gets this far.
     *
     * @dataProvider provideMissingRequiredFields
     */
    public function testValidateReportsEveryMissingRequiredField(callable $break, string $expectedField): void
    {
        // Arrange
        $orderIntakeRequestTransfer = $this->createValidRequest();
        $break($orderIntakeRequestTransfer);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createValidator()->validate($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertContains($expectedField, $this->extractFields($orderIntakeResponseTransfer));
    }

    /**
     * @return iterable<string, array{callable, string}>
     */
    public function provideMissingRequiredFields(): iterable
    {
        yield 'items[0].sku' => [
            fn (OrderIntakeRequestTransfer $r) => $r->getItems()->offsetGet(0)->setSku(null),
            'items[0].sku',
        ];

        yield 'items[0].quantity' => [
            fn (OrderIntakeRequestTransfer $r) => $r->getItems()->offsetGet(0)->setQuantity(null),
            'items[0].quantity',
        ];

        yield 'shipment.shipmentMethod (null)' => [
            fn (OrderIntakeRequestTransfer $r) => $r->setShipmentMethodName(null),
            'shipment.shipmentMethod',
        ];

        yield 'shipment.shipmentMethod (blank)' => [
            fn (OrderIntakeRequestTransfer $r) => $r->setShipmentMethodName('   '),
            'shipment.shipmentMethod',
        ];

        yield 'shipment.shippingAddress' => [
            fn (OrderIntakeRequestTransfer $r) => $r->setShippingAddress(null),
            'shipment.shippingAddress',
        ];
    }

    /**
     * An empty string satisfies the NOT NULL column underneath, so without this an order could be
     * placed with a blank city — a 201 that produces an undeliverable order.
     */
    public function testValidateTreatsABlankValueAsMissing(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = $this->createValidRequest()
            ->setShippingAddress($this->createCompleteAddress()->setCity('   '));
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createValidator()->validate($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertSame(['shipment.shippingAddress.city'], $this->extractFields($orderIntakeResponseTransfer));
    }

    /**
     * A per-line `shipment` is optional as a whole, but every field it DOES carry is judged by the
     * same rules as the order-level one — including the line index in the field path, so a fifty-line
     * order says which line is wrong.
     *
     * A blank `shipmentMethod` is the case worth pinning: `null` means "inherit the order-level
     * method", but `''` overrides it with nothing, and the resolution downstream cannot tell the
     * difference between the two once it has fallen back.
     *
     * @dataProvider providePerLineShipmentMethods
     *
     * @param array<int, string|null> $shipmentMethodNamesByLine
     * @param array<int, string> $expectedFields
     */
    public function testValidateAppliesTheOrderLevelShipmentMethodRuleToEveryProvidedLine(
        array $shipmentMethodNamesByLine,
        array $expectedFields
    ): void {
        // Arrange
        $orderIntakeRequestTransfer = $this->createValidRequest();
        $orderIntakeRequestTransfer->setItems(new ArrayObject());

        foreach ($shipmentMethodNamesByLine as $shipmentMethodName) {
            $orderIntakeRequestTransfer->addItem(
                (new OrderIntakeItemTransfer())
                    ->setSku(static::SKU)
                    ->setQuantity(1)
                    ->setShipmentMethodName($shipmentMethodName),
            );
        }

        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createValidator()->validate($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertSame($expectedFields, $this->extractFields($orderIntakeResponseTransfer));
    }

    /**
     * @return iterable<string, array{array<int, string|null>, array<int, string>}>
     */
    public function providePerLineShipmentMethods(): iterable
    {
        yield 'null inherits the order-level method' => [[null], []];

        yield 'a named method overrides it' => [['Express'], []];

        yield 'an empty string overrides it with nothing' => [[''], ['items[0].shipment.shipmentMethod']];

        yield 'whitespace reads as empty' => [['   '], ['items[0].shipment.shipmentMethod']];

        yield 'the second line names the second line' => [
            ['Express', ''],
            ['items[1].shipment.shipmentMethod'],
        ];

        yield 'every broken line is reported' => [
            ['', 'Express', '  '],
            ['items[0].shipment.shipmentMethod', 'items[2].shipment.shipmentMethod'],
        ];
    }

    public function testValidateDoesNotRequireAnOrderLevelShipmentMethodWhenEveryItemNamesItsOwn(): void
    {
        // Arrange
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();
        $orderIntakeRequestTransfer = $this->createValidRequest()->setShipmentMethodName(null);
        $orderIntakeRequestTransfer->getItems()->offsetGet(0)->setShipmentMethodName('Express');

        // Act
        $this->createValidator()->validate($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
    }

    public function testValidateStillRequiresTheOrderLevelShipmentMethodWhenOnlySomeItemsNameTheirOwn(): void
    {
        // Arrange
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();
        $orderIntakeRequestTransfer = $this->createValidRequest()
            ->setShipmentMethodName(null)
            ->addItem((new OrderIntakeItemTransfer())->setSku(static::SKU)->setQuantity(1));
        $orderIntakeRequestTransfer->getItems()->offsetGet(0)->setShipmentMethodName('Express');

        // Act
        $this->createValidator()->validate($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertContains('shipment.shipmentMethod', $this->extractFields($orderIntakeResponseTransfer));
    }

    /**
     * A per-line override address has the same rule as the order-level one, and names its line.
     */
    public function testValidateReportsAnIncompletePerLineShippingAddress(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = $this->createValidRequest();
        $orderIntakeRequestTransfer->getItems()->offsetGet(0)
            ->setShippingAddress((new AddressTransfer())->setFirstName('Ada'));
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createValidator()->validate($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertContains('items[0].shipment.shippingAddress.city', $this->extractFields($orderIntakeResponseTransfer));
    }

    /**
     * @dataProvider provideOutOfRangeItemValues
     */
    public function testValidateReportsOutOfRangeItemValues(callable $break, string $expectedField): void
    {
        // Arrange
        $orderIntakeRequestTransfer = $this->createValidRequest();
        $break($orderIntakeRequestTransfer);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createValidator()->validate($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertContains($expectedField, $this->extractFields($orderIntakeResponseTransfer));
    }

    /**
     * The deliberate divergence from the storefront cart API, where `quantity` is NotBlank
     * unconditionally: here a line naming a sales-unit amount has its base quantity derived from
     * that amount, so requiring it too would force the caller to do the conversion anyway.
     *
     * @see \SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Expander\OrderIntakeSalesUnitExpander
     */
    public function testValidateAcceptsAnOmittedQuantityWhenTheLineNamesASalesUnitAmount(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = $this->createValidRequest();
        $orderIntakeRequestTransfer->getItems()->offsetGet(0)
            ->setQuantity(null)
            ->setSalesUnit((new OrderIntakeSalesUnitTransfer())->setCode('METR')->setAmount(2.0));
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createValidator()->validate($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertNotContains('items[0].quantity', $this->extractFields($orderIntakeResponseTransfer));
    }

    /**
     * A sales unit WITHOUT an amount does not derive anything, so the quantity is still required.
     */
    public function testValidateStillRequiresQuantityWhenTheSalesUnitCarriesNoAmount(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = $this->createValidRequest();
        $orderIntakeRequestTransfer->getItems()->offsetGet(0)
            ->setQuantity(null)
            ->setSalesUnit((new OrderIntakeSalesUnitTransfer())->setCode('METR'));
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createValidator()->validate($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertContains('items[0].quantity', $this->extractFields($orderIntakeResponseTransfer));
    }

    public function testValidateReportsASalesUnitWithoutACode(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = $this->createValidRequest();
        $orderIntakeRequestTransfer->getItems()->offsetGet(0)
            ->setSalesUnit((new OrderIntakeSalesUnitTransfer())->setAmount(2.0));
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createValidator()->validate($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertContains('items[0].salesUnit.code', $this->extractFields($orderIntakeResponseTransfer));
    }

    public function testValidateReportsANonPositiveSalesUnitAmount(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = $this->createValidRequest();
        $orderIntakeRequestTransfer->getItems()->offsetGet(0)
            ->setSalesUnit((new OrderIntakeSalesUnitTransfer())->setCode('METR')->setAmount(0.0));
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createValidator()->validate($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertContains('items[0].salesUnit.amount', $this->extractFields($orderIntakeResponseTransfer));
    }

    /**
     * @return iterable<string, array{callable, string}>
     */
    public function provideOutOfRangeItemValues(): iterable
    {
        yield 'zero quantity' => [
            fn (OrderIntakeRequestTransfer $r) => $r->getItems()->offsetGet(0)->setQuantity(0),
            'items[0].quantity',
        ];

        yield 'negative quantity' => [
            fn (OrderIntakeRequestTransfer $r) => $r->getItems()->offsetGet(0)->setQuantity(-1),
            'items[0].quantity',
        ];

        yield 'negative unit price' => [
            fn (OrderIntakeRequestTransfer $r) => $r->getItems()->offsetGet(0)->setUnitCustomPrice(-100),
            'items[0].unitCustomPrice',
        ];
    }

    /**
     * The index is the point: an order can carry fifty lines and "sku is required" on its own does
     * not say which one is broken.
     */
    public function testValidateNamesTheOffendingLineByIndex(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = $this->createValidRequest()
            ->addItem((new OrderIntakeItemTransfer())->setQuantity(1)->setUnitCustomPrice(7000));
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createValidator()->validate($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertSame(['items[1].sku'], $this->extractFields($orderIntakeResponseTransfer));
    }

    /**
     * The shipping address is required unless every item already names its own delivery address (see
     * testValidateDoesNotRequireAnOrderLevelShippingAddressWhenEveryItemNamesItsOwn). It used to be
     * optional and fall back to a copy of the billing address; a delivery address the caller never
     * stated is a guess the backend has no business making, so an order with at least one addressless
     * item and no order-level address is a 422 naming the field. A client that wants the "same as
     * billing" convenience copies the billing address into the payload itself.
     */
    public function testValidateReportsAnAbsentShippingAddress(): void
    {
        // Arrange
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();
        $orderIntakeRequestTransfer = $this->createValidRequest()->setShippingAddress(null);

        // Act
        $this->createValidator()->validate($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);

        // Assert — one issue naming the whole field, not five naming each of its parts.
        $this->assertSame(['shipment.shippingAddress'], $this->extractFields($orderIntakeResponseTransfer));
    }

    public function testValidateDoesNotRequireAnOrderLevelShippingAddressWhenEveryItemNamesItsOwn(): void
    {
        // Arrange
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();
        $orderIntakeRequestTransfer = $this->createValidRequest()->setShippingAddress(null);
        $orderIntakeRequestTransfer->getItems()->offsetGet(0)->setShippingAddress($this->createCompleteAddress());

        // Act
        $this->createValidator()->validate($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
    }

    public function testValidateStillRequiresTheOrderLevelShippingAddressWhenOnlySomeItemsNameTheirOwn(): void
    {
        // Arrange
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();
        $orderIntakeRequestTransfer = $this->createValidRequest()
            ->setShippingAddress(null)
            ->addItem((new OrderIntakeItemTransfer())->setSku(static::SKU)->setQuantity(1));
        $orderIntakeRequestTransfer->getItems()->offsetGet(0)->setShippingAddress($this->createCompleteAddress());

        // Act
        $this->createValidator()->validate($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertContains('shipment.shippingAddress', $this->extractFields($orderIntakeResponseTransfer));
    }

    /**
     * A supplied address still has to be complete, and the issue names the payload path the caller
     * actually wrote — `shipment.shippingAddress`, not a bare `shippingAddress` that appears nowhere
     * in the request body.
     */
    public function testValidateReportsAnIncompleteShippingAddress(): void
    {
        // Arrange
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();
        $orderIntakeRequestTransfer = $this->createValidRequest()
            ->setShippingAddress((new AddressTransfer())->setFirstName('Ada'));

        // Act
        $this->createValidator()->validate($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);

        // Assert
        $fields = $this->extractFields($orderIntakeResponseTransfer);

        $this->assertContains('shipment.shippingAddress.city', $fields);
        $this->assertNotContains('shipment.shippingAddress.firstName', $fields);
    }

    /**
     * Walks the address field by field, the way a caller discovering the contract does. Exactly five
     * fields satisfy the requirement; supplying one of them leaves the other four reported, and
     * supplying any of the six OPTIONAL ones satisfies nothing — an address carrying only a
     * salutation is as incomplete as an empty one.
     *
     * The live equivalent of this matrix (39 payloads against POST /orders, including wrong-typed
     * and null addresses) returns 422 for every case and 500 for none.
     *
     * @dataProvider provideSingleAddressFields
     *
     * @param array<string, string> $address
     * @param array<int, string> $expectedMissing
     */
    public function testValidateReportsEveryStillMissingRequiredAddressField(array $address, array $expectedMissing): void
    {
        // Arrange
        $orderIntakeRequestTransfer = $this->createValidRequest()
            ->setShippingAddress((new AddressTransfer())->fromArray($address, true));
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createValidator()->validate($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertSame($expectedMissing, $this->extractFields($orderIntakeResponseTransfer));
    }

    /**
     * @return iterable<string, array{array<string, string>, array<int, string>}>
     */
    public function provideSingleAddressFields(): iterable
    {
        $required = ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'zipCode' => '10115', 'city' => 'Berlin', 'iso2Code' => 'DE'];
        $optional = ['salutation' => 'Ms', 'company' => 'Analytical Engines', 'address1' => 'Julie-Wolfthorn-Strasse', 'address2' => '1', 'address3' => 'c/o Babbage', 'phone' => '+49301234567'];

        $allMissing = array_map(
            static fn (string $field): string => 'shipment.shippingAddress.' . $field,
            array_keys($required),
        );

        yield 'no fields at all' => [[], $allMissing];

        foreach ($required as $field => $value) {
            yield sprintf('only %s (required)', $field) => [
                [$field => $value],
                array_values(array_diff($allMissing, ['shipment.shippingAddress.' . $field])),
            ];
        }

        foreach ($optional as $field => $value) {
            yield sprintf('only %s (optional)', $field) => [[$field => $value], $allMissing];
        }

        // Adding the five required ones one at a time shortens the report by exactly one each time.
        $accumulated = [];

        foreach ($required as $field => $value) {
            $accumulated[$field] = $value;

            yield sprintf('cumulative through %s', $field) => [
                $accumulated,
                array_values(array_diff($allMissing, array_map(
                    static fn (string $f): string => 'shipment.shippingAddress.' . $f,
                    array_keys($accumulated),
                ))),
            ];
        }
    }

    /**
     * A `uuid` names one of the customer's saved addresses, so it satisfies the requirement on its
     * own — OrderIntakeAddressResolver replaces it wholesale or rejects the uuid.
     */
    public function testValidateAcceptsAShippingAddressThatOnlyNamesAUuid(): void
    {
        // Arrange
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();
        $orderIntakeRequestTransfer = $this->createValidRequest()
            ->setShippingAddress((new AddressTransfer())->setUuid('3fa85f64-5717-4562-b3fc-2c963f66afa6'));

        // Act
        $this->createValidator()->validate($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
    }

    /**
     * Reporting one problem per call would make a broken payload a guessing game.
     */
    public function testValidateReportsEveryProblemInOnePass(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = (new OrderIntakeRequestTransfer())
            ->setShippingAddress((new AddressTransfer())->setFirstName('Ada'))
            ->addItem(new OrderIntakeItemTransfer());
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createValidator()->validate($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertSame(
            [
                'shipment.shippingAddress.lastName',
                'shipment.shippingAddress.zipCode',
                'shipment.shippingAddress.city',
                'shipment.shippingAddress.iso2Code',
                'shipment.shipmentMethod',
                'items[0].sku',
                'items[0].quantity',
            ],
            $this->extractFields($orderIntakeResponseTransfer),
        );
    }

    /**
     * @return array<int, string>
     */
    protected function extractFields(OrderIntakeResponseTransfer $orderIntakeResponseTransfer): array
    {
        $fields = [];

        foreach ($orderIntakeResponseTransfer->getValidationIssues() as $validationIssueTransfer) {
            $fields[] = (string)$validationIssueTransfer->getField();
        }

        return $fields;
    }

    protected function createCompleteAddress(): AddressTransfer
    {
        return (new AddressTransfer())
            ->setFirstName('Ada')
            ->setLastName('Lovelace')
            ->setZipCode('10115')
            ->setCity('Berlin')
            ->setIso2Code('DE');
    }

    protected function createValidRequest(): OrderIntakeRequestTransfer
    {
        return (new OrderIntakeRequestTransfer())
            ->setStoreName('DE')
            ->setCurrencyCode('EUR')
            ->setShipmentMethodName('Standard')
            ->setBillingAddress($this->createCompleteAddress())
            ->setShippingAddress($this->createCompleteAddress())
            ->addItem(
                (new OrderIntakeItemTransfer())
                    ->setSku(static::SKU)
                    ->setQuantity(1)
                    ->setUnitCustomPrice(7000),
            );
    }

    protected function createValidator(): OrderIntakeRequestValidator
    {
        return new OrderIntakeRequestValidator();
    }
}
