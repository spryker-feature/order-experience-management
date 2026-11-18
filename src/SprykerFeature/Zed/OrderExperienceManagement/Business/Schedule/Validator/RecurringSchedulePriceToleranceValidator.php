<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator;

use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Spryker\DecimalObject\Decimal;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;

class RecurringSchedulePriceToleranceValidator implements RecurringSchedulePriceToleranceValidatorInterface
{
    protected const string GLOSSARY_KEY_PRICE_TOLERANCE_OUT_OF_RANGE = 'recurring_orders.price_tolerance.error.out_of_range';

    protected const string GLOSSARY_KEY_PRICE_TOLERANCE_TOO_PRECISE = 'recurring_orders.price_tolerance.error.too_precise';

    protected const string GLOSSARY_KEY_PRICE_TOLERANCE_NOT_IN_STEP = 'recurring_orders.price_tolerance.error.not_in_step';

    protected const string PARAMETER_MAX = '%max%';

    protected const string PARAMETER_DECIMALS = '%decimals%';

    protected const string PARAMETER_STEP = '%step%';

    protected const string GLOSSARY_KEY_PRICE_TOLERANCE_IGNORE_NEEDS_ORDER_TOLERANCE = 'recurring_orders.price_tolerance.error.ignore_requires_order_tolerance';

    protected const string GLOSSARY_KEY_PRICE_TOLERANCE_ITEM_NOT_FOUND = 'recurring_orders.price_tolerance.error.item_not_found';

    protected const string GLOSSARY_KEY_PRICE_TOLERANCE_LOCKED_IN_REVIEW = 'recurring_orders.price_tolerance.locked_notice';

    public function __construct(protected OrderExperienceManagementConfig $config)
    {
    }

    public function findError(
        RecurringScheduleTransfer $requestedScheduleTransfer,
        array $requestedItemTransfers,
        RecurringScheduleTransfer $existingScheduleTransfer,
    ): ?ErrorTransfer {
        if (!$this->isToleranceChangeRequested($requestedScheduleTransfer, $requestedItemTransfers)) {
            return null;
        }

        $errorTransfer = $this->findRequestedToleranceError($requestedScheduleTransfer, $requestedItemTransfers, $existingScheduleTransfer);

        if ($errorTransfer !== null) {
            return $errorTransfer;
        }

        if ($existingScheduleTransfer->getStatus() === SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED) {
            return $this->createError(static::GLOSSARY_KEY_PRICE_TOLERANCE_LOCKED_IN_REVIEW);
        }

        return null;
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemTransfer> $requestedItemTransfers
     */
    protected function isToleranceChangeRequested(RecurringScheduleTransfer $requestedScheduleTransfer, array $requestedItemTransfers): bool
    {
        return $requestedScheduleTransfer->getPriceTolerancePercent() !== null
            || $requestedItemTransfers !== []
            || $requestedScheduleTransfer->getIsProductPriceChangeIgnored() !== null;
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemTransfer> $requestedItemTransfers
     */
    protected function findRequestedToleranceError(
        RecurringScheduleTransfer $requestedScheduleTransfer,
        array $requestedItemTransfers,
        RecurringScheduleTransfer $existingScheduleTransfer
    ): ?ErrorTransfer {
        $orderTolerancePercent = $requestedScheduleTransfer->getPriceTolerancePercent();
        $errorTransfer = $orderTolerancePercent === null ? null : $this->findToleranceValueError($orderTolerancePercent);

        if ($errorTransfer !== null) {
            return $errorTransfer;
        }

        $errorTransfer = $this->findItemToleranceError($requestedItemTransfers, $existingScheduleTransfer);

        if ($errorTransfer !== null) {
            return $errorTransfer;
        }

        if ($orderTolerancePercent !== null || $requestedScheduleTransfer->getIsProductPriceChangeIgnored() !== null) {
            return $this->findIgnoreSettingError($requestedScheduleTransfer, $existingScheduleTransfer);
        }

        return null;
    }

    protected function findIgnoreSettingError(
        RecurringScheduleTransfer $requestedScheduleTransfer,
        RecurringScheduleTransfer $existingScheduleTransfer
    ): ?ErrorTransfer {
        $isIgnored = $requestedScheduleTransfer->getIsProductPriceChangeIgnored() ?? $existingScheduleTransfer->getIsProductPriceChangeIgnored();
        $orderTolerancePercent = $requestedScheduleTransfer->getPriceTolerancePercent() ?? $existingScheduleTransfer->getPriceTolerancePercent();

        if ($isIgnored === true && ($orderTolerancePercent === null || !$orderTolerancePercent->isPositive())) {
            return $this->createError(static::GLOSSARY_KEY_PRICE_TOLERANCE_IGNORE_NEEDS_ORDER_TOLERANCE);
        }

        return null;
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemTransfer> $requestedItemTransfers
     */
    protected function findItemToleranceError(array $requestedItemTransfers, RecurringScheduleTransfer $existingScheduleTransfer): ?ErrorTransfer
    {
        $existingItemKeys = $this->indexItemKeys($existingScheduleTransfer);

        foreach ($requestedItemTransfers as $requestedItemTransfer) {
            $priceTolerancePercent = $requestedItemTransfer->getPriceTolerancePercent();

            if ($priceTolerancePercent !== null) {
                $valueError = $this->findToleranceValueError($priceTolerancePercent);

                if ($valueError !== null) {
                    return $valueError;
                }
            }

            $itemKey = $requestedItemTransfer->getGroupKey() ?? $requestedItemTransfer->getBundleItemIdentifier();

            if ($itemKey === null || !isset($existingItemKeys[$itemKey])) {
                return $this->createError(static::GLOSSARY_KEY_PRICE_TOLERANCE_ITEM_NOT_FOUND);
            }
        }

        return null;
    }

    protected function findToleranceValueError(Decimal $priceTolerancePercent): ?ErrorTransfer
    {
        if ($priceTolerancePercent->isNegative() || $priceTolerancePercent->greaterThan($this->config->getPriceToleranceMaxPercent())) {
            return $this->createError(static::GLOSSARY_KEY_PRICE_TOLERANCE_OUT_OF_RANGE, [static::PARAMETER_MAX => $this->config->getPriceToleranceMaxPercent()]);
        }

        if ($priceTolerancePercent->trim()->scale() > $this->config->getPriceToleranceScale()) {
            return $this->createError(static::GLOSSARY_KEY_PRICE_TOLERANCE_TOO_PRECISE, [static::PARAMETER_DECIMALS => (string)$this->config->getPriceToleranceScale()]);
        }

        if (!$priceTolerancePercent->mod($this->config->getPriceToleranceStep(), $this->config->getPriceToleranceScale())->isZero()) {
            return $this->createError(static::GLOSSARY_KEY_PRICE_TOLERANCE_NOT_IN_STEP, [static::PARAMETER_STEP => $this->config->getPriceToleranceStep()]);
        }

        return null;
    }

    /**
     * @param array<string, string> $parameters
     */
    protected function createError(string $glossaryKey, array $parameters = []): ErrorTransfer
    {
        return (new ErrorTransfer())->setMessage($glossaryKey)->setParameters($parameters);
    }

    /**
     * @return array<string, true>
     */
    protected function indexItemKeys(RecurringScheduleTransfer $recurringScheduleTransfer): array
    {
        $itemKeys = [];

        foreach ($recurringScheduleTransfer->getItems() as $recurringScheduleItemTransfer) {
            foreach ([$recurringScheduleItemTransfer->getGroupKey(), $recurringScheduleItemTransfer->getBundleItemIdentifier()] as $itemKey) {
                if ($itemKey !== null) {
                    $itemKeys[$itemKey] = true;
                }
            }
        }

        return $itemKeys;
    }
}
