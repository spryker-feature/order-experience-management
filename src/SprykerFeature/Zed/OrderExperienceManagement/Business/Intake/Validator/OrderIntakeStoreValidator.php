<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Validator;

use Generated\Shared\Transfer\OrderIntakeRequestTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\OrderIntakeValidationIssueTransfer;
use Spryker\Zed\Store\Business\StoreFacadeInterface;

/**
 * Checks that the store the payload names exists.
 */
class OrderIntakeStoreValidator implements OrderIntakeStoreValidatorInterface
{
    protected const string FIELD_STORE = 'store';

    protected const string MESSAGE_STORE_NOT_FOUND = 'Store "%storeName%" was not found.';

    public function __construct(
        protected readonly StoreFacadeInterface $storeFacade,
    ) {
    }

    public function validateStore(
        OrderIntakeRequestTransfer $orderIntakeRequestTransfer,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
    ): OrderIntakeResponseTransfer {
        $storeName = $orderIntakeRequestTransfer->getStoreName();

        if ($storeName === null || trim($storeName) === '') {
            return $orderIntakeResponseTransfer;
        }

        if ($this->storeFacade->findStoreByName($storeName) !== null) {
            return $orderIntakeResponseTransfer;
        }

        return $orderIntakeResponseTransfer->addValidationIssue(
            (new OrderIntakeValidationIssueTransfer())
                ->setField(static::FIELD_STORE)
                ->setMessage(static::MESSAGE_STORE_NOT_FOUND)
                ->setParameters(['%storeName%' => $storeName]),
        );
    }
}
