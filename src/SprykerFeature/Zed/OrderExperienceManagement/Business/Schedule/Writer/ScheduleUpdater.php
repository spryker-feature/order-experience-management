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
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence\CadenceResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Mapper\RecurringScheduleMapperInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Reader\RecurringScheduleReaderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Quote\RecurringScheduleQuoteDataMergerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\RecurringSchedulePriceToleranceValidatorInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementEntityManagerInterface;

class ScheduleUpdater implements ScheduleUpdaterInterface
{
    use TransactionTrait;

    protected const string GLOSSARY_KEY_SCHEDULE_NOT_FOUND = 'recurring_orders.detail.edit.error.schedule_not_found';

    protected const string PARAMETER_UUID = '%uuid%';

    public function __construct(
        protected RecurringScheduleReaderInterface $recurringScheduleReader,
        protected RecurringScheduleQuoteDataMergerInterface $recurringScheduleQuoteDataMerger,
        protected RecurringScheduleMapperInterface $recurringScheduleMapper,
        protected OrderExperienceManagementEntityManagerInterface $entityManager,
        protected CadenceResolverInterface $cadenceResolver,
        protected RecurringSchedulePriceToleranceValidatorInterface $priceToleranceValidator,
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
            $this->hasRequestedToleranceItems($requestedScheduleTransfers),
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

            $validationErrorGlossaryKey = $this->validateRequestedCadence($requestedScheduleTransfer);

            if ($validationErrorGlossaryKey !== null) {
                $recurringScheduleCollectionResponseTransfer->addError(
                    (new ErrorTransfer())
                        ->setMessage($validationErrorGlossaryKey)
                        ->setEntityIdentifier($uuid),
                );

                continue;
            }

            $priceToleranceErrorTransfer = $this->priceToleranceValidator->findError($requestedScheduleTransfer, $this->getRequestedToleranceItems($requestedScheduleTransfer), $existingScheduleTransfer);

            if ($priceToleranceErrorTransfer !== null) {
                $recurringScheduleCollectionResponseTransfer->addError(
                    $priceToleranceErrorTransfer
                        ->setEntityIdentifier($uuid)
                        ->setParameters($priceToleranceErrorTransfer->getParameters() + [static::PARAMETER_UUID => $uuid]),
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
                $this->getTransactionHandler()->handleTransaction(
                    fn (): RecurringScheduleTransfer => $this->persistScheduleUpdate(
                        $updateScheduleTransfer,
                        $this->getRequestedToleranceItems($requestedScheduleTransfer),
                    ),
                ),
            );
        }

        return $recurringScheduleCollectionResponseTransfer;
    }

    protected function validateRequestedCadence(RecurringScheduleTransfer $requestedScheduleTransfer): ?string
    {
        $cadenceType = $requestedScheduleTransfer->getCadenceType();

        if ($cadenceType === null || !$this->cadenceResolver->isSupported($cadenceType)) {
            return null;
        }

        $cadenceValidationResponseTransfer = $this->cadenceResolver->isCadenceValid(
            $cadenceType,
            $requestedScheduleTransfer->getCadenceValue(),
            $requestedScheduleTransfer->getCadenceOptions(),
        );

        if (!$cadenceValidationResponseTransfer->getIsSuccess()) {
            return $cadenceValidationResponseTransfer->getMessage();
        }

        return null;
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemTransfer> $requestedToleranceItemTransfers
     */
    protected function persistScheduleUpdate(
        RecurringScheduleTransfer $updateScheduleTransfer,
        array $requestedToleranceItemTransfers,
    ): RecurringScheduleTransfer {
        $this->entityManager->updateRecurringScheduleItemPriceTolerances(
            $updateScheduleTransfer->getIdRecurringScheduleOrFail(),
            $requestedToleranceItemTransfers,
        );

        return $this->entityManager->updateRecurringSchedule($updateScheduleTransfer);
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\RecurringScheduleTransfer> $requestedScheduleTransfers
     */
    protected function hasRequestedToleranceItems(ArrayObject $requestedScheduleTransfers): bool
    {
        foreach ($requestedScheduleTransfers as $requestedScheduleTransfer) {
            if ($this->getRequestedToleranceItems($requestedScheduleTransfer) !== []) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<\Generated\Shared\Transfer\RecurringScheduleItemTransfer>
     */
    protected function getRequestedToleranceItems(RecurringScheduleTransfer $requestedScheduleTransfer): array
    {
        $requestedItemTransfers = [];

        foreach ($requestedScheduleTransfer->getItems() as $requestedItemTransfer) {
            if ($requestedItemTransfer->isPropertyModified(RecurringScheduleItemTransfer::PRICE_TOLERANCE_PERCENT)) {
                $requestedItemTransfers[] = $requestedItemTransfer;
            }
        }

        return $requestedItemTransfers;
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\RecurringScheduleTransfer> $requestedScheduleTransfers
     *
     * @return array<string, \Generated\Shared\Transfer\RecurringScheduleTransfer>
     */
    protected function getExistingRecurringSchedulesIndexedByUuid(
        ArrayObject $requestedScheduleTransfers,
        CustomerTransfer $customerTransfer,
        bool $isWithItems = false,
    ): array {
        $uuids = [];

        foreach ($requestedScheduleTransfers as $requestedScheduleTransfer) {
            $uuids[] = $requestedScheduleTransfer->getUuidOrFail();
        }

        $recurringScheduleCriteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setCustomer($customerTransfer)
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->setUuids(array_values(array_unique($uuids)))
                    ->setIsWithItems($isWithItems)
                    ->setIsGroupedByGroupKey($isWithItems),
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
