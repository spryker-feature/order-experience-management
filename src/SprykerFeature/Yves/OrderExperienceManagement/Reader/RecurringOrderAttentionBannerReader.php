<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\OrderExperienceManagement\Reader;

use Generated\Shared\Transfer\RecurringScheduleConditionsTransfer;
use SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig;

class RecurringOrderAttentionBannerReader implements RecurringOrderAttentionBannerReaderInterface
{
    public function __construct(
        protected OrderExperienceManagementConfig $config,
    ) {
    }

    public function buildStatusCountConditions(int $idCustomer): RecurringScheduleConditionsTransfer
    {
        $recurringScheduleConditionsTransfer = (new RecurringScheduleConditionsTransfer())
            ->addCustomerId($idCustomer);

        foreach ($this->config->getAttentionBannerStatuses() as $status) {
            $recurringScheduleConditionsTransfer->addStatus($status);
        }

        return $recurringScheduleConditionsTransfer;
    }

    /**
     * {@inheritDoc}
     */
    public function getAttentionStatusCounts(iterable $recurringScheduleStatusCountTransfers): array
    {
        $counts = array_fill_keys($this->config->getAttentionBannerStatuses(), 0);

        foreach ($recurringScheduleStatusCountTransfers as $recurringScheduleStatusCountTransfer) {
            $counts[$recurringScheduleStatusCountTransfer->getStatusOrFail()] = $recurringScheduleStatusCountTransfer->getCountOrFail();
        }

        return $counts;
    }
}
