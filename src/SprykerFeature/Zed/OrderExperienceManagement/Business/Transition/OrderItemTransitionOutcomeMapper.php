<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Transition;

use ArrayObject;
use Generated\Shared\Transfer\OrderItemTransitionOutcomeTransfer;

class OrderItemTransitionOutcomeMapper implements OrderItemTransitionOutcomeMapperInterface
{
    public const string OUTCOME_TRANSITIONED = 'transitioned';

    public const string OUTCOME_UNCHANGED = 'unchanged';

    public const string OUTCOME_SKIPPED = 'skipped';

    public function createOutcomes(
        array $itemTransfers,
        array $stateNamesBeforeByOrderItemId,
        array $admittedStateNamesByOrderItemId
    ): ArrayObject {
        $outcomeTransfers = new ArrayObject();

        foreach ($itemTransfers as $itemTransfer) {
            $idSalesOrderItem = $itemTransfer->getIdSalesOrderItem();
            $stateName = $itemTransfer->getState()?->getName();

            $stateNameBefore = $idSalesOrderItem !== null
                ? $stateNamesBeforeByOrderItemId[$idSalesOrderItem] ?? $stateName
                : $stateName;
            $wasAdmitted = $idSalesOrderItem !== null
                && array_key_exists($idSalesOrderItem, $admittedStateNamesByOrderItemId);

            $outcomeTransfers->append((new OrderItemTransitionOutcomeTransfer())
                ->setUuid($itemTransfer->getUuid())
                ->setIdSalesOrderItem($idSalesOrderItem)
                ->setStateBefore($stateNameBefore)
                ->setState($stateName)
                ->setOutcome($this->resolveOutcome($wasAdmitted, $stateNameBefore, $stateName)));
        }

        return $outcomeTransfers;
    }

    protected function resolveOutcome(bool $wasAdmitted, ?string $stateNameBefore, ?string $stateName): string
    {
        if (!$wasAdmitted) {
            return static::OUTCOME_SKIPPED;
        }

        return $stateNameBefore === $stateName ? static::OUTCOME_UNCHANGED : static::OUTCOME_TRANSITIONED;
    }

    public function extractStateNamesByOrderItemId(array $itemTransfers): array
    {
        $stateNamesByOrderItemId = [];

        foreach ($itemTransfers as $itemTransfer) {
            $idSalesOrderItem = $itemTransfer->getIdSalesOrderItem();
            $stateName = $itemTransfer->getState()?->getName();

            if ($idSalesOrderItem === null || $stateName === null) {
                continue;
            }

            $stateNamesByOrderItemId[$idSalesOrderItem] = $stateName;
        }

        return $stateNamesByOrderItemId;
    }

    public function extractUuids(array $itemTransfers): array
    {
        $uuids = [];

        foreach ($itemTransfers as $itemTransfer) {
            if ($itemTransfer->getUuid() !== null) {
                $uuids[] = $itemTransfer->getUuid();
            }
        }

        return $uuids;
    }

    public function findUnknownUuids(array $requestedUuids, array $itemTransfers): array
    {
        if ($requestedUuids === []) {
            return [];
        }

        return array_values(array_diff($requestedUuids, $this->extractUuids($itemTransfers)));
    }
}
