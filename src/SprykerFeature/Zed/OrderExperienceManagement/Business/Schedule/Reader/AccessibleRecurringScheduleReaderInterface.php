<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Reader;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;

interface AccessibleRecurringScheduleReaderInterface
{
    public function findAccessibleScheduleByUuid(
        string $uuid,
        int $idCustomer,
        ?CustomerTransfer $customerTransfer = null,
    ): ?RecurringScheduleTransfer;
}
