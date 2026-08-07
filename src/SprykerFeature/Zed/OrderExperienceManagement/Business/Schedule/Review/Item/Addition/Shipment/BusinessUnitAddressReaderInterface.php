<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;

interface BusinessUnitAddressReaderInterface
{
    /**
     * @return array<int, \Generated\Shared\Transfer\AddressTransfer> Keyed by idCompanyUnitAddress.
     */
    public function getAddressTransfers(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        ?CustomerTransfer $customerTransfer,
    ): array;
}
