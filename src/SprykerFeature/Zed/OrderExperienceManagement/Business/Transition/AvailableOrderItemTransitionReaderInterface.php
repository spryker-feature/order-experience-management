<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Transition;

use Generated\Shared\Transfer\ItemCollectionTransfer;
use Generated\Shared\Transfer\ItemTransfer;

interface AvailableOrderItemTransitionReaderInterface
{
    /**
     * @return array<int, array<int, string>> Event names keyed by idSalesOrderItem.
     */
    public function getAvailableTransitionsByOrderItemId(ItemCollectionTransfer $itemCollectionTransfer): array;

    /**
     * @return array<int, string> Event names; empty when the item is in a terminal state.
     */
    public function getAvailableTransitions(ItemTransfer $itemTransfer): array;
}
