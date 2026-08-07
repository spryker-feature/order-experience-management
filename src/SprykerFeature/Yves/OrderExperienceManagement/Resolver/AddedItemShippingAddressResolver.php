<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Resolver;

use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleShippingAddressChoiceTransfer;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;

class AddedItemShippingAddressResolver implements AddedItemShippingAddressResolverInterface
{
    public function resolveAddress(
        ?string $shippingAddressKey,
        ?int $idShippingAddress,
        RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer,
    ): ?AddressTransfer {
        $choiceTransfers = $recurringScheduleReviewResponseTransfer->getShippingAddressChoices();

        if ($shippingAddressKey !== null && $shippingAddressKey !== '') {
            return $this->findAddressByKey($choiceTransfers, $shippingAddressKey);
        }

        if ($idShippingAddress === null || $idShippingAddress === 0) {
            return null;
        }

        return $this->findAddressByIdCompanyUnitAddress($choiceTransfers, $idShippingAddress);
    }

    /**
     * @param iterable<\Generated\Shared\Transfer\RecurringScheduleShippingAddressChoiceTransfer> $choiceTransfers
     */
    protected function findAddressByKey(iterable $choiceTransfers, string $shippingAddressKey): ?AddressTransfer
    {
        foreach ($choiceTransfers as $choiceTransfer) {
            if ($choiceTransfer->getKey() === $shippingAddressKey) {
                return $choiceTransfer->getAddress();
            }
        }

        return null;
    }

    /**
     * @param iterable<\Generated\Shared\Transfer\RecurringScheduleShippingAddressChoiceTransfer> $choiceTransfers
     */
    protected function findAddressByIdCompanyUnitAddress(iterable $choiceTransfers, int $idShippingAddress): ?AddressTransfer
    {
        foreach ($choiceTransfers as $choiceTransfer) {
            if ($this->isCompanyUnitAddressChoice($choiceTransfer, $idShippingAddress)) {
                return $choiceTransfer->getAddress();
            }
        }

        return null;
    }

    protected function isCompanyUnitAddressChoice(
        RecurringScheduleShippingAddressChoiceTransfer $recurringScheduleShippingAddressChoiceTransfer,
        int $idShippingAddress,
    ): bool {
        return $recurringScheduleShippingAddressChoiceTransfer->getSource() === SharedOrderExperienceManagementConfig::SHIPPING_ADDRESS_SOURCE_COMPANY_UNIT_ADDRESS
            && $recurringScheduleShippingAddressChoiceTransfer->getIdCompanyUnitAddress() === $idShippingAddress;
    }
}
