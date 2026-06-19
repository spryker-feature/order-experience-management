<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\OrderExperienceManagement\Form\DataProvider;

use Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderAcceptedItemForm;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderApproveForm;

class RecurringOrderApproveFormDataProvider
{
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
     * @return array<int, array<string, mixed>>
     */
    protected function buildAcceptedItems(
        RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer,
    ): array {
        $acceptedItems = [];

        foreach ($recurringScheduleReviewResponseTransfer->getFlaggedItems() as $recurringScheduleItemReviewTransfer) {
            $groupKey = $recurringScheduleItemReviewTransfer->getRecurringScheduleItemOrFail()->getGroupKey();

            if ($recurringScheduleItemReviewTransfer->getIsPurchasable() === false || $groupKey === null) {
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
