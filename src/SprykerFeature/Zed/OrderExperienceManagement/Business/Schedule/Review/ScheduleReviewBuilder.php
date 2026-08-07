<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review;

use ArrayObject;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Reader\RecurringScheduleReaderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\AcceptedItemReviewMapperInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment\ScheduleShippingAddressChoiceReaderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Quote\RecurringScheduleQuoteDataMergerInterface;
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
        protected readonly AcceptedItemReviewMapperInterface $acceptedItemReviewMapper,
        protected readonly RecurringScheduleQuoteDataMergerInterface $recurringScheduleQuoteDataMerger,
        protected readonly ScheduleShippingAddressChoiceReaderInterface $scheduleShippingAddressChoiceReader,
    ) {
    }

    public function buildReview(RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer): RecurringScheduleReviewResponseTransfer
    {
        $recurringScheduleTransfer = $this->recurringScheduleReader->findRecurringScheduleByCriteria($recurringScheduleCriteriaTransfer);

        if ($recurringScheduleTransfer === null) {
            return new RecurringScheduleReviewResponseTransfer();
        }

        if (!$this->isAwaitingReview($recurringScheduleTransfer)) {
            return $this->createScheduleOnlyResponse($recurringScheduleTransfer);
        }

        return $this->buildResponseForSchedule(
            $recurringScheduleTransfer,
            $this->scheduleShippingAddressChoiceReader->getChoices($recurringScheduleTransfer),
        );
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer> $acceptedItemReviewTransfers
     */
    public function buildApprovalReview(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer,
        array $acceptedItemReviewTransfers,
        ?QuoteTransfer $quoteOverrideTransfer = null,
    ): RecurringScheduleReviewResponseTransfer {
        $recurringScheduleTransfer = $this->recurringScheduleReader->findRecurringScheduleByCriteria($recurringScheduleCriteriaTransfer);

        if ($recurringScheduleTransfer === null) {
            return new RecurringScheduleReviewResponseTransfer();
        }

        if (!$this->isAwaitingReview($recurringScheduleTransfer)) {
            return $this->createScheduleOnlyResponse($recurringScheduleTransfer);
        }

        $shippingAddressChoiceTransfers = $this->scheduleShippingAddressChoiceReader->getChoices($recurringScheduleTransfer);

        $recurringScheduleTransfer = $this->applyRemovals($recurringScheduleTransfer, $acceptedItemReviewTransfers);
        $recurringScheduleTransfer = $this->reBaselineAcceptedItems($recurringScheduleTransfer, $acceptedItemReviewTransfers);
        $recurringScheduleTransfer = $this->reBaselineAcceptedQuantities($recurringScheduleTransfer, $acceptedItemReviewTransfers);

        $recurringScheduleTransfer = $this->recurringScheduleQuoteDataMerger->applyQuoteOverride(
            $recurringScheduleTransfer,
            $quoteOverrideTransfer,
        );

        return $this->buildResponseForSchedule($recurringScheduleTransfer, $shippingAddressChoiceTransfers);
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer> $acceptedItemReviewTransfers
     */
    protected function applyRemovals(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        array $acceptedItemReviewTransfers,
    ): RecurringScheduleTransfer {
        $removedGroupKeys = $this->acceptedItemReviewMapper->mapRemovedGroupKeys($acceptedItemReviewTransfers);

        if ($removedGroupKeys === []) {
            return $recurringScheduleTransfer;
        }

        $removedGroupKeyMap = array_flip($removedGroupKeys);
        $remainingItems = new ArrayObject();

        foreach ($recurringScheduleTransfer->getItems() as $recurringScheduleItemTransfer) {
            if (isset($removedGroupKeyMap[$recurringScheduleItemTransfer->getGroupKey()])) {
                continue;
            }

            $remainingItems->append($recurringScheduleItemTransfer);
        }

        return $recurringScheduleTransfer->setItems($remainingItems);
    }

    protected function isAwaitingReview(RecurringScheduleTransfer $recurringScheduleTransfer): bool
    {
        return $recurringScheduleTransfer->getStatus() === SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED;
    }

    protected function createScheduleOnlyResponse(RecurringScheduleTransfer $recurringScheduleTransfer): RecurringScheduleReviewResponseTransfer
    {
        return (new RecurringScheduleReviewResponseTransfer())->setRecurringSchedule($recurringScheduleTransfer);
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleShippingAddressChoiceTransfer> $shippingAddressChoiceTransfers
     */
    protected function buildResponseForSchedule(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        array $shippingAddressChoiceTransfers,
    ): RecurringScheduleReviewResponseTransfer {
        $recurringScheduleValidationResultTransfer = $this->recurringSchedulePrePlacementValidator
            ->validateRecurringSchedule($recurringScheduleTransfer);

        $recurringScheduleReviewResponseTransfer = $this->scheduleReviewMapper->mapValidationResultToReviewResponse(
            $recurringScheduleTransfer,
            $recurringScheduleValidationResultTransfer,
        );

        $recurringScheduleReviewResponseTransfer = $this->configuredBundleUnavailabilityExpander->expand($recurringScheduleReviewResponseTransfer);
        $recurringScheduleReviewResponseTransfer->setShippingAddressChoices(new ArrayObject($shippingAddressChoiceTransfers));

        return $this->scheduleReviewSummaryCalculator->calculate($recurringScheduleReviewResponseTransfer);
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer> $acceptedItemReviewTransfers
     */
    protected function reBaselineAcceptedItems(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        array $acceptedItemReviewTransfers,
    ): RecurringScheduleTransfer {
        $acceptedPricesByGroupKey = $this->acceptedItemReviewMapper->mapAcceptedPricesByGroupKey($acceptedItemReviewTransfers);

        if ($acceptedPricesByGroupKey === []) {
            return $recurringScheduleTransfer;
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

        return $recurringScheduleTransfer;
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer> $acceptedItemReviewTransfers
     */
    protected function reBaselineAcceptedQuantities(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        array $acceptedItemReviewTransfers,
    ): RecurringScheduleTransfer {
        $acceptedQuantitiesByGroupKey = $this->acceptedItemReviewMapper->mapAcceptedQuantitiesByGroupKey($acceptedItemReviewTransfers);

        if ($acceptedQuantitiesByGroupKey === []) {
            return $recurringScheduleTransfer;
        }

        foreach ($recurringScheduleTransfer->getItems() as $recurringScheduleItemTransfer) {
            $acceptedQuantity = $acceptedQuantitiesByGroupKey[$recurringScheduleItemTransfer->getGroupKey()] ?? null;

            if ($acceptedQuantity === null) {
                continue;
            }

            $recurringScheduleItemTransfer->setQuantity($acceptedQuantity);
        }

        return $recurringScheduleTransfer;
    }
}
