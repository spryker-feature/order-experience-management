<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Mapper;

use Generated\Shared\Transfer\AddressTransfer;

class OrderAddressMapper implements OrderAddressMapperInterface
{
    /**
     * @return array<string, string|null>
     */
    public function mapAddressTransferToArray(?AddressTransfer $addressTransfer): array
    {
        if ($addressTransfer === null) {
            return [];
        }

        return [
            'salutation' => $addressTransfer->getSalutation(),
            'firstName' => $addressTransfer->getFirstName(),
            'lastName' => $addressTransfer->getLastName(),
            'company' => $addressTransfer->getCompany(),
            'address1' => $addressTransfer->getAddress1(),
            'address2' => $addressTransfer->getAddress2(),
            'address3' => $addressTransfer->getAddress3(),
            'zipCode' => $addressTransfer->getZipCode(),
            'city' => $addressTransfer->getCity(),
            'iso2Code' => $addressTransfer->getIso2Code(),
            'phone' => $addressTransfer->getPhone(),
        ];
    }
}
