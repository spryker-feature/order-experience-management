<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\OrderExperienceManagement\Reader;

interface RecurringOrderAttentionBannerReaderInterface
{
    /**
     * @api
     *
     * @return array<string, int>
     */
    public function getAttentionStatusCounts(int $idCustomer): array;
}
