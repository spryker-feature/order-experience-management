<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment;

use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleShippingAddressChoiceTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Mapper\AddedItemShippingAddressMapperInterface;

class AddedItemShippingAddressResolver implements AddedItemShippingAddressResolverInterface
{
    public function __construct(
        protected readonly BusinessUnitAddressReaderInterface $businessUnitAddressReader,
        protected readonly ScheduleAddressReaderInterface $scheduleAddressReader,
        protected readonly ShippingAddressChoiceKeyGeneratorInterface $shippingAddressChoiceKeyGenerator,
        protected readonly OfferedShippingAddressCheckerInterface $offeredShippingAddressChecker,
        protected readonly AddedItemShippingAddressMapperInterface $addedItemShippingAddressMapper,
    ) {
    }

    /**
     * @return array<string, \Generated\Shared\Transfer\RecurringScheduleShippingAddressChoiceTransfer>
     */
    public function getOwnedAddressChoices(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        QuoteTransfer $scheduleQuoteTransfer,
    ): array {
        $choiceTransfers = $this->createBusinessUnitAddressChoices($recurringScheduleTransfer, $scheduleQuoteTransfer);
        $choiceTransfers = $this->addScheduleAddressChoices($choiceTransfers, $recurringScheduleTransfer, $scheduleQuoteTransfer);

        return $choiceTransfers;
    }

    /**
     * @return array<string, \Generated\Shared\Transfer\RecurringScheduleShippingAddressChoiceTransfer>
     */
    protected function createBusinessUnitAddressChoices(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        QuoteTransfer $scheduleQuoteTransfer,
    ): array {
        $addressTransfers = $this->businessUnitAddressReader->getAddressTransfers(
            $recurringScheduleTransfer,
            $scheduleQuoteTransfer->getCustomer(),
        );

        $choiceTransfers = [];

        foreach ($addressTransfers as $idCompanyUnitAddress => $addressTransfer) {
            $key = $this->shippingAddressChoiceKeyGenerator->generateCompanyUnitAddressKey($idCompanyUnitAddress);
            $choiceTransfers[$key] = $this->createChoice(
                $addressTransfer,
                $key,
                SharedOrderExperienceManagementConfig::SHIPPING_ADDRESS_SOURCE_COMPANY_UNIT_ADDRESS,
            );
        }

        return $choiceTransfers;
    }

    /**
     * @param array<string, \Generated\Shared\Transfer\RecurringScheduleShippingAddressChoiceTransfer> $choiceTransfers
     *
     * @return array<string, \Generated\Shared\Transfer\RecurringScheduleShippingAddressChoiceTransfer>
     */
    protected function addScheduleAddressChoices(
        array $choiceTransfers,
        RecurringScheduleTransfer $recurringScheduleTransfer,
        QuoteTransfer $scheduleQuoteTransfer,
    ): array {
        $addressTransfers = $this->scheduleAddressReader->getAddressTransfers(
            $recurringScheduleTransfer,
            $scheduleQuoteTransfer,
        );

        foreach ($addressTransfers as $addressTransfer) {
            if ($this->offeredShippingAddressChecker->isAlreadyOffered($addressTransfer, $choiceTransfers)) {
                continue;
            }

            $key = $this->shippingAddressChoiceKeyGenerator->generateScheduleAddressKey($addressTransfer);
            $choiceTransfers[$key] = $this->createChoice(
                $addressTransfer,
                $key,
                SharedOrderExperienceManagementConfig::SHIPPING_ADDRESS_SOURCE_SCHEDULE,
            );
        }

        return $choiceTransfers;
    }

    protected function createChoice(
        AddressTransfer $addressTransfer,
        string $key,
        string $source,
    ): RecurringScheduleShippingAddressChoiceTransfer {
        return $this->addedItemShippingAddressMapper->mapAddressTransferToChoiceTransfer(
            $addressTransfer,
            $key,
            $source,
            new RecurringScheduleShippingAddressChoiceTransfer(),
        );
    }
}
