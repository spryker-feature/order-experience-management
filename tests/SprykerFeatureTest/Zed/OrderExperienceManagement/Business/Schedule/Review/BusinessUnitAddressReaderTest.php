<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Schedule\Review;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\CompanyBusinessUnitTransfer;
use Generated\Shared\Transfer\CompanyUnitAddressCollectionTransfer;
use Generated\Shared\Transfer\CompanyUnitAddressCriteriaFilterTransfer;
use Generated\Shared\Transfer\CompanyUnitAddressTransfer;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Spryker\Zed\CompanyUnitAddress\Business\CompanyUnitAddressFacadeInterface;
use Spryker\Zed\CompanyUser\Business\CompanyUserFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Mapper\AddedItemShippingAddressMapperInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment\BusinessUnitAddressReader;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Schedule
 * @group Review
 * @group BusinessUnitAddressReaderTest
 * Add your own group annotations below this line
 */
class BusinessUnitAddressReaderTest extends Unit
{
    protected const int ID_COMPANY_USER = 7;

    protected const int ID_COMPANY_BUSINESS_UNIT = 21;

    protected const int ID_COMPANY_UNIT_ADDRESS = 13;

    protected const int ID_COMPANY_UNIT_ADDRESS_SECOND = 14;

    public function testReturnsAddressesKeyedByCompanyUnitAddressId(): void
    {
        // Arrange
        $businessUnitAddressReader = new BusinessUnitAddressReader(
            $this->createCompanyUserFacadeMock(static::ID_COMPANY_BUSINESS_UNIT),
            $this->createCompanyUnitAddressFacadeMock([static::ID_COMPANY_UNIT_ADDRESS, static::ID_COMPANY_UNIT_ADDRESS_SECOND]),
            $this->createMapperMock(),
        );

        // Act
        $addressTransfers = $businessUnitAddressReader->getAddressTransfers($this->createSchedule(static::ID_COMPANY_USER), null);

        // Assert
        $this->assertSame(
            [static::ID_COMPANY_UNIT_ADDRESS, static::ID_COMPANY_UNIT_ADDRESS_SECOND],
            array_keys($addressTransfers),
        );
    }

    public function testSkipsACompanyUnitAddressWithoutAnId(): void
    {
        // Arrange
        $companyUnitAddressCollectionTransfer = (new CompanyUnitAddressCollectionTransfer())
            ->addCompanyUnitAddress(new CompanyUnitAddressTransfer())
            ->addCompanyUnitAddress((new CompanyUnitAddressTransfer())->setIdCompanyUnitAddress(static::ID_COMPANY_UNIT_ADDRESS));

        $companyUnitAddressFacadeMock = $this->createMock(CompanyUnitAddressFacadeInterface::class);
        $companyUnitAddressFacadeMock->method('getCompanyUnitAddressCollection')->willReturn($companyUnitAddressCollectionTransfer);

        $businessUnitAddressReader = new BusinessUnitAddressReader(
            $this->createCompanyUserFacadeMock(static::ID_COMPANY_BUSINESS_UNIT),
            $companyUnitAddressFacadeMock,
            $this->createMapperMock(),
        );

        // Act
        $addressTransfers = $businessUnitAddressReader->getAddressTransfers($this->createSchedule(static::ID_COMPANY_USER), null);

        // Assert
        $this->assertSame([static::ID_COMPANY_UNIT_ADDRESS], array_keys($addressTransfers));
    }

    /**
     * The address lookup is a database read, so an unresolvable business unit must not reach it.
     */
    public function testSkipsTheAddressLookupWhenTheScheduleHasNoCompanyUser(): void
    {
        // Arrange
        $businessUnitAddressReader = new BusinessUnitAddressReader(
            $this->createMock(CompanyUserFacadeInterface::class),
            $this->createUnusedCompanyUnitAddressFacadeMock(),
            $this->createMapperMock(),
        );

        // Act
        $addressTransfers = $businessUnitAddressReader->getAddressTransfers(new RecurringScheduleTransfer(), null);

        // Assert
        $this->assertSame([], $addressTransfers);
    }

    public function testSkipsTheAddressLookupWhenTheCompanyUserIsNotFound(): void
    {
        // Arrange
        $companyUserFacadeMock = $this->createMock(CompanyUserFacadeInterface::class);
        $companyUserFacadeMock->method('findCompanyUserById')->willReturn(null);

        $businessUnitAddressReader = new BusinessUnitAddressReader(
            $companyUserFacadeMock,
            $this->createUnusedCompanyUnitAddressFacadeMock(),
            $this->createMapperMock(),
        );

        // Act
        $addressTransfers = $businessUnitAddressReader->getAddressTransfers($this->createSchedule(static::ID_COMPANY_USER), null);

        // Assert
        $this->assertSame([], $addressTransfers);
    }

    public function testSkipsTheAddressLookupWhenTheCompanyUserHasNoBusinessUnit(): void
    {
        // Arrange
        $companyUserFacadeMock = $this->createMock(CompanyUserFacadeInterface::class);
        $companyUserFacadeMock->method('findCompanyUserById')->willReturn(new CompanyUserTransfer());

        $businessUnitAddressReader = new BusinessUnitAddressReader(
            $companyUserFacadeMock,
            $this->createUnusedCompanyUnitAddressFacadeMock(),
            $this->createMapperMock(),
        );

        // Act
        $addressTransfers = $businessUnitAddressReader->getAddressTransfers($this->createSchedule(static::ID_COMPANY_USER), null);

        // Assert
        $this->assertSame([], $addressTransfers);
    }

    public function testFiltersTheAddressesByTheResolvedBusinessUnit(): void
    {
        // Arrange
        $companyUnitAddressFacadeMock = $this->createMock(CompanyUnitAddressFacadeInterface::class);
        $companyUnitAddressFacadeMock
            ->method('getCompanyUnitAddressCollection')
            ->willReturnCallback(function (CompanyUnitAddressCriteriaFilterTransfer $criteriaFilterTransfer): CompanyUnitAddressCollectionTransfer {
                $this->assertSame(static::ID_COMPANY_BUSINESS_UNIT, $criteriaFilterTransfer->getIdCompanyBusinessUnit());

                return new CompanyUnitAddressCollectionTransfer();
            });

        $businessUnitAddressReader = new BusinessUnitAddressReader(
            $this->createCompanyUserFacadeMock(static::ID_COMPANY_BUSINESS_UNIT),
            $companyUnitAddressFacadeMock,
            $this->createMapperMock(),
        );

        // Act
        $addressTransfers = $businessUnitAddressReader->getAddressTransfers($this->createSchedule(static::ID_COMPANY_USER), null);

        // Assert
        $this->assertSame([], $addressTransfers);
    }

    /**
     * The customer supplies the salutation and name of a business unit address, and it is absent for a schedule
     * whose quote carries no customer.
     */
    public function testPassesTheCustomerThroughToTheMapper(): void
    {
        // Arrange
        $customerTransfer = new CustomerTransfer();

        $addedItemShippingAddressMapperMock = $this->createMock(AddedItemShippingAddressMapperInterface::class);
        $addedItemShippingAddressMapperMock
            ->expects($this->once())
            ->method('mapCompanyUnitAddressTransferToAddressTransfer')
            ->with($this->anything(), $customerTransfer, $this->anything())
            ->willReturn(new AddressTransfer());

        $businessUnitAddressReader = new BusinessUnitAddressReader(
            $this->createCompanyUserFacadeMock(static::ID_COMPANY_BUSINESS_UNIT),
            $this->createCompanyUnitAddressFacadeMock([static::ID_COMPANY_UNIT_ADDRESS]),
            $addedItemShippingAddressMapperMock,
        );

        // Act
        $addressTransfers = $businessUnitAddressReader->getAddressTransfers(
            $this->createSchedule(static::ID_COMPANY_USER),
            $customerTransfer,
        );

        // Assert
        $this->assertCount(1, $addressTransfers);
    }

    protected function createSchedule(int $idCompanyUser): RecurringScheduleTransfer
    {
        return (new RecurringScheduleTransfer())->setIdCompanyUser($idCompanyUser);
    }

    protected function createCompanyUserFacadeMock(int $idCompanyBusinessUnit): CompanyUserFacadeInterface
    {
        $companyUserTransfer = (new CompanyUserTransfer())->setCompanyBusinessUnit(
            (new CompanyBusinessUnitTransfer())->setIdCompanyBusinessUnit($idCompanyBusinessUnit),
        );

        $companyUserFacadeMock = $this->createMock(CompanyUserFacadeInterface::class);
        $companyUserFacadeMock->method('findCompanyUserById')->willReturn($companyUserTransfer);

        return $companyUserFacadeMock;
    }

    /**
     * @param array<int> $companyUnitAddressIds
     */
    protected function createCompanyUnitAddressFacadeMock(array $companyUnitAddressIds): CompanyUnitAddressFacadeInterface
    {
        $companyUnitAddressCollectionTransfer = new CompanyUnitAddressCollectionTransfer();

        foreach ($companyUnitAddressIds as $idCompanyUnitAddress) {
            $companyUnitAddressCollectionTransfer->addCompanyUnitAddress(
                (new CompanyUnitAddressTransfer())->setIdCompanyUnitAddress($idCompanyUnitAddress),
            );
        }

        $companyUnitAddressFacadeMock = $this->createMock(CompanyUnitAddressFacadeInterface::class);
        $companyUnitAddressFacadeMock->method('getCompanyUnitAddressCollection')->willReturn($companyUnitAddressCollectionTransfer);

        return $companyUnitAddressFacadeMock;
    }

    protected function createUnusedCompanyUnitAddressFacadeMock(): CompanyUnitAddressFacadeInterface
    {
        $companyUnitAddressFacadeMock = $this->createMock(CompanyUnitAddressFacadeInterface::class);
        $companyUnitAddressFacadeMock->expects($this->never())->method('getCompanyUnitAddressCollection');

        return $companyUnitAddressFacadeMock;
    }

    protected function createMapperMock(): AddedItemShippingAddressMapperInterface
    {
        $addedItemShippingAddressMapperMock = $this->createMock(AddedItemShippingAddressMapperInterface::class);
        $addedItemShippingAddressMapperMock
            ->method('mapCompanyUnitAddressTransferToAddressTransfer')
            ->willReturn(new AddressTransfer());

        return $addedItemShippingAddressMapperMock;
    }
}
