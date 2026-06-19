<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review;

use Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer;
use Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\RecurringScheduleValidationResultTransfer;

class ScheduleReviewMapper implements ScheduleReviewMapperInterface
{
    public function mapValidationResultToReviewResponse(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        RecurringScheduleValidationResultTransfer $recurringScheduleValidationResultTransfer,
    ): RecurringScheduleReviewResponseTransfer {
        $recurringScheduleReviewResponseTransfer = (new RecurringScheduleReviewResponseTransfer())
            ->setRecurringSchedule($recurringScheduleTransfer);

        foreach ($recurringScheduleValidationResultTransfer->getBlockingErrors() as $recurringScheduleErrorTransfer) {
            $recurringScheduleReviewResponseTransfer->addBlockingError($recurringScheduleErrorTransfer);
        }

        $itemReviewsByItemId = $this->indexItemReviewsByItemId($recurringScheduleValidationResultTransfer);
        $this->partitionItems($recurringScheduleTransfer, $itemReviewsByItemId, $recurringScheduleReviewResponseTransfer);

        return $recurringScheduleReviewResponseTransfer;
    }

    /**
     * @return array<int, \Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer>
     */
    protected function indexItemReviewsByItemId(
        RecurringScheduleValidationResultTransfer $recurringScheduleValidationResultTransfer,
    ): array {
        $itemReviewsByItemId = [];

        foreach ($recurringScheduleValidationResultTransfer->getItemReviews() as $recurringScheduleItemReviewTransfer) {
            $idRecurringScheduleItem = $recurringScheduleItemReviewTransfer->getRecurringScheduleItemOrFail()->getIdRecurringScheduleItem();

            if ($idRecurringScheduleItem === null) {
                continue;
            }

            if (!isset($itemReviewsByItemId[$idRecurringScheduleItem])) {
                $itemReviewsByItemId[$idRecurringScheduleItem] = $recurringScheduleItemReviewTransfer;

                continue;
            }

            $this->mergeItemReview($itemReviewsByItemId[$idRecurringScheduleItem], $recurringScheduleItemReviewTransfer);
        }

        return $itemReviewsByItemId;
    }

    protected function mergeItemReview(
        RecurringScheduleItemReviewTransfer $targetItemReviewTransfer,
        RecurringScheduleItemReviewTransfer $sourceItemReviewTransfer,
    ): void {
        foreach ($sourceItemReviewTransfer->getReviewReasons() as $reviewReason) {
            $targetItemReviewTransfer->addReviewReason($reviewReason);
        }

        if ($targetItemReviewTransfer->getPreviousPrice() === null) {
            $targetItemReviewTransfer->setPreviousPrice($sourceItemReviewTransfer->getPreviousPrice());
        }

        if ($targetItemReviewTransfer->getCurrentPrice() === null) {
            $targetItemReviewTransfer->setCurrentPrice($sourceItemReviewTransfer->getCurrentPrice());
        }

        if ($sourceItemReviewTransfer->getIsPurchasable() === false) {
            $targetItemReviewTransfer->setIsPurchasable(false);
        }
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer> $itemReviewsByItemId
     */
    protected function partitionItems(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        array $itemReviewsByItemId,
        RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer,
    ): void {
        foreach ($recurringScheduleTransfer->getItems() as $recurringScheduleItemTransfer) {
            $recurringScheduleItemReviewTransfer = $itemReviewsByItemId[$recurringScheduleItemTransfer->getIdRecurringScheduleItem()] ?? null;

            if ($recurringScheduleItemReviewTransfer === null) {
                $recurringScheduleReviewResponseTransfer->addUnchangedItem($recurringScheduleItemTransfer);

                continue;
            }

            $recurringScheduleItemReviewTransfer->setRecurringScheduleItem($recurringScheduleItemTransfer);
            $recurringScheduleReviewResponseTransfer->addFlaggedItem($recurringScheduleItemReviewTransfer);
        }
    }
}
