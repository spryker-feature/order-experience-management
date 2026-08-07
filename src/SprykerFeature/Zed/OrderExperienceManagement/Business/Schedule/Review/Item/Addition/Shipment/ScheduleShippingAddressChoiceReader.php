<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment;

use Generated\Shared\Transfer\RecurringScheduleTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\PlaceableQuoteDeserializerInterface;

class ScheduleShippingAddressChoiceReader implements ScheduleShippingAddressChoiceReaderInterface
{
    public function __construct(
        protected readonly PlaceableQuoteDeserializerInterface $placeableQuoteDeserializer,
        protected readonly AddedItemShippingAddressResolverInterface $addedItemShippingAddressResolver,
    ) {
    }

    /**
     * @return array<\Generated\Shared\Transfer\RecurringScheduleShippingAddressChoiceTransfer>
     */
    public function getChoices(RecurringScheduleTransfer $recurringScheduleTransfer): array
    {
        $quoteData = $recurringScheduleTransfer->getQuoteData();

        if ($quoteData === null || $quoteData === '') {
            return [];
        }

        return array_values($this->addedItemShippingAddressResolver->getOwnedAddressChoices(
            $recurringScheduleTransfer,
            $this->placeableQuoteDeserializer->deserialize($quoteData),
        ));
    }
}
