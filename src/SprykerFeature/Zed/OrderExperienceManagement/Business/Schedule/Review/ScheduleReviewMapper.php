<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review;

use Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer;
use Generated\Shared\Transfer\RecurringScheduleOrderReviewTransfer;
use Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\RecurringScheduleValidationResultTransfer;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;

class ScheduleReviewMapper implements ScheduleReviewMapperInterface
{
    public function mapValidationResultToReviewResponse(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        RecurringScheduleValidationResultTransfer $recurringScheduleValidationResultTransfer,
    ): RecurringScheduleReviewResponseTransfer {
        $recurringScheduleReviewResponseTransfer = (new RecurringScheduleReviewResponseTransfer())
            ->setRecurringSchedule($recurringScheduleTransfer)
            ->setOrderReview($recurringScheduleValidationResultTransfer->getOrderReview());

        foreach ($recurringScheduleValidationResultTransfer->getUnflaggedPriceChanges() as $unflaggedPriceChangeTransfer) {
            $recurringScheduleReviewResponseTransfer->addUnflaggedPriceChange($unflaggedPriceChangeTransfer);
        }

        foreach ($recurringScheduleValidationResultTransfer->getBlockingErrors() as $recurringScheduleErrorTransfer) {
            $recurringScheduleReviewResponseTransfer->addBlockingError($recurringScheduleErrorTransfer);
        }

        $itemReviewsByItemId = $this->indexItemReviewsByItemId($recurringScheduleValidationResultTransfer);
        $itemReviewsByItemId = $this->flagItemsRaisingTheOrderTotal($itemReviewsByItemId, $recurringScheduleValidationResultTransfer->getOrderReview());
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

    /**
     * @param array<int, \Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer> $itemReviewsByItemId
     *
     * @return array<int, \Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer>
     */
    protected function flagItemsRaisingTheOrderTotal(
        array $itemReviewsByItemId,
        ?RecurringScheduleOrderReviewTransfer $recurringScheduleOrderReviewTransfer,
    ): array {
        if ($recurringScheduleOrderReviewTransfer === null) {
            return $itemReviewsByItemId;
        }

        $recurringScheduleOrderReviewTransfer->setIsOrderTotalOnly(!$this->hasPriceIncreasedReview($itemReviewsByItemId));

        foreach ($recurringScheduleOrderReviewTransfer->getDriftedItems() as $driftedItemReviewTransfer) {
            $idRecurringScheduleItem = $driftedItemReviewTransfer->getRecurringScheduleItemOrFail()->getIdRecurringScheduleItem();

            if ($idRecurringScheduleItem === null || $this->hasPriceIncreasedReview([$itemReviewsByItemId[$idRecurringScheduleItem] ?? new RecurringScheduleItemReviewTransfer()])) {
                continue;
            }

            $itemReviewsByItemId[$idRecurringScheduleItem] = ($itemReviewsByItemId[$idRecurringScheduleItem] ?? new RecurringScheduleItemReviewTransfer())
                ->setRecurringScheduleItem($driftedItemReviewTransfer->getRecurringScheduleItemOrFail())
                ->setPreviousPrice($driftedItemReviewTransfer->getPreviousPrice())
                ->setCurrentPrice($driftedItemReviewTransfer->getCurrentPrice())
                ->setIsFlaggedByOrderTotal(true)
                ->addReviewReason(SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED);
        }

        return $itemReviewsByItemId;
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer> $itemReviewTransfers
     */
    protected function hasPriceIncreasedReview(array $itemReviewTransfers): bool
    {
        foreach ($itemReviewTransfers as $itemReviewTransfer) {
            if (in_array(SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED, $itemReviewTransfer->getReviewReasons(), true)) {
                return true;
            }
        }

        return false;
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
