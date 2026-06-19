<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Reader;

use Generated\Shared\Transfer\QuoteTransfer;

class RecurringScheduleQuoteDataDeserializer implements RecurringScheduleQuoteDataDeserializerInterface
{
    public function deserialize(?string $quoteData): QuoteTransfer
    {
        if ($quoteData === null) {
            return new QuoteTransfer();
        }

        $quoteDataArray = json_decode($quoteData, true, 512, JSON_THROW_ON_ERROR);

        return (new QuoteTransfer())->fromArray($quoteDataArray, true);
    }
}
