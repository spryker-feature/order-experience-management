<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Resolver;

use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Spryker\Client\Currency\CurrencyClientInterface;
use Spryker\Client\Price\PriceClientInterface;

class ScheduleReviewContextMismatchResolver implements ScheduleReviewContextMismatchResolverInterface
{
    public function __construct(
        protected readonly CurrencyClientInterface $currencyClient,
        protected readonly PriceClientInterface $priceClient,
    ) {
    }

    public function isCurrencyMismatch(RecurringScheduleTransfer $recurringScheduleTransfer): bool
    {
        $currencyIsoCode = $recurringScheduleTransfer->getCurrencyIsoCode();

        if ($currencyIsoCode === null) {
            return false;
        }

        return $currencyIsoCode !== $this->currencyClient->getCurrent()->getCode();
    }

    public function isPriceModeMismatch(RecurringScheduleTransfer $recurringScheduleTransfer): bool
    {
        $priceMode = $recurringScheduleTransfer->getPriceMode();

        if ($priceMode === null) {
            return false;
        }

        return $priceMode !== $this->priceClient->getCurrentPriceMode();
    }
}
