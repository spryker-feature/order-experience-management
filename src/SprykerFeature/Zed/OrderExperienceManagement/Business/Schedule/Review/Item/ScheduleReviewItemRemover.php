<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item;

use Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use InvalidArgumentException;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementEntityManagerInterface;

class ScheduleReviewItemRemover implements ScheduleReviewItemRemoverInterface
{
    protected const string ADDRESSING_BUNDLE_ITEM = 'bundle_item';

    protected const string ADDRESSING_CONFIGURED_BUNDLE = 'configured_bundle';

    protected const string ADDRESSING_GROUP = 'group';

    protected const string ADDRESSING_ID = 'id';

    protected const int NEXT_DELIVERY_ZERO_QUANTITY = 0;

    public function __construct(protected readonly OrderExperienceManagementEntityManagerInterface $entityManager)
    {
    }

    public function remove(RecurringScheduleItemReviewTransfer $recurringScheduleItemReviewTransfer, int $idRecurringSchedule): void
    {
        $addressing = $this->resolveGroupAddressing($recurringScheduleItemReviewTransfer->getRecurringScheduleItemOrFail());

        if ($addressing === null) {
            return;
        }

        [$addressingType, $addressingValue] = $addressing;

        match ($addressingType) {
            static::ADDRESSING_BUNDLE_ITEM => $this->entityManager->deleteRecurringScheduleItemsByBundleItemIdentifier($idRecurringSchedule, $addressingValue),
            static::ADDRESSING_CONFIGURED_BUNDLE => $this->entityManager->deleteRecurringScheduleItemsByConfiguredBundleGroupKey($idRecurringSchedule, $addressingValue),
            static::ADDRESSING_GROUP => $this->entityManager->deleteRecurringScheduleItemsByGroupKey($idRecurringSchedule, $addressingValue),
            static::ADDRESSING_ID => $this->entityManager->deleteRecurringScheduleItem((int)$addressingValue),
            default => throw new InvalidArgumentException(sprintf('Unsupported schedule item addressing type "%s".', $addressingType)),
        };
    }

    public function skipForNextOrder(RecurringScheduleItemReviewTransfer $recurringScheduleItemReviewTransfer, int $idRecurringSchedule): void
    {
        $addressing = $this->resolveGroupAddressing($recurringScheduleItemReviewTransfer->getRecurringScheduleItemOrFail());

        if ($addressing === null) {
            return;
        }

        [$addressingType, $addressingValue] = $addressing;

        match ($addressingType) {
            static::ADDRESSING_BUNDLE_ITEM => $this->entityManager->updateNextDeliveryQuantityToZeroByBundleItemIdentifier($idRecurringSchedule, $addressingValue),
            static::ADDRESSING_CONFIGURED_BUNDLE => $this->entityManager->updateNextDeliveryQuantityToZeroByConfiguredBundleGroupKey($idRecurringSchedule, $addressingValue),
            static::ADDRESSING_GROUP => $this->entityManager->updateNextDeliveryQuantityToZeroByGroupKey($idRecurringSchedule, $addressingValue),
            static::ADDRESSING_ID => $this->entityManager->updateRecurringScheduleItemNextDeliveryQuantity((int)$addressingValue, static::NEXT_DELIVERY_ZERO_QUANTITY),
            default => throw new InvalidArgumentException(sprintf('Unsupported schedule item addressing type "%s".', $addressingType)),
        };
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    protected function resolveGroupAddressing(RecurringScheduleItemTransfer $recurringScheduleItemTransfer): ?array
    {
        $bundleItemIdentifier = $recurringScheduleItemTransfer->getBundleItemIdentifier();

        if ($bundleItemIdentifier !== null) {
            return [static::ADDRESSING_BUNDLE_ITEM, $bundleItemIdentifier];
        }

        $configuredBundleGroupKey = $recurringScheduleItemTransfer->getConfiguredBundleGroupKey();

        if ($configuredBundleGroupKey !== null) {
            return [static::ADDRESSING_CONFIGURED_BUNDLE, $configuredBundleGroupKey];
        }

        $groupKey = $recurringScheduleItemTransfer->getGroupKey();

        if ($groupKey !== null) {
            return [static::ADDRESSING_GROUP, $groupKey];
        }

        $idRecurringScheduleItem = $recurringScheduleItemTransfer->getIdRecurringScheduleItem();

        if ($idRecurringScheduleItem !== null) {
            return [static::ADDRESSING_ID, (string)$idRecurringScheduleItem];
        }

        return null;
    }
}
