<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Schedule\Review;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AddressTransfer;
use Spryker\Service\Customer\CustomerServiceInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment\ShippingAddressChoiceKeyGenerator;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Schedule
 * @group Review
 * @group ShippingAddressChoiceKeyGeneratorTest
 * Add your own group annotations below this line
 */
class ShippingAddressChoiceKeyGeneratorTest extends Unit
{
    protected const int ID_COMPANY_UNIT_ADDRESS = 13;

    protected const string UNIQUE_ADDRESS_KEY = 'e6f0c2b1a4d3';

    protected const string ADDRESS1 = 'Julie-Wolfthorn-Str.';

    /**
     * This class is the only place that composes a choice key, so the literal shape is asserted here on purpose.
     */
    public function testGeneratesTheCompanyUnitAddressKeyFromTheSourceAndTheId(): void
    {
        // Act
        $key = $this->createGenerator()->generateCompanyUnitAddressKey(static::ID_COMPANY_UNIT_ADDRESS);

        // Assert
        $this->assertSame('company_unit_address:13', $key);
    }

    public function testGeneratesTheScheduleAddressKeyFromTheSourceAndTheUniqueAddressKey(): void
    {
        // Act
        $key = $this->createGenerator()->generateScheduleAddressKey(new AddressTransfer());

        // Assert
        $this->assertSame('schedule:' . static::UNIQUE_ADDRESS_KEY, $key);
    }

    /**
     * The unique address key is content-derived, so the address must reach the service untouched.
     */
    public function testPassesTheAddressToTheCustomerServiceUnchanged(): void
    {
        // Arrange
        $addressTransfer = (new AddressTransfer())->setAddress1(static::ADDRESS1);

        $customerServiceMock = $this->createMock(CustomerServiceInterface::class);
        $customerServiceMock
            ->expects($this->once())
            ->method('getUniqueAddressKey')
            ->with($addressTransfer)
            ->willReturn(static::UNIQUE_ADDRESS_KEY);

        // Act
        $key = (new ShippingAddressChoiceKeyGenerator($customerServiceMock))->generateScheduleAddressKey($addressTransfer);

        // Assert
        $this->assertSame('schedule:' . static::UNIQUE_ADDRESS_KEY, $key);
    }

    /**
     * The company unit address key is composed locally, so no content hash is needed for it.
     */
    public function testDoesNotAskTheCustomerServiceForACompanyUnitAddressKey(): void
    {
        // Arrange
        $customerServiceMock = $this->createMock(CustomerServiceInterface::class);
        $customerServiceMock->expects($this->never())->method('getUniqueAddressKey');

        // Act
        $key = (new ShippingAddressChoiceKeyGenerator($customerServiceMock))
            ->generateCompanyUnitAddressKey(static::ID_COMPANY_UNIT_ADDRESS);

        // Assert
        $this->assertSame('company_unit_address:13', $key);
    }

    protected function createGenerator(): ShippingAddressChoiceKeyGenerator
    {
        $customerServiceMock = $this->createMock(CustomerServiceInterface::class);
        $customerServiceMock->method('getUniqueAddressKey')->willReturn(static::UNIQUE_ADDRESS_KEY);

        return new ShippingAddressChoiceKeyGenerator($customerServiceMock);
    }
}
