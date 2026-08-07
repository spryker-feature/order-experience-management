<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment;

use Generated\Shared\Transfer\RecurringScheduleItemAdditionTransfer;
use Generated\Shared\Transfer\RecurringScheduleShippingAddressChoiceTransfer;

class ShippingAddressChoiceMatcher implements ShippingAddressChoiceMatcherInterface
{
    public function __construct(protected readonly ShippingAddressChoiceKeyGeneratorInterface $shippingAddressChoiceKeyGenerator)
    {
    }

    /**
     * @param array<string, \Generated\Shared\Transfer\RecurringScheduleShippingAddressChoiceTransfer> $choiceTransfers
     */
    public function findChoice(
        RecurringScheduleItemAdditionTransfer $recurringScheduleItemAdditionTransfer,
        array $choiceTransfers,
    ): ?RecurringScheduleShippingAddressChoiceTransfer {
        $key = $this->resolveKey($recurringScheduleItemAdditionTransfer);

        if ($key === null) {
            return null;
        }

        return $choiceTransfers[$key] ?? null;
    }

    protected function resolveKey(RecurringScheduleItemAdditionTransfer $recurringScheduleItemAdditionTransfer): ?string
    {
        $shippingAddressKey = $recurringScheduleItemAdditionTransfer->getShippingAddressKey();

        if ($shippingAddressKey !== null && $shippingAddressKey !== '') {
            return $shippingAddressKey;
        }

        $idShippingAddress = $recurringScheduleItemAdditionTransfer->getIdShippingAddress();

        if ($idShippingAddress === null) {
            return null;
        }

        return $this->shippingAddressChoiceKeyGenerator->generateCompanyUnitAddressKey($idShippingAddress);
    }
}
