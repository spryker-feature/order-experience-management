<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment;

use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\CompanyUnitAddressCriteriaFilterTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Spryker\Zed\CompanyUnitAddress\Business\CompanyUnitAddressFacadeInterface;
use Spryker\Zed\CompanyUser\Business\CompanyUserFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Mapper\AddedItemShippingAddressMapperInterface;

class BusinessUnitAddressReader implements BusinessUnitAddressReaderInterface
{
    public function __construct(
        protected readonly CompanyUserFacadeInterface $companyUserFacade,
        protected readonly CompanyUnitAddressFacadeInterface $companyUnitAddressFacade,
        protected readonly AddedItemShippingAddressMapperInterface $addedItemShippingAddressMapper,
    ) {
    }

    /**
     * @return array<int, \Generated\Shared\Transfer\AddressTransfer>
     */
    public function getAddressTransfers(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        ?CustomerTransfer $customerTransfer,
    ): array {
        $idCompanyBusinessUnit = $this->findIdCompanyBusinessUnit($recurringScheduleTransfer);

        if ($idCompanyBusinessUnit === null) {
            return [];
        }

        $companyUnitAddressCollectionTransfer = $this->companyUnitAddressFacade->getCompanyUnitAddressCollection(
            (new CompanyUnitAddressCriteriaFilterTransfer())->setIdCompanyBusinessUnit($idCompanyBusinessUnit),
        );

        $addressTransfers = [];

        foreach ($companyUnitAddressCollectionTransfer->getCompanyUnitAddresses() as $companyUnitAddressTransfer) {
            $idCompanyUnitAddress = $companyUnitAddressTransfer->getIdCompanyUnitAddress();

            if ($idCompanyUnitAddress === null) {
                continue;
            }

            $addressTransfers[$idCompanyUnitAddress] = $this->addedItemShippingAddressMapper
                ->mapCompanyUnitAddressTransferToAddressTransfer(
                    $companyUnitAddressTransfer,
                    $customerTransfer,
                    new AddressTransfer(),
                );
        }

        return $addressTransfers;
    }

    protected function findIdCompanyBusinessUnit(RecurringScheduleTransfer $recurringScheduleTransfer): ?int
    {
        $idCompanyUser = $recurringScheduleTransfer->getIdCompanyUser();

        if ($idCompanyUser === null) {
            return null;
        }

        return $this->companyUserFacade->findCompanyUserById($idCompanyUser)
            ?->getCompanyBusinessUnit()
            ?->getIdCompanyBusinessUnit();
    }
}
