<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Schedule\Review;

use ArrayObject;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\CompanyBusinessUnitTransfer;
use Generated\Shared\Transfer\CompanyUnitAddressCollectionTransfer;
use Generated\Shared\Transfer\CompanyUnitAddressTransfer;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;
use Spryker\Service\Customer\CustomerServiceInterface;
use Spryker\Zed\CompanyUnitAddress\Business\CompanyUnitAddressFacadeInterface;
use Spryker\Zed\CompanyUser\Business\CompanyUserFacadeInterface;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Mapper\AddedItemShippingAddressMapper;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment\AddedItemShippingAddressResolver;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment\BusinessUnitAddressReader;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment\OfferedShippingAddressChecker;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment\ScheduleAddressReader;
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
 * @group AddedItemShippingAddressResolverTest
 * Add your own group annotations below this line
 */
class AddedItemShippingAddressResolverTest extends Unit
{
    protected const int ID_COMPANY_USER = 18;

    protected const int ID_COMPANY_BUSINESS_UNIT = 4;

    protected const int ID_COMPANY_UNIT_ADDRESS = 13;

    protected const string BU_ADDRESS1 = 'Kirncher Str.';

    protected const string BU_ZIP_CODE = '10247';

    protected const string BU_CITY = 'Berlin';

    protected const string ISO2_CODE = 'DE';

    protected const string OTHER_ADDRESS1 = 'Julie-Wolfthorn-Str.';

    protected const string OTHER_ZIP_CODE = '10115';

    protected const string COMPANY_UNIT_ADDRESS_KEY = 'company_unit_address:13';

    public function testOffersOnlyBusinessUnitAddressesWhenTheScheduleStoresNone(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule([]);

        // Act
        $choiceTransfers = $this->createResolver()->getOwnedAddressChoices($recurringScheduleTransfer, new QuoteTransfer());

        // Assert
        $this->assertSame([static::COMPANY_UNIT_ADDRESS_KEY], array_keys($choiceTransfers));
        $this->assertSame(
            SharedOrderExperienceManagementConfig::SHIPPING_ADDRESS_SOURCE_COMPANY_UNIT_ADDRESS,
            $choiceTransfers[static::COMPANY_UNIT_ADDRESS_KEY]->getSource(),
        );
        $this->assertSame(static::ID_COMPANY_UNIT_ADDRESS, $choiceTransfers[static::COMPANY_UNIT_ADDRESS_KEY]->getIdCompanyUnitAddress());
    }

    public function testOffersAScheduleAddressThatIsNotAmongTheBusinessUnitAddresses(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule([
            $this->createItemData(static::OTHER_ADDRESS1, static::OTHER_ZIP_CODE, static::BU_CITY),
        ]);

        // Act
        $choiceTransfers = $this->createResolver()->getOwnedAddressChoices($recurringScheduleTransfer, new QuoteTransfer());

        // Assert
        $this->assertCount(2, $choiceTransfers);

        $scheduleChoiceTransfers = $this->filterBySource(
            $choiceTransfers,
            SharedOrderExperienceManagementConfig::SHIPPING_ADDRESS_SOURCE_SCHEDULE,
        );
        $this->assertCount(1, $scheduleChoiceTransfers);
        $this->assertSame(static::OTHER_ADDRESS1, reset($scheduleChoiceTransfers)->getAddressOrFail()->getAddress1());
        $this->assertNull(reset($scheduleChoiceTransfers)->getIdCompanyUnitAddress());
    }

    /**
     * The buyer must not be shown the same place twice: a stored line address that is really a business unit
     * address carries its id, so it collapses into the business unit entry.
     */
    public function testCollapsesAScheduleAddressCarryingAKnownCompanyUnitAddressId(): void
    {
        // Arrange
        $itemData = $this->createItemData(static::BU_ADDRESS1, static::BU_ZIP_CODE, static::BU_CITY, [
            'idCompanyUnitAddress' => static::ID_COMPANY_UNIT_ADDRESS,
        ]);
        $recurringScheduleTransfer = $this->createSchedule([$itemData]);

        // Act
        $choiceTransfers = $this->createResolver()->getOwnedAddressChoices($recurringScheduleTransfer, new QuoteTransfer());

        // Assert
        $this->assertSame([static::COMPANY_UNIT_ADDRESS_KEY], array_keys($choiceTransfers));
    }

    /**
     * A stored snapshot has no id and disagrees with a business unit address on the contact fields, so only the
     * postal fields can tell the buyer would otherwise see one place twice.
     */
    public function testCollapsesAnIdLessScheduleAddressWithTheSamePostalFields(): void
    {
        // Arrange
        $itemData = $this->createItemData(static::BU_ADDRESS1, static::BU_ZIP_CODE, static::BU_CITY, [
            'firstName' => 'Sonia',
            'lastName' => 'Wagner',
            'company' => 'Acme Corporation',
            'phone' => '4902890031',
        ]);
        $recurringScheduleTransfer = $this->createSchedule([$itemData]);

        // Act
        $choiceTransfers = $this->createResolver()->getOwnedAddressChoices($recurringScheduleTransfer, new QuoteTransfer());

        // Assert
        $this->assertSame([static::COMPANY_UNIT_ADDRESS_KEY], array_keys($choiceTransfers));
    }

    public function testOffersOneEntryWhenSeveralLinesShipToTheSameAddress(): void
    {
        // Arrange
        $itemData = $this->createItemData(static::OTHER_ADDRESS1, static::OTHER_ZIP_CODE, static::BU_CITY);
        $recurringScheduleTransfer = $this->createSchedule([$itemData, $itemData]);

        // Act
        $choiceTransfers = $this->createResolver()->getOwnedAddressChoices($recurringScheduleTransfer, new QuoteTransfer());

        // Assert
        $this->assertCount(2, $choiceTransfers);
    }

    public function testSkipsALineWithoutAShipmentAddress(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createSchedule([json_encode(['sku' => 'sku-1'])]);

        // Act
        $choiceTransfers = $this->createResolver()->getOwnedAddressChoices($recurringScheduleTransfer, new QuoteTransfer());

        // Assert
        $this->assertSame([static::COMPANY_UNIT_ADDRESS_KEY], array_keys($choiceTransfers));
    }

    /**
     * The approval path hands over a quote that already carries items, so its item-level addresses count too.
     */
    public function testOffersAnAddressFoundOnTheQuoteItems(): void
    {
        // Arrange
        $quoteTransfer = (new QuoteTransfer())->addItem(
            (new ItemTransfer())->setShipment(
                (new ShipmentTransfer())->setShippingAddress(
                    (new AddressTransfer())
                        ->setAddress1(static::OTHER_ADDRESS1)
                        ->setZipCode(static::OTHER_ZIP_CODE)
                        ->setCity(static::BU_CITY)
                        ->setIso2Code(static::ISO2_CODE),
                ),
            ),
        );

        // Act
        $choiceTransfers = $this->createResolver()->getOwnedAddressChoices($this->createSchedule([]), $quoteTransfer);

        // Assert
        $this->assertCount(2, $choiceTransfers);
    }

    public function testOffersNothingWithoutACompanyUser(): void
    {
        // Arrange
        $recurringScheduleTransfer = (new RecurringScheduleTransfer())->setItems(new ArrayObject());

        // Act
        $choiceTransfers = $this->createResolver()->getOwnedAddressChoices($recurringScheduleTransfer, new QuoteTransfer());

        // Assert
        $this->assertSame([], $choiceTransfers);
    }

    protected function createResolver(): AddedItemShippingAddressResolver
    {
        $companyUserFacadeMock = $this->createMock(CompanyUserFacadeInterface::class);
        $companyUserFacadeMock->method('findCompanyUserById')->willReturn(
            (new CompanyUserTransfer())->setCompanyBusinessUnit(
                (new CompanyBusinessUnitTransfer())->setIdCompanyBusinessUnit(static::ID_COMPANY_BUSINESS_UNIT),
            ),
        );

        $companyUnitAddressFacadeMock = $this->createMock(CompanyUnitAddressFacadeInterface::class);
        $companyUnitAddressFacadeMock->method('getCompanyUnitAddressCollection')->willReturn(
            (new CompanyUnitAddressCollectionTransfer())->addCompanyUnitAddress(
                (new CompanyUnitAddressTransfer())
                    ->setIdCompanyUnitAddress(static::ID_COMPANY_UNIT_ADDRESS)
                    ->setAddress1(static::BU_ADDRESS1)
                    ->setZipCode(static::BU_ZIP_CODE)
                    ->setCity(static::BU_CITY)
                    ->setIso2Code(static::ISO2_CODE),
            ),
        );

        $addedItemShippingAddressMapper = new AddedItemShippingAddressMapper();

        return new AddedItemShippingAddressResolver(
            new BusinessUnitAddressReader($companyUserFacadeMock, $companyUnitAddressFacadeMock, $addedItemShippingAddressMapper),
            new ScheduleAddressReader($addedItemShippingAddressMapper),
            new ShippingAddressChoiceKeyGenerator($this->createCustomerServiceMock()),
            new OfferedShippingAddressChecker(),
            $addedItemShippingAddressMapper,
        );
    }

    /**
     * Mirrors the real service: an md5 over the address fields, ids excluded.
     */
    protected function createCustomerServiceMock(): CustomerServiceInterface
    {
        $customerServiceMock = $this->createMock(CustomerServiceInterface::class);
        $customerServiceMock->method('getUniqueAddressKey')->willReturnCallback(
            static function (AddressTransfer $addressTransfer): string {
                $addressData = $addressTransfer->toArray(true, true);
                unset($addressData['id_company_unit_address'], $addressData['id_sales_order_address'], $addressData['is_address_saving_skipped']);

                return md5((string)json_encode(array_filter($addressData)));
            },
        );

        return $customerServiceMock;
    }

    /**
     * @param array<string|false> $itemDataList
     */
    protected function createSchedule(array $itemDataList): RecurringScheduleTransfer
    {
        $recurringScheduleItemTransfers = new ArrayObject();

        foreach ($itemDataList as $itemData) {
            $recurringScheduleItemTransfers->append(
                (new RecurringScheduleItemTransfer())->setItemData((string)$itemData),
            );
        }

        return (new RecurringScheduleTransfer())
            ->setIdCompanyUser(static::ID_COMPANY_USER)
            ->setItems($recurringScheduleItemTransfers);
    }

    /**
     * @param array<string, mixed> $additionalAddressData
     */
    protected function createItemData(
        string $address1,
        string $zipCode,
        string $city,
        array $additionalAddressData = [],
    ): string {
        return (string)json_encode([
            ItemTransfer::SHIPMENT => [
                ShipmentTransfer::SHIPPING_ADDRESS => $additionalAddressData + [
                    'address1' => $address1,
                    'zipCode' => $zipCode,
                    'city' => $city,
                    'iso2Code' => static::ISO2_CODE,
                ],
            ],
        ]);
    }

    /**
     * @param array<string, \Generated\Shared\Transfer\RecurringScheduleShippingAddressChoiceTransfer> $choiceTransfers
     *
     * @return array<string, \Generated\Shared\Transfer\RecurringScheduleShippingAddressChoiceTransfer>
     */
    protected function filterBySource(array $choiceTransfers, string $source): array
    {
        return array_filter(
            $choiceTransfers,
            static fn ($choiceTransfer): bool => $choiceTransfer->getSource() === $source,
        );
    }
}
