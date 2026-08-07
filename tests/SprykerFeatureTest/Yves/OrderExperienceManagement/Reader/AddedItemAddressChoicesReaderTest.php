<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\OrderExperienceManagement\Reader;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleShippingAddressChoiceTransfer;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\AddedItemAddressChoicesReader;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group OrderExperienceManagement
 * @group Reader
 * @group AddedItemAddressChoicesReaderTest
 */
class AddedItemAddressChoicesReaderTest extends Unit
{
    protected const string GLOSSARY_KEY_GROUP_SCHEDULE = 'recurring_orders.review.add_product.shipment_address.group.schedule';

    protected const string GLOSSARY_KEY_GROUP_COMPANY_UNIT_ADDRESS = 'recurring_orders.review.add_product.shipment_address.group.company_unit_address';

    protected const string COMPANY_UNIT_ADDRESS_KEY = 'company_unit_address:13';

    protected const string SCHEDULE_ADDRESS_KEY = 'schedule_address:abc123';

    public function testGroupsChoicesBySourceWithTheScheduleGroupFirst(): void
    {
        // Arrange
        $recurringScheduleReviewResponseTransfer = (new RecurringScheduleReviewResponseTransfer())
            ->addShippingAddressChoice($this->createCompanyUnitAddressChoice())
            ->addShippingAddressChoice($this->createScheduleChoice());

        // Act
        $addressChoices = (new AddedItemAddressChoicesReader())->getAddressChoices($recurringScheduleReviewResponseTransfer);

        // Assert
        $this->assertSame(
            [static::GLOSSARY_KEY_GROUP_SCHEDULE, static::GLOSSARY_KEY_GROUP_COMPANY_UNIT_ADDRESS],
            array_keys($addressChoices),
        );
        $this->assertSame([static::SCHEDULE_ADDRESS_KEY], array_keys($addressChoices[static::GLOSSARY_KEY_GROUP_SCHEDULE]));
        $this->assertSame([static::COMPANY_UNIT_ADDRESS_KEY], array_keys($addressChoices[static::GLOSSARY_KEY_GROUP_COMPANY_UNIT_ADDRESS]));
    }

    public function testBuildsTheLabelFromTheAddressPartsAndCarriesTheCompanyUnitAddressId(): void
    {
        // Arrange
        $recurringScheduleReviewResponseTransfer = (new RecurringScheduleReviewResponseTransfer())
            ->addShippingAddressChoice($this->createCompanyUnitAddressChoice());

        // Act
        $addressChoices = (new AddedItemAddressChoicesReader())->getAddressChoices($recurringScheduleReviewResponseTransfer);

        // Assert
        $this->assertSame(
            ['label' => 'Kirncher Str., 10247, Berlin, DE', 'idCompanyUnitAddress' => '13'],
            $addressChoices[static::GLOSSARY_KEY_GROUP_COMPANY_UNIT_ADDRESS][static::COMPANY_UNIT_ADDRESS_KEY],
        );
    }

    /**
     * The template renders this straight into the legacy idShippingAddress field, which must stay empty for an
     * address that exists only on the schedule.
     */
    public function testLeavesTheCompanyUnitAddressIdEmptyForAScheduleAddress(): void
    {
        // Arrange
        $recurringScheduleReviewResponseTransfer = (new RecurringScheduleReviewResponseTransfer())
            ->addShippingAddressChoice($this->createScheduleChoice());

        // Act
        $addressChoices = (new AddedItemAddressChoicesReader())->getAddressChoices($recurringScheduleReviewResponseTransfer);

        // Assert
        $this->assertSame(
            ['label' => 'Julie-Wolfthorn-Str., 10115, Berlin, DE', 'idCompanyUnitAddress' => ''],
            $addressChoices[static::GLOSSARY_KEY_GROUP_SCHEDULE][static::SCHEDULE_ADDRESS_KEY],
        );
    }

    public function testOmitsAGroupThatHasNoChoices(): void
    {
        // Arrange
        $recurringScheduleReviewResponseTransfer = (new RecurringScheduleReviewResponseTransfer())
            ->addShippingAddressChoice($this->createCompanyUnitAddressChoice());

        // Act
        $addressChoices = (new AddedItemAddressChoicesReader())->getAddressChoices($recurringScheduleReviewResponseTransfer);

        // Assert
        $this->assertSame([static::GLOSSARY_KEY_GROUP_COMPANY_UNIT_ADDRESS], array_keys($addressChoices));
    }

    public function testReturnsNoGroupsWithoutChoices(): void
    {
        // Act
        $addressChoices = (new AddedItemAddressChoicesReader())->getAddressChoices(new RecurringScheduleReviewResponseTransfer());

        // Assert
        $this->assertSame([], $addressChoices);
    }

    public function testSkipsAChoiceWithoutAKeyOrAnAddress(): void
    {
        // Arrange
        $recurringScheduleReviewResponseTransfer = (new RecurringScheduleReviewResponseTransfer())
            ->addShippingAddressChoice(
                (new RecurringScheduleShippingAddressChoiceTransfer())
                    ->setSource(SharedOrderExperienceManagementConfig::SHIPPING_ADDRESS_SOURCE_SCHEDULE)
                    ->setAddress(new AddressTransfer()),
            )
            ->addShippingAddressChoice(
                (new RecurringScheduleShippingAddressChoiceTransfer())
                    ->setKey(static::SCHEDULE_ADDRESS_KEY)
                    ->setSource(SharedOrderExperienceManagementConfig::SHIPPING_ADDRESS_SOURCE_SCHEDULE),
            );

        // Act
        $addressChoices = (new AddedItemAddressChoicesReader())->getAddressChoices($recurringScheduleReviewResponseTransfer);

        // Assert
        $this->assertSame([], $addressChoices);
    }

    protected function createCompanyUnitAddressChoice(): RecurringScheduleShippingAddressChoiceTransfer
    {
        return (new RecurringScheduleShippingAddressChoiceTransfer())
            ->setKey(static::COMPANY_UNIT_ADDRESS_KEY)
            ->setSource(SharedOrderExperienceManagementConfig::SHIPPING_ADDRESS_SOURCE_COMPANY_UNIT_ADDRESS)
            ->setIdCompanyUnitAddress(13)
            ->setAddress(
                (new AddressTransfer())
                    ->setAddress1('Kirncher Str.')
                    ->setZipCode('10247')
                    ->setCity('Berlin')
                    ->setIso2Code('DE'),
            );
    }

    protected function createScheduleChoice(): RecurringScheduleShippingAddressChoiceTransfer
    {
        return (new RecurringScheduleShippingAddressChoiceTransfer())
            ->setKey(static::SCHEDULE_ADDRESS_KEY)
            ->setSource(SharedOrderExperienceManagementConfig::SHIPPING_ADDRESS_SOURCE_SCHEDULE)
            ->setAddress(
                (new AddressTransfer())
                    ->setAddress1('Julie-Wolfthorn-Str.')
                    ->setZipCode('10115')
                    ->setCity('Berlin')
                    ->setIso2Code('DE'),
            );
    }
}
