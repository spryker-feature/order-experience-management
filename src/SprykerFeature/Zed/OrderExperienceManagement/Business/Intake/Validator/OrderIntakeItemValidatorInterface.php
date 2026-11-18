<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Validator;

use Generated\Shared\Transfer\OrderIntakeItemTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;

interface OrderIntakeItemValidatorInterface
{
    public function validate(
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        OrderIntakeItemTransfer $orderIntakeItemTransfer,
        int $index,
    ): void;
}
