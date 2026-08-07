<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\OrderExperienceManagement\Form\DataProvider;

use Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderAcceptedItemForm;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderApproveForm;
use SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig;

class RecurringOrderApproveFormDataProvider
{
    public function __construct(protected readonly OrderExperienceManagementConfig $config)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(
        string $uuid,
        RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer,
    ): array {
        return [
            RecurringOrderApproveForm::FIELD_UUID => $uuid,
            RecurringOrderApproveForm::FIELD_ACCEPTED_ITEMS => $this->buildAcceptedItems($recurringScheduleReviewResponseTransfer),
        ];
    }

    /**
     * Seeds one row per flagged line. Lines that cannot be kept are pre-marked as removed, so approval always
     * drops them: this covers substituted/discontinued lines (which stay purchasable but are being replaced —
     * whether or not a substitute is available or confirmed) and any non-purchasable line (e.g. unavailable
     * products or unavailable configured bundles). A chosen substitute is added separately via the added-items
     * collection. Remaining purchasable lines carry the accepted price. Unchanged lines are read-only on the
     * review page and are not seeded. The scope has no default and the price is not editable here: accepting the
     * flagged price becomes a change only when the buyer chooses a scope.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function buildAcceptedItems(
        RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer,
    ): array {
        $acceptedItems = [];
        $substitutableReviewReasons = $this->config->getSubstitutableReviewReasons();

        foreach ($recurringScheduleReviewResponseTransfer->getFlaggedItems() as $recurringScheduleItemReviewTransfer) {
            $groupKey = $recurringScheduleItemReviewTransfer->getRecurringScheduleItemOrFail()->getGroupKey();

            if ($groupKey === null) {
                continue;
            }

            $isSubstitutable = array_intersect($recurringScheduleItemReviewTransfer->getReviewReasons(), $substitutableReviewReasons) !== [];

            if ($isSubstitutable || $recurringScheduleItemReviewTransfer->getIsPurchasable() === false) {
                $acceptedItems[] = [
                    RecurringOrderAcceptedItemForm::FIELD_GROUP_KEY => $groupKey,
                    RecurringOrderAcceptedItemForm::FIELD_IS_REMOVED => true,
                ];

                continue;
            }

            $acceptedItems[] = [
                RecurringOrderAcceptedItemForm::FIELD_GROUP_KEY => $groupKey,
                RecurringOrderAcceptedItemForm::FIELD_PRICE => $recurringScheduleItemReviewTransfer->getCurrentPrice(),
            ];
        }

        return $acceptedItems;
    }
}
