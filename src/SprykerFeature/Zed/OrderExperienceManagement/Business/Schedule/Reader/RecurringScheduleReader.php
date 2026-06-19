<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Reader;

use Generated\Shared\Transfer\RecurringScheduleCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use Generated\Shared\Transfer\RecurringScheduleStatusCountCollectionTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander\RecurringScheduleCustomerExpanderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander\RecurringScheduleHistoryExpanderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander\RecurringScheduleItemExpanderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander\RecurringScheduleQuoteDataExpanderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander\RecurringScheduleSkipPreviewExpanderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Filter\RecurringScheduleAccessFilterInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Grouper\RecurringScheduleItemGrouperInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface;

class RecurringScheduleReader implements RecurringScheduleReaderInterface
{
    public function __construct(
        protected readonly OrderExperienceManagementRepositoryInterface $subscriptionRepository,
        protected readonly RecurringScheduleItemExpanderInterface $recurringScheduleItemExpander,
        protected readonly RecurringScheduleHistoryExpanderInterface $recurringScheduleHistoryExpander,
        protected readonly RecurringScheduleCustomerExpanderInterface $recurringScheduleCustomerExpander,
        protected readonly RecurringScheduleItemGrouperInterface $recurringScheduleItemGrouper,
        protected readonly RecurringScheduleAccessFilterInterface $recurringScheduleAccessFilter,
        protected readonly RecurringScheduleQuoteDataExpanderInterface $recurringScheduleQuoteDataExpander,
        protected readonly RecurringScheduleSkipPreviewExpanderInterface $recurringScheduleSkipPreviewExpander,
    ) {
    }

    public function getRecurringScheduleCollection(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer,
    ): RecurringScheduleCollectionTransfer {
        $recurringScheduleCriteriaTransfer = $this->recurringScheduleAccessFilter->applyAccessFilter($recurringScheduleCriteriaTransfer);

        $recurringScheduleConditionsTransfer = $recurringScheduleCriteriaTransfer->getRecurringScheduleConditions();

        $recurringScheduleCollectionTransfer = $this->subscriptionRepository->getRecurringScheduleCollection($recurringScheduleCriteriaTransfer);

        if ($recurringScheduleConditionsTransfer?->getIsWithItems()) {
            $recurringScheduleCollectionTransfer = $this->recurringScheduleItemExpander->expandWithItems($recurringScheduleCollectionTransfer);
        }

        if ($recurringScheduleConditionsTransfer?->getGroupItemsByGroupKey()) {
            $recurringScheduleCollectionTransfer = $this->recurringScheduleItemGrouper->groupItemsByGroupKey($recurringScheduleCollectionTransfer);
        }

        if ($recurringScheduleConditionsTransfer?->getIsWithHistory()) {
            $recurringScheduleCollectionTransfer = $this->recurringScheduleHistoryExpander->expandWithHistory(
                $recurringScheduleCollectionTransfer,
                $recurringScheduleCriteriaTransfer->getHistoryPagination(),
            );
        }

        if ($recurringScheduleConditionsTransfer?->getIsWithCustomer()) {
            $recurringScheduleCollectionTransfer = $this->recurringScheduleCustomerExpander->expandWithCustomer($recurringScheduleCollectionTransfer);
        }

        if ($recurringScheduleConditionsTransfer?->getIsWithQuoteData()) {
            $recurringScheduleCollectionTransfer = $this->recurringScheduleQuoteDataExpander->expandWithQuoteData($recurringScheduleCollectionTransfer);
        }

        if ($recurringScheduleConditionsTransfer?->getIsWithSkipPreview()) {
            $recurringScheduleCollectionTransfer = $this->recurringScheduleSkipPreviewExpander->expandWithSkipPreview($recurringScheduleCollectionTransfer);
        }

        return $recurringScheduleCollectionTransfer;
    }

    public function getRecurringScheduleStatusCountCollection(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer,
    ): RecurringScheduleStatusCountCollectionTransfer {
        return $this->subscriptionRepository->getRecurringScheduleStatusCountCollection($recurringScheduleCriteriaTransfer);
    }
}
