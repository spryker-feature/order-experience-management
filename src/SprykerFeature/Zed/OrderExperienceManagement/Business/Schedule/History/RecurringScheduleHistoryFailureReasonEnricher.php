<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\History;

use Generated\Shared\Transfer\RecurringScheduleErrorTransfer;
use Generated\Shared\Transfer\RecurringScheduleHistoryTransfer;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;

class RecurringScheduleHistoryFailureReasonEnricher implements RecurringScheduleHistoryFailureReasonEnricherInterface
{
    protected const string DETAIL_KEY_MESSAGE = 'message';

    protected const string DETAIL_KEY_PARAMETERS = 'parameters';

    protected const string GLOSSARY_KEY_PRODUCT_UNAVAILABLE = 'product.unavailable';

    protected const string PARAMETER_SKU = '%sku%';

    public function __construct(
        protected readonly UtilEncodingServiceInterface $utilEncodingService,
    ) {
    }

    public function enrich(RecurringScheduleHistoryTransfer $recurringScheduleHistoryTransfer): RecurringScheduleHistoryTransfer
    {
        if ($recurringScheduleHistoryTransfer->getEventType() !== SharedOrderExperienceManagementConfig::HISTORY_EVENT_TYPE_FAILED) {
            return $recurringScheduleHistoryTransfer;
        }

        $detail = $this->utilEncodingService->decodeJson($recurringScheduleHistoryTransfer->getDetail() ?? '[]', true);

        if (!is_array($detail) || $detail === []) {
            return $recurringScheduleHistoryTransfer;
        }

        $recurringScheduleHistoryTransfer->setFailureReason($this->extractUnavailableSkus($detail));
        $this->enrichErrors($recurringScheduleHistoryTransfer, $detail);

        return $recurringScheduleHistoryTransfer;
    }

    /**
     * @param array<int, array<string, mixed>> $errors
     */
    protected function enrichErrors(RecurringScheduleHistoryTransfer $recurringScheduleHistoryTransfer, array $errors): void
    {
        foreach ($errors as $error) {
            $message = $error[static::DETAIL_KEY_MESSAGE] ?? null;

            if ($message === null) {
                continue;
            }

            $recurringScheduleHistoryTransfer->addError(
                (new RecurringScheduleErrorTransfer())
                    ->setMessage($message)
                    ->setParameters($error[static::DETAIL_KEY_PARAMETERS] ?? []),
            );
        }
    }

    /**
     * @param array<int, array<string, mixed>> $errors
     */
    protected function extractUnavailableSkus(array $errors): ?string
    {
        $skus = [];

        foreach ($errors as $error) {
            if (($error[static::DETAIL_KEY_MESSAGE] ?? null) !== static::GLOSSARY_KEY_PRODUCT_UNAVAILABLE) {
                continue;
            }

            $sku = $error[static::DETAIL_KEY_PARAMETERS][static::PARAMETER_SKU] ?? null;

            if ($sku !== null) {
                $skus[] = (string)$sku;
            }
        }

        if ($skus !== []) {
            return implode(', ', array_unique($skus));
        }

        return ($errors[0][static::DETAIL_KEY_MESSAGE] ?? null) ?: null;
    }
}
