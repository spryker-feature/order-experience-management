<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Transition;

use ArrayObject;

interface OrderItemTransitionOutcomeMapperInterface
{
    /**
     * @param array<int, \Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     * @param array<int, string> $stateNamesBeforeByOrderItemId
     * @param array<int, string> $admittedStateNamesByOrderItemId
     *
     * @return \ArrayObject<int, \Generated\Shared\Transfer\OrderItemTransitionOutcomeTransfer>
     */
    public function createOutcomes(
        array $itemTransfers,
        array $stateNamesBeforeByOrderItemId,
        array $admittedStateNamesByOrderItemId
    ): ArrayObject;

    /**
     * @param array<int, \Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     *
     * @return array<int, string> State names keyed by idSalesOrderItem.
     */
    public function extractStateNamesByOrderItemId(array $itemTransfers): array;

    /**
     * @param array<int, \Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     *
     * @return array<int, string>
     */
    public function extractUuids(array $itemTransfers): array;

    /**
     * @param array<int, string> $requestedUuids
     * @param array<int, \Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     *
     * @return array<int, string>
     */
    public function findUnknownUuids(array $requestedUuids, array $itemTransfers): array;
}
