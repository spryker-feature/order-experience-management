<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review;

use ArrayObject;
use Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer;
use Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;

class ConfiguredBundleUnavailabilityExpander implements ConfiguredBundleUnavailabilityExpanderInterface
{
    public function expand(
        RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer,
    ): RecurringScheduleReviewResponseTransfer {
        $unavailableConfiguredBundleGroupKeys = $this->collectUnavailableConfiguredBundleGroupKeys($recurringScheduleReviewResponseTransfer);

        if ($unavailableConfiguredBundleGroupKeys === []) {
            return $recurringScheduleReviewResponseTransfer;
        }

        $recurringScheduleReviewResponseTransfer = $this->markFlaggedMembersUnavailable($recurringScheduleReviewResponseTransfer, $unavailableConfiguredBundleGroupKeys);

        return $this->flagUnchangedMembers($recurringScheduleReviewResponseTransfer, $unavailableConfiguredBundleGroupKeys);
    }

    /**
     * @return array<string, true>
     */
    protected function collectUnavailableConfiguredBundleGroupKeys(
        RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer,
    ): array {
        $unavailableConfiguredBundleGroupKeys = [];

        foreach ($recurringScheduleReviewResponseTransfer->getFlaggedItems() as $recurringScheduleItemReviewTransfer) {
            if ($recurringScheduleItemReviewTransfer->getIsPurchasable() !== false) {
                continue;
            }

            $configuredBundleGroupKey = $recurringScheduleItemReviewTransfer->getRecurringScheduleItemOrFail()->getConfiguredBundleGroupKey();

            if ($configuredBundleGroupKey === null) {
                continue;
            }

            $unavailableConfiguredBundleGroupKeys[$configuredBundleGroupKey] = true;
        }

        return $unavailableConfiguredBundleGroupKeys;
    }

    /**
     * @param array<string, true> $unavailableConfiguredBundleGroupKeys
     */
    protected function markFlaggedMembersUnavailable(
        RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer,
        array $unavailableConfiguredBundleGroupKeys,
    ): RecurringScheduleReviewResponseTransfer {
        foreach ($recurringScheduleReviewResponseTransfer->getFlaggedItems() as $recurringScheduleItemReviewTransfer) {
            $configuredBundleGroupKey = $recurringScheduleItemReviewTransfer->getRecurringScheduleItemOrFail()->getConfiguredBundleGroupKey();

            if ($configuredBundleGroupKey === null || !isset($unavailableConfiguredBundleGroupKeys[$configuredBundleGroupKey])) {
                continue;
            }

            $this->markUnavailable($recurringScheduleItemReviewTransfer);
        }

        return $recurringScheduleReviewResponseTransfer;
    }

    /**
     * @param array<string, true> $unavailableConfiguredBundleGroupKeys
     */
    protected function flagUnchangedMembers(
        RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer,
        array $unavailableConfiguredBundleGroupKeys,
    ): RecurringScheduleReviewResponseTransfer {
        $remainingUnchangedItemTransfers = new ArrayObject();

        foreach ($recurringScheduleReviewResponseTransfer->getUnchangedItems() as $recurringScheduleItemTransfer) {
            $configuredBundleGroupKey = $recurringScheduleItemTransfer->getConfiguredBundleGroupKey();

            if ($configuredBundleGroupKey === null || !isset($unavailableConfiguredBundleGroupKeys[$configuredBundleGroupKey])) {
                $remainingUnchangedItemTransfers->append($recurringScheduleItemTransfer);

                continue;
            }

            $recurringScheduleReviewResponseTransfer->addFlaggedItem(
                $this->markUnavailable(
                    (new RecurringScheduleItemReviewTransfer())->setRecurringScheduleItem($recurringScheduleItemTransfer),
                ),
            );
        }

        $recurringScheduleReviewResponseTransfer->setUnchangedItems($remainingUnchangedItemTransfers);

        return $recurringScheduleReviewResponseTransfer;
    }

    protected function markUnavailable(
        RecurringScheduleItemReviewTransfer $recurringScheduleItemReviewTransfer,
    ): RecurringScheduleItemReviewTransfer {
        $recurringScheduleItemReviewTransfer->setIsPurchasable(false);

        if (!in_array(SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_CONFIGURABLE_BUNDLE_UNAVAILABLE, $recurringScheduleItemReviewTransfer->getReviewReasons(), true)) {
            $recurringScheduleItemReviewTransfer->addReviewReason(SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_CONFIGURABLE_BUNDLE_UNAVAILABLE);
        }

        return $recurringScheduleItemReviewTransfer;
    }
}
