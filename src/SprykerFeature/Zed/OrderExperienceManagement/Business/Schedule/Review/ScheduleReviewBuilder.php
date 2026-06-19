<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review;

use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Reader\RecurringScheduleReaderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\RecurringSchedulePrePlacementValidatorInterface;

class ScheduleReviewBuilder implements ScheduleReviewBuilderInterface
{
    /**
     * @see \Spryker\Shared\Price\PriceConfig::PRICE_MODE_NET
     */
    protected const string PRICE_MODE_NET = 'NET_MODE';

    public function __construct(
        protected readonly RecurringScheduleReaderInterface $recurringScheduleReader,
        protected readonly RecurringSchedulePrePlacementValidatorInterface $recurringSchedulePrePlacementValidator,
        protected readonly ScheduleReviewMapperInterface $scheduleReviewMapper,
        protected readonly ConfiguredBundleUnavailabilityExpanderInterface $configuredBundleUnavailabilityExpander,
        protected readonly ScheduleReviewSummaryCalculatorInterface $scheduleReviewSummaryCalculator,
    ) {
    }

    public function buildReview(RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer): RecurringScheduleReviewResponseTransfer
    {
        $recurringScheduleTransfer = $this->findSchedule($recurringScheduleCriteriaTransfer);

        if ($recurringScheduleTransfer === null) {
            return new RecurringScheduleReviewResponseTransfer();
        }

        return $this->buildResponseForSchedule($recurringScheduleTransfer);
    }

     /**
      * @param \Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer
      * @param array<\Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer> $acceptedItemReviewTransfers
      */
    public function buildApprovalReview(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer,
        array $acceptedItemReviewTransfers,
    ): RecurringScheduleReviewResponseTransfer {
        $recurringScheduleTransfer = $this->findSchedule($recurringScheduleCriteriaTransfer);

        if ($recurringScheduleTransfer === null) {
            return new RecurringScheduleReviewResponseTransfer();
        }

        $this->reBaselineAcceptedItems($recurringScheduleTransfer, $acceptedItemReviewTransfers);

        return $this->buildResponseForSchedule($recurringScheduleTransfer);
    }

    protected function findSchedule(RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer): ?RecurringScheduleTransfer
    {
        $recurringScheduleCollectionTransfer = $this->recurringScheduleReader
            ->getRecurringScheduleCollection($recurringScheduleCriteriaTransfer);

        return $recurringScheduleCollectionTransfer->getRecurringSchedules()->getIterator()->current();
    }

    protected function buildResponseForSchedule(RecurringScheduleTransfer $recurringScheduleTransfer): RecurringScheduleReviewResponseTransfer
    {
        $recurringScheduleValidationResultTransfer = $this->recurringSchedulePrePlacementValidator
            ->validateRecurringSchedule($recurringScheduleTransfer);

        $recurringScheduleReviewResponseTransfer = $this->scheduleReviewMapper->mapValidationResultToReviewResponse(
            $recurringScheduleTransfer,
            $recurringScheduleValidationResultTransfer,
        );

        // Propagate unavailability across configurable-bundle members so totals reflect the whole bundle
        // being dropped before the summary is calculated.
        $recurringScheduleReviewResponseTransfer = $this->configuredBundleUnavailabilityExpander->expand($recurringScheduleReviewResponseTransfer);

        return $this->scheduleReviewSummaryCalculator->calculate($recurringScheduleReviewResponseTransfer);
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer> $acceptedItemReviewTransfers
     */
    protected function reBaselineAcceptedItems(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        array $acceptedItemReviewTransfers,
    ): void {
        $acceptedPricesByGroupKey = $this->mapAcceptedPricesByGroupKey($acceptedItemReviewTransfers);

        if ($acceptedPricesByGroupKey === []) {
            return;
        }

        $isNetMode = $recurringScheduleTransfer->getPriceMode() === static::PRICE_MODE_NET;

        foreach ($recurringScheduleTransfer->getItems() as $recurringScheduleItemTransfer) {
            $acceptedPrice = $acceptedPricesByGroupKey[$recurringScheduleItemTransfer->getGroupKey()] ?? null;

            if ($acceptedPrice === null) {
                continue;
            }

            $isNetMode
                ? $recurringScheduleItemTransfer->setReferenceNetPrice($acceptedPrice)
                : $recurringScheduleItemTransfer->setReferenceGrossPrice($acceptedPrice);
        }
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer> $acceptedItemReviewTransfers
     *
     * @return array<string, int>
     */
    protected function mapAcceptedPricesByGroupKey(array $acceptedItemReviewTransfers): array
    {
        $acceptedPricesByGroupKey = [];

        foreach ($acceptedItemReviewTransfers as $recurringScheduleItemReviewTransfer) {
            $groupKey = $recurringScheduleItemReviewTransfer->getRecurringScheduleItemOrFail()->getGroupKey();
            $acceptedPrice = $recurringScheduleItemReviewTransfer->getCurrentPrice();

            if ($groupKey === null || $acceptedPrice === null) {
                continue;
            }

            $acceptedPricesByGroupKey[$groupKey] = $acceptedPrice;
        }

        return $acceptedPricesByGroupKey;
    }
}
