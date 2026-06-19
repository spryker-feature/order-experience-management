<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Reader;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\PaginationTransfer;
use Generated\Shared\Transfer\RecurringScheduleCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleConditionsTransfer;
use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use SprykerFeature\Client\OrderExperienceManagement\OrderExperienceManagementClientInterface;

class RecurringScheduleReader implements RecurringScheduleReaderInterface
{
    public function __construct(
        protected OrderExperienceManagementClientInterface $subscriptionClient,
    ) {
    }

    public function findScheduleDetail(
        string $uuid,
        CustomerTransfer $customerTransfer,
        ?PaginationTransfer $historyPaginationTransfer = null,
    ): ?RecurringScheduleTransfer {
        $recurringScheduleCriteriaTransfer = $this->buildDetailCriteria($uuid, $customerTransfer)
            ->setHistoryPagination($historyPaginationTransfer);

        $recurringScheduleCollectionTransfer = $this->subscriptionClient
            ->getRecurringScheduleCollection($recurringScheduleCriteriaTransfer);

        if ($recurringScheduleCollectionTransfer->getRecurringSchedules()->count() === 0) {
            return null;
        }

        return $recurringScheduleCollectionTransfer->getRecurringSchedules()->offsetGet(0);
    }

    public function getScheduleCollection(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer,
    ): RecurringScheduleCollectionTransfer {
        return $this->subscriptionClient->getRecurringScheduleCollection($recurringScheduleCriteriaTransfer);
    }

    public function findScheduleReview(
        string $uuid,
        CustomerTransfer $customerTransfer,
    ): RecurringScheduleReviewResponseTransfer {
        return $this->subscriptionClient->getRecurringScheduleReview(
            $this->buildReviewCriteria($uuid, $customerTransfer),
        );
    }

    protected function buildReviewCriteria(
        string $uuid,
        CustomerTransfer $customerTransfer,
    ): RecurringScheduleCriteriaTransfer {
        $recurringScheduleConditionsTransfer = (new RecurringScheduleConditionsTransfer())
            ->addUuid($uuid)
            ->setIsWithItems(true)
            ->setGroupItemsByGroupKey(true);

        return (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions($recurringScheduleConditionsTransfer)
            ->setCustomer($customerTransfer);
    }

    protected function buildDetailCriteria(
        string $uuid,
        CustomerTransfer $customerTransfer,
    ): RecurringScheduleCriteriaTransfer {
        $recurringScheduleConditionsTransfer = (new RecurringScheduleConditionsTransfer())
            ->addUuid($uuid)
            ->setIsWithItems(true)
            ->setIsWithHistory(true)
            ->setIsWithCustomer(true)
            ->setIsWithSkipPreview(true)
            ->setGroupItemsByGroupKey(true);

        return (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions($recurringScheduleConditionsTransfer)
            ->setCustomer($customerTransfer);
    }
}
