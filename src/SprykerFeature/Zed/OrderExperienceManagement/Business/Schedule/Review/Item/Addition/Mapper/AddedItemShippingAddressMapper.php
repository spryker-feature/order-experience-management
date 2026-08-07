<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Mapper;

use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\CompanyUnitAddressTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\RecurringScheduleShippingAddressChoiceTransfer;

class AddedItemShippingAddressMapper implements AddedItemShippingAddressMapperInterface
{
    public function mapCompanyUnitAddressTransferToAddressTransfer(
        CompanyUnitAddressTransfer $companyUnitAddressTransfer,
        ?CustomerTransfer $customerTransfer,
        AddressTransfer $addressTransfer,
    ): AddressTransfer {
        $addressTransfer
            ->fromArray($companyUnitAddressTransfer->modifiedToArray(), true)
            ->setIsAddressSavingSkipped(true);

        if ($customerTransfer === null) {
            return $addressTransfer;
        }

        return $addressTransfer
            ->setSalutation($addressTransfer->getSalutation() ?? $customerTransfer->getSalutation())
            ->setFirstName($addressTransfer->getFirstName() ?? $customerTransfer->getFirstName())
            ->setLastName($addressTransfer->getLastName() ?? $customerTransfer->getLastName());
    }

    /**
     * @param array<string, mixed> $addressData
     */
    public function mapStoredAddressDataToAddressTransfer(array $addressData, AddressTransfer $addressTransfer): AddressTransfer
    {
        return $addressTransfer
            ->fromArray($addressData, true)
            ->setIdSalesOrderAddress(null)
            ->setIsAddressSavingSkipped(true);
    }

    public function mapAddressTransferToChoiceTransfer(
        AddressTransfer $addressTransfer,
        string $key,
        string $source,
        RecurringScheduleShippingAddressChoiceTransfer $recurringScheduleShippingAddressChoiceTransfer,
    ): RecurringScheduleShippingAddressChoiceTransfer {
        return $recurringScheduleShippingAddressChoiceTransfer
            ->setKey($key)
            ->setSource($source)
            ->setAddress($addressTransfer)
            ->setIdCompanyUnitAddress($addressTransfer->getIdCompanyUnitAddress());
    }
}
