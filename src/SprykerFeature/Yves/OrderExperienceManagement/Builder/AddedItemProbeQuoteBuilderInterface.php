<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Builder;

use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;

interface AddedItemProbeQuoteBuilderInterface
{
    public function buildProbeQuote(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        string $sku,
        ?string $merchantReference,
        ?string $shipmentTypeUuid,
        AddressTransfer $addressTransfer,
    ): QuoteTransfer;
}
