<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Intake\Resolver;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AddressesTransfer;
use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\OrderIntakeItemTransfer;
use Generated\Shared\Transfer\OrderIntakeRequestTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Resolver\OrderIntakeAddressResolver;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Business
 * @group Intake
 * @group Resolver
 * @group OrderIntakeAddressResolverTest
 * Add your own group annotations below this line
 */
class OrderIntakeAddressResolverTest extends Unit
{
    protected const string ADDRESS_UUID = 'c0ffee00-1111-4222-8333-444455556666';

    protected const string OTHER_ADDRESS_UUID = 'deadbeef-1111-4222-8333-444455556666';

    protected const string CUSTOMER_REFERENCE = 'DE--21';

    protected const string STORED_CITY = 'Berlin';

    /**
     * The reason the field exists: an integration replaying the same weekly order re-sends the same
     * street, and without a reference every replay adds another address-book row.
     */
    public function testResolveAddressesReplacesAUuidWithTheStoredAddressAndMarksItSaved(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = (new OrderIntakeRequestTransfer())
            ->setCustomer($this->createCustomerWithAddressBook())
            ->setBillingAddress((new AddressTransfer())->setUuid(static::ADDRESS_UUID));
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $orderIntakeRequestTransfer = (new OrderIntakeAddressResolver())
            ->resolveAddresses($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);

        // Assert
        $billingAddressTransfer = $orderIntakeRequestTransfer->getBillingAddressOrFail();

        $this->assertSame(static::STORED_CITY, $billingAddressTransfer->getCity());
        $this->assertTrue($billingAddressTransfer->getIsAddressSavingSkipped());
        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
    }

    /**
     * The stored address is the customer's curated record. Merging caller fields into it would
     * produce an address matching neither what was sent nor what the customer has.
     */
    public function testResolveAddressesLetsTheStoredAddressWinOverFieldsSentBesideTheUuid(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = (new OrderIntakeRequestTransfer())
            ->setCustomer($this->createCustomerWithAddressBook())
            ->setBillingAddress(
                (new AddressTransfer())->setUuid(static::ADDRESS_UUID)->setCity('Hamburg'),
            );

        // Act
        $orderIntakeRequestTransfer = (new OrderIntakeAddressResolver())
            ->resolveAddresses($orderIntakeRequestTransfer, new OrderIntakeResponseTransfer());

        // Assert
        $this->assertSame(static::STORED_CITY, $orderIntakeRequestTransfer->getBillingAddressOrFail()->getCity());
    }

    /**
     * Ownership is guaranteed by construction — the candidates ARE the customer's addresses — so a
     * uuid belonging to somebody else simply does not match, and must be reported rather than
     * silently falling back to whatever else the caller sent.
     */
    public function testResolveAddressesReportsAUuidThatIsNotOneOfTheCustomersAddresses(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = (new OrderIntakeRequestTransfer())
            ->setCustomer($this->createCustomerWithAddressBook())
            ->setBillingAddress((new AddressTransfer())->setUuid(static::OTHER_ADDRESS_UUID));
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        (new OrderIntakeAddressResolver())->resolveAddresses($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);

        // Assert
        $validationIssueTransfer = $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0);

        $this->assertCount(1, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertSame('billingAddress.uuid', $validationIssueTransfer->getField());
        $this->assertSame(static::OTHER_ADDRESS_UUID, $validationIssueTransfer->getParameters()['%uuid%']);
    }

    public function testResolveAddressesLeavesAnInlineAddressUntouched(): void
    {
        // Arrange
        $inlineAddressTransfer = (new AddressTransfer())->setCity('Hamburg');
        $orderIntakeRequestTransfer = (new OrderIntakeRequestTransfer())
            ->setCustomer($this->createCustomerWithAddressBook())
            ->setBillingAddress($inlineAddressTransfer);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $orderIntakeRequestTransfer = (new OrderIntakeAddressResolver())
            ->resolveAddresses($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertSame($inlineAddressTransfer, $orderIntakeRequestTransfer->getBillingAddress());
        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
    }

    /**
     * A per-line delivery address can be a reference too, and the resolved instances must not be
     * shared: the shipment expander writes onto whatever it is handed.
     */
    public function testResolveAddressesResolvesPerItemShippingAddressesIndependently(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = (new OrderIntakeRequestTransfer())
            ->setCustomer($this->createCustomerWithAddressBook())
            ->addItem((new OrderIntakeItemTransfer())->setShippingAddress(
                (new AddressTransfer())->setUuid(static::ADDRESS_UUID),
            ))
            ->addItem((new OrderIntakeItemTransfer())->setShippingAddress(
                (new AddressTransfer())->setUuid(static::ADDRESS_UUID),
            ));

        // Act
        $orderIntakeRequestTransfer = (new OrderIntakeAddressResolver())
            ->resolveAddresses($orderIntakeRequestTransfer, new OrderIntakeResponseTransfer());

        // Assert
        $firstAddressTransfer = $orderIntakeRequestTransfer->getItems()->offsetGet(0)->getShippingAddressOrFail();
        $secondAddressTransfer = $orderIntakeRequestTransfer->getItems()->offsetGet(1)->getShippingAddressOrFail();

        $this->assertSame(static::STORED_CITY, $firstAddressTransfer->getCity());
        $this->assertSame(static::STORED_CITY, $secondAddressTransfer->getCity());
        $this->assertNotSame($firstAddressTransfer, $secondAddressTransfer);
    }

    public function testResolveAddressesNamesTheOffendingLineByIndex(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = (new OrderIntakeRequestTransfer())
            ->setCustomer($this->createCustomerWithAddressBook())
            ->addItem(new OrderIntakeItemTransfer())
            ->addItem((new OrderIntakeItemTransfer())->setShippingAddress(
                (new AddressTransfer())->setUuid(static::OTHER_ADDRESS_UUID),
            ));
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        (new OrderIntakeAddressResolver())->resolveAddresses($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertSame(
            'items[1].shipment.shippingAddress.uuid',
            $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0)->getField(),
        );
    }

    protected function createCustomerWithAddressBook(): CustomerTransfer
    {
        return (new CustomerTransfer())
            ->setCustomerReference(static::CUSTOMER_REFERENCE)
            ->setAddresses(
                (new AddressesTransfer())->addAddress(
                    (new AddressTransfer())->setUuid(static::ADDRESS_UUID)->setCity(static::STORED_CITY),
                ),
            );
    }
}
