<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment;

use Generated\Shared\Transfer\AddressTransfer;

class OfferedShippingAddressChecker implements OfferedShippingAddressCheckerInterface
{
    protected const string POSTAL_PART_SEPARATOR = '|';

    /**
     * @param array<string, \Generated\Shared\Transfer\RecurringScheduleShippingAddressChoiceTransfer> $choiceTransfers
     */
    public function isAlreadyOffered(AddressTransfer $addressTransfer, array $choiceTransfers): bool
    {
        foreach ($choiceTransfers as $choiceTransfer) {
            $offeredAddressTransfer = $choiceTransfer->getAddress();

            if ($offeredAddressTransfer === null) {
                continue;
            }

            if ($this->isSameCompanyUnitAddress($addressTransfer, $offeredAddressTransfer)) {
                return true;
            }

            if ($this->isSamePostalPlace($addressTransfer, $offeredAddressTransfer)) {
                return true;
            }
        }

        return false;
    }

    protected function isSameCompanyUnitAddress(
        AddressTransfer $addressTransfer,
        AddressTransfer $offeredAddressTransfer,
    ): bool {
        $idCompanyUnitAddress = $addressTransfer->getIdCompanyUnitAddress();

        return $idCompanyUnitAddress !== null
            && $idCompanyUnitAddress === $offeredAddressTransfer->getIdCompanyUnitAddress();
    }

    protected function isSamePostalPlace(
        AddressTransfer $addressTransfer,
        AddressTransfer $offeredAddressTransfer,
    ): bool {
        return $this->buildPostalFingerprint($addressTransfer) === $this->buildPostalFingerprint($offeredAddressTransfer);
    }

    protected function buildPostalFingerprint(AddressTransfer $addressTransfer): string
    {
        $postalParts = [
            $addressTransfer->getAddress1(),
            $addressTransfer->getAddress2(),
            $addressTransfer->getAddress3(),
            $addressTransfer->getZipCode(),
            $addressTransfer->getCity(),
            $addressTransfer->getIso2Code(),
        ];

        return implode(static::POSTAL_PART_SEPARATOR, array_map(
            static fn (?string $postalPart): string => mb_strtolower(trim((string)$postalPart)),
            $postalParts,
        ));
    }
}
