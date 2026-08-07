<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Reader;

use Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer;

interface AddedItemAddressChoicesReaderInterface
{
    /**
     * The company unit address id is empty for an address stored with the schedule, which is what the legacy
     * `idShippingAddress` field must submit for one.
     *
     * @return array<string, array<string, array{label: string, idCompanyUnitAddress: string}>> Group glossary key => [address choice key => choice].
     */
    public function getAddressChoices(
        RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer,
    ): array;
}
