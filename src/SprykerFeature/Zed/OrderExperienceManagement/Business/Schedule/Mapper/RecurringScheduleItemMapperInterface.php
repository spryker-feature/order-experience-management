<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Mapper;

use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;

interface RecurringScheduleItemMapperInterface
{
    /**
     * @param array<string, array{idShipmentMethod: int, unitGrossPrice: int, unitNetPrice: int}> $shipmentDataByShipmentTypeUuid
     */
    public function mapItemToRecurringScheduleItem(
        ItemTransfer $itemTransfer,
        int $fkRecurringSchedule,
        array $shipmentDataByShipmentTypeUuid,
    ): RecurringScheduleItemTransfer;
}
