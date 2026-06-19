<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Order;

use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;

class RecurringOrderQuoteBuilder implements RecurringOrderQuoteBuilderInterface
{
    public function __construct(
        protected PlaceableQuoteDeserializerInterface $placeableQuoteDeserializer,
        protected PlaceableQuoteItemBuilderInterface $placeableQuoteItemBuilder,
    ) {
    }

    public function buildPlaceableQuote(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        bool $isPlacement = false,
    ): QuoteTransfer {
        $quoteTransfer = $this->placeableQuoteDeserializer->deserialize($recurringScheduleTransfer->getQuoteDataOrFail());

        return $this->placeableQuoteItemBuilder->appendScheduleItems($quoteTransfer, $recurringScheduleTransfer, $isPlacement);
    }
}
