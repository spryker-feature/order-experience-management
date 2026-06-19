<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\OrderExperienceManagement\Reader;

use Generated\Shared\Transfer\RecurringScheduleConditionsTransfer;
use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use SprykerFeature\Client\OrderExperienceManagement\OrderExperienceManagementClientInterface;
use SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig;

class RecurringOrderAttentionBannerReader implements RecurringOrderAttentionBannerReaderInterface
{
    public function __construct(
        protected OrderExperienceManagementClientInterface $subscriptionClient,
        protected OrderExperienceManagementConfig $subscriptionConfig,
    ) {
    }

    /**
     * @return array<string, int>
     */
    public function getAttentionStatusCounts(int $idCustomer): array
    {
        $attentionBannerStatuses = $this->subscriptionConfig->getAttentionBannerStatuses();
        $recurringScheduleConditionsTransfer = (new RecurringScheduleConditionsTransfer())
            ->addCustomerId($idCustomer);

        foreach ($attentionBannerStatuses as $status) {
            $recurringScheduleConditionsTransfer->addStatus($status);
        }

        $recurringScheduleCriteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions($recurringScheduleConditionsTransfer);

        $statusCountCollectionTransfer = $this->subscriptionClient->getRecurringScheduleStatusCountCollection($recurringScheduleCriteriaTransfer);
        $counts = array_fill_keys($attentionBannerStatuses, 0);

        foreach ($statusCountCollectionTransfer->getStatusCounts() as $statusCountTransfer) {
            $counts[$statusCountTransfer->getStatusOrFail()] = $statusCountTransfer->getCountOrFail();
        }

        return $counts;
    }
}
