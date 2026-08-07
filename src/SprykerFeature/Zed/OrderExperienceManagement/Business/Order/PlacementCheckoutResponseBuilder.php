<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Order;

use Generated\Shared\Transfer\CheckoutErrorTransfer;
use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\QuoteResponseTransfer;

class PlacementCheckoutResponseBuilder implements PlacementCheckoutResponseBuilderInterface
{
    protected const string GLOSSARY_KEY_ITEMS_NOT_PURCHASABLE = 'recurring_orders.error.items_not_purchasable';

    protected const string ERROR_SCHEDULE_NOT_FOUND = 'Recurring schedule not found.';

    protected const string ERROR_PLACEMENT_FAILED = 'Recurring order placement failed unexpectedly.';

    protected const string PARAMETER_SKUS = '%skus%';

    public function createScheduleNotFoundResponse(): CheckoutResponseTransfer
    {
        return (new CheckoutResponseTransfer())
            ->setIsSuccess(false)
            ->addError((new CheckoutErrorTransfer())->setMessage(static::ERROR_SCHEDULE_NOT_FOUND));
    }

    public function createPlacementFailureResponse(): CheckoutResponseTransfer
    {
        return (new CheckoutResponseTransfer())
            ->setIsSuccess(false)
            ->addError((new CheckoutErrorTransfer())->setMessage(static::ERROR_PLACEMENT_FAILED));
    }

    /**
     * @param array<string> $newErrorMessages
     */
    public function createReloadErrorResponse(QuoteResponseTransfer $quoteResponseTransfer, array $newErrorMessages): CheckoutResponseTransfer
    {
        $checkoutResponseTransfer = (new CheckoutResponseTransfer())->setIsSuccess(false);

        foreach ($quoteResponseTransfer->getErrors() as $quoteErrorTransfer) {
            $checkoutResponseTransfer->addError(
                (new CheckoutErrorTransfer())->setMessage($quoteErrorTransfer->getMessage()),
            );
        }

        return $this->addErrorMessages($checkoutResponseTransfer, $newErrorMessages);
    }

    /**
     * @param list<string> $unpurchasableSkus
     * @param array<string> $messages
     */
    public function createUnpurchasableItemsResponse(array $unpurchasableSkus, array $messages): CheckoutResponseTransfer
    {
        $checkoutResponseTransfer = (new CheckoutResponseTransfer())
            ->setIsSuccess(false)
            ->addError(
                (new CheckoutErrorTransfer())
                    ->setMessage(static::GLOSSARY_KEY_ITEMS_NOT_PURCHASABLE)
                    ->setParameters([static::PARAMETER_SKUS => implode(', ', $unpurchasableSkus)]),
            );

        return $this->addErrorMessages($checkoutResponseTransfer, $messages);
    }

    /**
     * @param array<string> $errorMessages
     */
    protected function addErrorMessages(CheckoutResponseTransfer $checkoutResponseTransfer, array $errorMessages): CheckoutResponseTransfer
    {
        foreach ($errorMessages as $errorMessage) {
            $checkoutResponseTransfer->addError(
                (new CheckoutErrorTransfer())->setMessage($errorMessage),
            );
        }

        return $checkoutResponseTransfer;
    }
}
