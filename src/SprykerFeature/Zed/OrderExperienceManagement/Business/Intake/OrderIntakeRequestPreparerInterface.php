<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Intake;

use Generated\Shared\Transfer\OrderIntakeRequestTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;

interface OrderIntakeRequestPreparerInterface
{
    /**
     * Validates the request and resolves its customer and addresses, recording every problem on the response.
     */
    public function prepareRequest(
        OrderIntakeRequestTransfer $orderIntakeRequestTransfer,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
    ): OrderIntakeRequestTransfer;
}
