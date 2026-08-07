<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\OrderExperienceManagement\Reader;

use Generated\Shared\Transfer\RecurringScheduleConditionsTransfer;

interface RecurringOrderAttentionBannerReaderInterface
{
    public function buildStatusCountConditions(int $idCustomer): RecurringScheduleConditionsTransfer;

    /**
     * @param iterable<\Generated\Shared\Transfer\RecurringScheduleStatusCountTransfer> $recurringScheduleStatusCountTransfers
     *
     * @return array<string, int>
     */
    public function getAttentionStatusCounts(iterable $recurringScheduleStatusCountTransfers): array;
}
