<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Writer;

use ArrayObject;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\RecurringScheduleCollectionRequestTransfer;
use Generated\Shared\Transfer\RecurringScheduleCollectionResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleConditionsTransfer;
use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Mapper\RecurringScheduleMapperInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Reader\RecurringScheduleReaderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Quote\RecurringScheduleQuoteDataMergerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementEntityManagerInterface;

class ScheduleUpdater implements ScheduleUpdaterInterface
{
    protected const string GLOSSARY_KEY_SCHEDULE_NOT_FOUND = 'recurring_orders.detail.edit.error.schedule_not_found';

    protected const string PARAMETER_UUID = '%uuid%';

    public function __construct(
        protected RecurringScheduleReaderInterface $recurringScheduleReader,
        protected RecurringScheduleQuoteDataMergerInterface $recurringScheduleQuoteDataMerger,
        protected RecurringScheduleMapperInterface $recurringScheduleMapper,
        protected OrderExperienceManagementEntityManagerInterface $entityManager,
    ) {
    }

    public function updateRecurringScheduleCollection(
        RecurringScheduleCollectionRequestTransfer $recurringScheduleCollectionRequestTransfer,
    ): RecurringScheduleCollectionResponseTransfer {
        $recurringScheduleCollectionResponseTransfer = new RecurringScheduleCollectionResponseTransfer();
        $requestedScheduleTransfers = $recurringScheduleCollectionRequestTransfer->getRecurringSchedules();

        if ($requestedScheduleTransfers->count() === 0) {
            return $recurringScheduleCollectionResponseTransfer;
        }

        $existingScheduleTransfers = $this->getExistingRecurringSchedulesIndexedByUuid(
            $requestedScheduleTransfers,
            $recurringScheduleCollectionRequestTransfer->getCustomerOrFail(),
        );

        foreach ($requestedScheduleTransfers as $requestedScheduleTransfer) {
            $uuid = $requestedScheduleTransfer->getUuidOrFail();
            $existingScheduleTransfer = $existingScheduleTransfers[$uuid] ?? null;

            if ($existingScheduleTransfer === null) {
                $recurringScheduleCollectionResponseTransfer->addError(
                    (new ErrorTransfer())
                        ->setMessage(static::GLOSSARY_KEY_SCHEDULE_NOT_FOUND)
                        ->setEntityIdentifier($uuid)
                        ->setParameters([static::PARAMETER_UUID => $uuid]),
                );

                continue;
            }

            $updateScheduleTransfer = $this->applyQuoteOverride(
                $this->recurringScheduleMapper->mapRequestedRecurringScheduleToRecurringSchedule(
                    $requestedScheduleTransfer,
                    $existingScheduleTransfer,
                    new RecurringScheduleTransfer(),
                ),
                $requestedScheduleTransfer,
                $existingScheduleTransfer,
            );

            $recurringScheduleCollectionResponseTransfer->addRecurringSchedule(
                $this->entityManager->updateRecurringSchedule($updateScheduleTransfer),
            );
        }

        return $recurringScheduleCollectionResponseTransfer;
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\RecurringScheduleTransfer> $requestedScheduleTransfers
     *
     * @return array<string, \Generated\Shared\Transfer\RecurringScheduleTransfer>
     */
    protected function getExistingRecurringSchedulesIndexedByUuid(
        ArrayObject $requestedScheduleTransfers,
        CustomerTransfer $customerTransfer,
    ): array {
        $uuids = [];

        foreach ($requestedScheduleTransfers as $requestedScheduleTransfer) {
            $uuids[] = $requestedScheduleTransfer->getUuidOrFail();
        }

        $recurringScheduleCriteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setCustomer($customerTransfer)
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())->setUuids(array_values(array_unique($uuids))),
            );

        $recurringScheduleCollectionTransfer = $this->recurringScheduleReader
            ->getRecurringScheduleCollection($recurringScheduleCriteriaTransfer);

        $existingScheduleTransfers = [];

        foreach ($recurringScheduleCollectionTransfer->getRecurringSchedules() as $existingScheduleTransfer) {
            $existingScheduleTransfers[$existingScheduleTransfer->getUuidOrFail()] = $existingScheduleTransfer;
        }

        return $existingScheduleTransfers;
    }

    protected function applyQuoteOverride(
        RecurringScheduleTransfer $updateScheduleTransfer,
        RecurringScheduleTransfer $requestedScheduleTransfer,
        RecurringScheduleTransfer $existingScheduleTransfer,
    ): RecurringScheduleTransfer {
        $mergedScheduleTransfer = $this->recurringScheduleQuoteDataMerger->applyQuoteOverride(
            $existingScheduleTransfer,
            $requestedScheduleTransfer->getQuote(),
        );

        if ($requestedScheduleTransfer->getQuote() !== null && $mergedScheduleTransfer->getQuoteData() !== null) {
            $updateScheduleTransfer->setQuoteData($mergedScheduleTransfer->getQuoteData());
        }

        return $updateScheduleTransfer;
    }
}
