<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\OrderExperienceManagement\Resolver;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleShippingAddressChoiceTransfer;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Yves\OrderExperienceManagement\Resolver\AddedItemShippingAddressResolver;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group OrderExperienceManagement
 * @group Resolver
 * @group AddedItemShippingAddressResolverTest
 */
class AddedItemShippingAddressResolverTest extends Unit
{
    protected const string COMPANY_UNIT_ADDRESS_KEY = 'company_unit_address:13';

    protected const string SCHEDULE_ADDRESS_KEY = 'schedule_address:abc123';

    protected const int ID_COMPANY_UNIT_ADDRESS = 13;

    protected const string SCHEDULE_ADDRESS1 = 'Julie-Wolfthorn-Str.';

    protected const string FOREIGN_COMPANY_UNIT_ADDRESS_KEY = 'company_unit_address:999';

    public function testResolvesTheAddressOfTheChosenKey(): void
    {
        // Act
        $addressTransfer = (new AddedItemShippingAddressResolver())->resolveAddress(
            static::SCHEDULE_ADDRESS_KEY,
            null,
            $this->createReviewResponse(),
        );

        // Assert
        $this->assertNotNull($addressTransfer);
        $this->assertSame(static::SCHEDULE_ADDRESS1, $addressTransfer->getAddress1());
    }

    /**
     * Callers that predate the choice key send only the business unit address id.
     */
    public function testResolvesTheAddressFromTheCompanyUnitAddressIdWhenNoKeyIsGiven(): void
    {
        // Act
        $addressTransfer = (new AddedItemShippingAddressResolver())->resolveAddress(
            null,
            static::ID_COMPANY_UNIT_ADDRESS,
            $this->createReviewResponse(),
        );

        // Assert
        $this->assertNotNull($addressTransfer);
        $this->assertSame(static::ID_COMPANY_UNIT_ADDRESS, $addressTransfer->getIdCompanyUnitAddress());
    }

    public function testPrefersTheKeyOverTheId(): void
    {
        // Act
        $addressTransfer = (new AddedItemShippingAddressResolver())->resolveAddress(
            static::SCHEDULE_ADDRESS_KEY,
            static::ID_COMPANY_UNIT_ADDRESS,
            $this->createReviewResponse(),
        );

        // Assert
        $this->assertNotNull($addressTransfer);
        $this->assertSame(static::SCHEDULE_ADDRESS1, $addressTransfer->getAddress1());
    }

    /**
     * The choices are already scoped to this schedule and buyer, so anything outside them must not resolve.
     */
    public function testResolvesNothingForAKeyThatIsNotOffered(): void
    {
        // Act
        $addressTransfer = (new AddedItemShippingAddressResolver())->resolveAddress(
            static::FOREIGN_COMPANY_UNIT_ADDRESS_KEY,
            null,
            $this->createReviewResponse(),
        );

        // Assert
        $this->assertNull($addressTransfer);
    }

    public function testResolvesNothingWithoutAKeyOrAnId(): void
    {
        // Act
        $addressTransfer = (new AddedItemShippingAddressResolver())->resolveAddress('', 0, $this->createReviewResponse());

        // Assert
        $this->assertNull($addressTransfer);
    }

    /**
     * An address stored with the schedule can carry a company unit address id that is no longer offered as a
     * business unit address. The legacy id-only path addresses business unit addresses only, so it must not
     * reach such a choice.
     */
    public function testResolvesNothingFromTheIdWhenOnlyAScheduleChoiceCarriesIt(): void
    {
        // Arrange
        $recurringScheduleReviewResponseTransfer = (new RecurringScheduleReviewResponseTransfer())
            ->addShippingAddressChoice(
                (new RecurringScheduleShippingAddressChoiceTransfer())
                    ->setKey(static::SCHEDULE_ADDRESS_KEY)
                    ->setSource(SharedOrderExperienceManagementConfig::SHIPPING_ADDRESS_SOURCE_SCHEDULE)
                    ->setIdCompanyUnitAddress(static::ID_COMPANY_UNIT_ADDRESS)
                    ->setAddress((new AddressTransfer())->setAddress1(static::SCHEDULE_ADDRESS1)),
            );

        // Act
        $addressTransfer = (new AddedItemShippingAddressResolver())->resolveAddress(
            null,
            static::ID_COMPANY_UNIT_ADDRESS,
            $recurringScheduleReviewResponseTransfer,
        );

        // Assert
        $this->assertNull($addressTransfer);
    }

    /**
     * The same schedule choice must still resolve when it is addressed by its own key.
     */
    public function testResolvesAScheduleChoiceCarryingACompanyUnitAddressIdByItsKey(): void
    {
        // Arrange
        $recurringScheduleReviewResponseTransfer = (new RecurringScheduleReviewResponseTransfer())
            ->addShippingAddressChoice(
                (new RecurringScheduleShippingAddressChoiceTransfer())
                    ->setKey(static::SCHEDULE_ADDRESS_KEY)
                    ->setSource(SharedOrderExperienceManagementConfig::SHIPPING_ADDRESS_SOURCE_SCHEDULE)
                    ->setIdCompanyUnitAddress(static::ID_COMPANY_UNIT_ADDRESS)
                    ->setAddress((new AddressTransfer())->setAddress1(static::SCHEDULE_ADDRESS1)),
            );

        // Act
        $addressTransfer = (new AddedItemShippingAddressResolver())->resolveAddress(
            static::SCHEDULE_ADDRESS_KEY,
            null,
            $recurringScheduleReviewResponseTransfer,
        );

        // Assert
        $this->assertNotNull($addressTransfer);
        $this->assertSame(static::SCHEDULE_ADDRESS1, $addressTransfer->getAddress1());
    }

    protected function createReviewResponse(): RecurringScheduleReviewResponseTransfer
    {
        return (new RecurringScheduleReviewResponseTransfer())
            ->addShippingAddressChoice(
                (new RecurringScheduleShippingAddressChoiceTransfer())
                    ->setKey(static::COMPANY_UNIT_ADDRESS_KEY)
                    ->setSource(SharedOrderExperienceManagementConfig::SHIPPING_ADDRESS_SOURCE_COMPANY_UNIT_ADDRESS)
                    ->setIdCompanyUnitAddress(static::ID_COMPANY_UNIT_ADDRESS)
                    ->setAddress(
                        (new AddressTransfer())
                            ->setAddress1('Kirncher Str.')
                            ->setIdCompanyUnitAddress(static::ID_COMPANY_UNIT_ADDRESS),
                    ),
            )
            ->addShippingAddressChoice(
                (new RecurringScheduleShippingAddressChoiceTransfer())
                    ->setKey(static::SCHEDULE_ADDRESS_KEY)
                    ->setSource(SharedOrderExperienceManagementConfig::SHIPPING_ADDRESS_SOURCE_SCHEDULE)
                    ->setAddress((new AddressTransfer())->setAddress1(static::SCHEDULE_ADDRESS1)),
            );
    }
}
