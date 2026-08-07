<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Stub;

use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleErrorTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\RecurringScheduleValidationResultTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\ScheduleValidatorPluginInterface;

class FixedBlockingErrorScheduleValidatorPlugin implements ScheduleValidatorPluginInterface
{
    public function __construct(protected string $message)
    {
    }

    public function validate(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        QuoteTransfer $quoteTransfer,
        RecurringScheduleValidationResultTransfer $recurringScheduleValidationResultTransfer,
    ): RecurringScheduleValidationResultTransfer {
        return $recurringScheduleValidationResultTransfer
            ->addBlockingError((new RecurringScheduleErrorTransfer())->setMessage($this->message))
            ->setIsValid(false);
    }
}
