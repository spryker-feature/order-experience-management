<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Order;

use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\QuoteResponseTransfer;

interface PlacementCheckoutResponseBuilderInterface
{
    public function createScheduleNotFoundResponse(): CheckoutResponseTransfer;

    public function createPlacementFailureResponse(): CheckoutResponseTransfer;

    /**
     * @param array<string> $newErrorMessages
     */
    public function createReloadErrorResponse(QuoteResponseTransfer $quoteResponseTransfer, array $newErrorMessages): CheckoutResponseTransfer;

    /**
     * @param list<string> $unpurchasableSkus
     * @param array<string> $messages
     */
    public function createUnpurchasableItemsResponse(array $unpurchasableSkus, array $messages): CheckoutResponseTransfer;
}
