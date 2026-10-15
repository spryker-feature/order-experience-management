<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Calculator;

use Spryker\DecimalObject\Decimal;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig;

class OrderTotalReviewThresholdCalculator implements OrderTotalReviewThresholdCalculatorInterface
{
    public function __construct(protected readonly OrderExperienceManagementConfig $orderExperienceManagementConfig)
    {
    }

    public function calculate(int $total, Decimal $tolerancePercent): int
    {
        $denominator = SharedOrderExperienceManagementConfig::PRICE_TOLERANCE_PERCENT_DENOMINATOR;

        return Decimal::create($total)
            ->multiply(Decimal::create($denominator)->add($tolerancePercent))
            ->divide($denominator, $this->getExactDivisionScale($denominator))
            ->floor()
            ->toInt();
    }

    protected function getExactDivisionScale(int $denominator): int
    {
        $denominatorDecimals = strlen((string)$denominator) - 1;

        return $this->orderExperienceManagementConfig->getPriceToleranceScale() + $denominatorDecimals;
    }
}
