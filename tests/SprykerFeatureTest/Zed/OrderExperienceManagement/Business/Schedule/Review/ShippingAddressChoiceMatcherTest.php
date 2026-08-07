<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Schedule\Review;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemAdditionTransfer;
use Generated\Shared\Transfer\RecurringScheduleShippingAddressChoiceTransfer;
use Spryker\Service\Customer\CustomerServiceInterface;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment\ShippingAddressChoiceKeyGenerator;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment\ShippingAddressChoiceMatcher;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Schedule
 * @group Review
 * @group ShippingAddressChoiceMatcherTest
 * Add your own group annotations below this line
 */
class ShippingAddressChoiceMatcherTest extends Unit
{
    protected const int ID_COMPANY_UNIT_ADDRESS = 13;

    protected const string COMPANY_UNIT_ADDRESS_KEY = 'company_unit_address:13';

    protected const string SCHEDULE_ADDRESS_KEY = 'schedule:abc123';

    public function testFindsAChoiceByItsKey(): void
    {
        // Arrange
        $recurringScheduleItemAdditionTransfer = (new RecurringScheduleItemAdditionTransfer())
            ->setShippingAddressKey(static::SCHEDULE_ADDRESS_KEY);

        // Act
        $choiceTransfer = $this->createMatcher()->findChoice($recurringScheduleItemAdditionTransfer, $this->createChoices());

        // Assert
        $this->assertNotNull($choiceTransfer);
        $this->assertSame(static::SCHEDULE_ADDRESS_KEY, $choiceTransfer->getKey());
    }

    /**
     * Clients that predate the choice key submit only the business unit address id.
     */
    public function testFindsAChoiceByTheCompanyUnitAddressIdWhenNoKeyIsSubmitted(): void
    {
        // Arrange
        $recurringScheduleItemAdditionTransfer = (new RecurringScheduleItemAdditionTransfer())
            ->setIdShippingAddress(static::ID_COMPANY_UNIT_ADDRESS);

        // Act
        $choiceTransfer = $this->createMatcher()->findChoice($recurringScheduleItemAdditionTransfer, $this->createChoices());

        // Assert
        $this->assertNotNull($choiceTransfer);
        $this->assertSame(static::COMPANY_UNIT_ADDRESS_KEY, $choiceTransfer->getKey());
    }

    public function testPrefersTheKeyOverTheId(): void
    {
        // Arrange
        $recurringScheduleItemAdditionTransfer = (new RecurringScheduleItemAdditionTransfer())
            ->setShippingAddressKey(static::SCHEDULE_ADDRESS_KEY)
            ->setIdShippingAddress(static::ID_COMPANY_UNIT_ADDRESS);

        // Act
        $choiceTransfer = $this->createMatcher()->findChoice($recurringScheduleItemAdditionTransfer, $this->createChoices());

        // Assert
        $this->assertNotNull($choiceTransfer);
        $this->assertSame(static::SCHEDULE_ADDRESS_KEY, $choiceTransfer->getKey());
    }

    /**
     * The choices are the whitelist, so anything outside them must not match.
     */
    public function testFindsNoChoiceForAKeyThatIsNotOffered(): void
    {
        // Arrange
        $recurringScheduleItemAdditionTransfer = (new RecurringScheduleItemAdditionTransfer())
            ->setShippingAddressKey('schedule:deadbeef');

        // Act
        $choiceTransfer = $this->createMatcher()->findChoice($recurringScheduleItemAdditionTransfer, $this->createChoices());

        // Assert
        $this->assertNull($choiceTransfer);
    }

    public function testFindsNoChoiceForAnIdThatIsNotOffered(): void
    {
        // Arrange
        $recurringScheduleItemAdditionTransfer = (new RecurringScheduleItemAdditionTransfer())->setIdShippingAddress(999);

        // Act
        $choiceTransfer = $this->createMatcher()->findChoice($recurringScheduleItemAdditionTransfer, $this->createChoices());

        // Assert
        $this->assertNull($choiceTransfer);
    }

    public function testFindsNoChoiceWhenNeitherKeyNorIdIsSubmitted(): void
    {
        // Act
        $choiceTransfer = $this->createMatcher()->findChoice(new RecurringScheduleItemAdditionTransfer(), $this->createChoices());

        // Assert
        $this->assertNull($choiceTransfer);
    }

    protected function createMatcher(): ShippingAddressChoiceMatcher
    {
        return new ShippingAddressChoiceMatcher(
            new ShippingAddressChoiceKeyGenerator($this->createMock(CustomerServiceInterface::class)),
        );
    }

    /**
     * @return array<string, \Generated\Shared\Transfer\RecurringScheduleShippingAddressChoiceTransfer>
     */
    protected function createChoices(): array
    {
        return [
            static::COMPANY_UNIT_ADDRESS_KEY => (new RecurringScheduleShippingAddressChoiceTransfer())
                ->setKey(static::COMPANY_UNIT_ADDRESS_KEY)
                ->setSource(SharedOrderExperienceManagementConfig::SHIPPING_ADDRESS_SOURCE_COMPANY_UNIT_ADDRESS)
                ->setIdCompanyUnitAddress(static::ID_COMPANY_UNIT_ADDRESS)
                ->setAddress(new AddressTransfer()),
            static::SCHEDULE_ADDRESS_KEY => (new RecurringScheduleShippingAddressChoiceTransfer())
                ->setKey(static::SCHEDULE_ADDRESS_KEY)
                ->setSource(SharedOrderExperienceManagementConfig::SHIPPING_ADDRESS_SOURCE_SCHEDULE)
                ->setAddress(new AddressTransfer()),
        ];
    }
}
