<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Stub;

use Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\RecurringScheduleValidationResultTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\ScheduleValidatorPluginInterface;

class FixedScheduleValidatorPlugin implements ScheduleValidatorPluginInterface
{
    public function __construct(
        protected string $targetSku,
        protected bool $isPurchasable,
        protected string $reviewReason,
        protected ?int $currentPrice = null,
    ) {
    }

    public function validate(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        RecurringScheduleValidationResultTransfer $recurringScheduleValidationResultTransfer,
    ): RecurringScheduleValidationResultTransfer {
        foreach ($recurringScheduleTransfer->getItems() as $recurringScheduleItemTransfer) {
            if ($recurringScheduleItemTransfer->getSku() !== $this->targetSku) {
                continue;
            }

            $recurringScheduleValidationResultTransfer
                ->addItemReview(
                    (new RecurringScheduleItemReviewTransfer())
                        ->setRecurringScheduleItem($recurringScheduleItemTransfer)
                        ->setIsPurchasable($this->isPurchasable)
                        ->setCurrentPrice($this->currentPrice)
                        ->addReviewReason($this->reviewReason),
                )
                ->setIsValid(false);
        }

        return $recurringScheduleValidationResultTransfer;
    }
}
