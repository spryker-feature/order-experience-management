<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item;

interface AcceptedItemReviewMapperInterface
{
    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer> $acceptedItemReviewTransfers
     *
     * @return array<string, int>
     */
    public function mapAcceptedPricesByGroupKey(array $acceptedItemReviewTransfers): array;

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer> $acceptedItemReviewTransfers
     *
     * @return array<string, int>
     */
    public function mapAcceptedQuantitiesByGroupKey(array $acceptedItemReviewTransfers): array;

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer> $acceptedItemReviewTransfers
     *
     * @return array<string>
     */
    public function mapRemovedGroupKeys(array $acceptedItemReviewTransfers): array;
}
