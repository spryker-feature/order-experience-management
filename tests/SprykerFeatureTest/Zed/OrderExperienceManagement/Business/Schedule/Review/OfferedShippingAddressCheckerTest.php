<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Schedule\Review;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\RecurringScheduleShippingAddressChoiceTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment\OfferedShippingAddressChecker;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Schedule
 * @group Review
 * @group OfferedShippingAddressCheckerTest
 * Add your own group annotations below this line
 */
class OfferedShippingAddressCheckerTest extends Unit
{
    protected const int ID_COMPANY_UNIT_ADDRESS = 13;

    protected const string COMPANY_UNIT_ADDRESS_KEY = 'company_unit_address:13';

    public function testTreatsTheSamePostalPlaceAsAlreadyOffered(): void
    {
        // Arrange — same place, different recipient and contact details.
        $offeredAddressTransfer = $this->createAddress('Kirncher Str.', '7', '10247', 'Berlin');
        $addressTransfer = $this->createAddress('Kirncher Str.', '7', '10247', 'Berlin')
            ->setFirstName('Sonia')
            ->setLastName('Wagner')
            ->setCompany('Acme Corporation')
            ->setPhone('4902890031');

        // Act
        $isAlreadyOffered = (new OfferedShippingAddressChecker())
            ->isAlreadyOffered($addressTransfer, $this->createChoices($offeredAddressTransfer));

        // Assert
        $this->assertTrue($isAlreadyOffered);
    }

    public function testIgnoresCaseAndSurroundingWhitespaceInThePostalFields(): void
    {
        // Arrange
        $offeredAddressTransfer = $this->createAddress('Kirncher Str.', '7', '10247', 'Berlin');
        $addressTransfer = $this->createAddress('  KIRNCHER STR. ', '7', '10247', ' berlin');

        // Act
        $isAlreadyOffered = (new OfferedShippingAddressChecker())
            ->isAlreadyOffered($addressTransfer, $this->createChoices($offeredAddressTransfer));

        // Assert
        $this->assertTrue($isAlreadyOffered);
    }

    /**
     * A stored address keeps the identifier of the business unit address it was taken from, which stays
     * conclusive even after that address was edited.
     */
    public function testTreatsAMatchingCompanyUnitAddressIdAsAlreadyOffered(): void
    {
        // Arrange
        $offeredAddressTransfer = $this->createAddress('Kirncher Str.', '7', '10247', 'Berlin')
            ->setIdCompanyUnitAddress(static::ID_COMPANY_UNIT_ADDRESS);
        $addressTransfer = $this->createAddress('Renamed Str.', '9', '99999', 'Hamburg')
            ->setIdCompanyUnitAddress(static::ID_COMPANY_UNIT_ADDRESS);

        // Act
        $isAlreadyOffered = (new OfferedShippingAddressChecker())
            ->isAlreadyOffered($addressTransfer, $this->createChoices($offeredAddressTransfer));

        // Assert
        $this->assertTrue($isAlreadyOffered);
    }

    public function testTreatsADifferentPlaceAsNotOffered(): void
    {
        // Arrange
        $offeredAddressTransfer = $this->createAddress('Kirncher Str.', '7', '10247', 'Berlin');
        $addressTransfer = $this->createAddress('Julie-Wolfthorn-Str.', '1', '10115', 'Berlin');

        // Act
        $isAlreadyOffered = (new OfferedShippingAddressChecker())
            ->isAlreadyOffered($addressTransfer, $this->createChoices($offeredAddressTransfer));

        // Assert
        $this->assertFalse($isAlreadyOffered);
    }

    public function testTreatsAnyAddressAsNotOfferedWhenNothingIsOfferedYet(): void
    {
        // Act
        $isAlreadyOffered = (new OfferedShippingAddressChecker())
            ->isAlreadyOffered($this->createAddress('Kirncher Str.', '7', '10247', 'Berlin'), []);

        // Assert
        $this->assertFalse($isAlreadyOffered);
    }

    public function testSkipsAChoiceThatCarriesNoAddress(): void
    {
        // Arrange
        $choiceTransfers = [static::COMPANY_UNIT_ADDRESS_KEY => new RecurringScheduleShippingAddressChoiceTransfer()];

        // Act
        $isAlreadyOffered = (new OfferedShippingAddressChecker())
            ->isAlreadyOffered($this->createAddress('Kirncher Str.', '7', '10247', 'Berlin'), $choiceTransfers);

        // Assert
        $this->assertFalse($isAlreadyOffered);
    }

    protected function createAddress(string $address1, string $address2, string $zipCode, string $city): AddressTransfer
    {
        return (new AddressTransfer())
            ->setAddress1($address1)
            ->setAddress2($address2)
            ->setZipCode($zipCode)
            ->setCity($city)
            ->setIso2Code('DE');
    }

    /**
     * @return array<string, \Generated\Shared\Transfer\RecurringScheduleShippingAddressChoiceTransfer>
     */
    protected function createChoices(AddressTransfer $addressTransfer): array
    {
        return [
            static::COMPANY_UNIT_ADDRESS_KEY => (new RecurringScheduleShippingAddressChoiceTransfer())->setAddress($addressTransfer),
        ];
    }
}
