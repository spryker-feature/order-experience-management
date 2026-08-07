<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment;

use Generated\Shared\Transfer\AddressTransfer;
use Spryker\Service\Customer\CustomerServiceInterface;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;

class ShippingAddressChoiceKeyGenerator implements ShippingAddressChoiceKeyGeneratorInterface
{
    public function __construct(protected readonly CustomerServiceInterface $customerService)
    {
    }

    public function generateCompanyUnitAddressKey(int $idCompanyUnitAddress): string
    {
        return $this->generateKey(
            SharedOrderExperienceManagementConfig::SHIPPING_ADDRESS_SOURCE_COMPANY_UNIT_ADDRESS,
            (string)$idCompanyUnitAddress,
        );
    }

    public function generateScheduleAddressKey(AddressTransfer $addressTransfer): string
    {
        return $this->generateKey(
            SharedOrderExperienceManagementConfig::SHIPPING_ADDRESS_SOURCE_SCHEDULE,
            $this->customerService->getUniqueAddressKey($addressTransfer),
        );
    }

    protected function generateKey(string $source, string $identifier): string
    {
        return $source . SharedOrderExperienceManagementConfig::SHIPPING_ADDRESS_KEY_SEPARATOR . $identifier;
    }
}
