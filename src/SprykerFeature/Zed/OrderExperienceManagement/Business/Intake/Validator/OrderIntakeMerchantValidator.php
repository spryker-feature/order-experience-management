<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Validator;

use Generated\Shared\Transfer\MerchantCriteriaTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\OrderIntakeValidationIssueTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Zed\Merchant\Business\MerchantFacadeInterface;

/**
 * Checks the merchant on a line that names one without naming an offer.
 */
class OrderIntakeMerchantValidator implements OrderIntakeMerchantValidatorInterface
{
    protected const string FIELD_ITEM_MERCHANT_REFERENCE_PATTERN = 'items[%d].merchantReference';

    protected const string MESSAGE_MERCHANT_NOT_FOUND = 'Merchant "%merchantReference%" was not found or is not active.';

    public function __construct(
        protected readonly MerchantFacadeInterface $merchantFacade,
    ) {
    }

    public function validateMerchants(
        QuoteTransfer $quoteTransfer,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
    ): QuoteTransfer {
        $merchantReferencesByIndex = $this->findMerchantOnlyLines($quoteTransfer);

        if ($merchantReferencesByIndex === []) {
            return $quoteTransfer;
        }

        $activeMerchantReferences = $this->findActiveMerchantReferences($merchantReferencesByIndex);

        foreach ($merchantReferencesByIndex as $index => $merchantReference) {
            if (isset($activeMerchantReferences[$merchantReference])) {
                continue;
            }

            $orderIntakeResponseTransfer->addValidationIssue(
                (new OrderIntakeValidationIssueTransfer())
                    ->setField(sprintf(static::FIELD_ITEM_MERCHANT_REFERENCE_PATTERN, $index))
                    ->setMessage(static::MESSAGE_MERCHANT_NOT_FOUND)
                    ->setParameters(['%merchantReference%' => $merchantReference]),
            );
        }

        return $quoteTransfer;
    }

    /**
     * @return array<int, string>
     */
    protected function findMerchantOnlyLines(QuoteTransfer $quoteTransfer): array
    {
        $merchantReferencesByIndex = [];

        foreach ($quoteTransfer->getItems() as $index => $itemTransfer) {
            $productOfferReference = $itemTransfer->getProductOfferReference();

            if ($productOfferReference !== null && $productOfferReference !== '') {
                continue;
            }

            $merchantReference = $itemTransfer->getMerchantReference();

            if ($merchantReference === null || $merchantReference === '') {
                continue;
            }

            $merchantReferencesByIndex[$index] = $merchantReference;
        }

        return $merchantReferencesByIndex;
    }

    /**
     * @param array<int, string> $merchantReferencesByIndex
     *
     * @return array<string, true>
     */
    protected function findActiveMerchantReferences(array $merchantReferencesByIndex): array
    {
        $merchantCollectionTransfer = $this->merchantFacade->get(
            (new MerchantCriteriaTransfer())
                ->setMerchantReferences(array_values(array_unique($merchantReferencesByIndex)))
                ->setIsActive(true),
        );

        $activeMerchantReferences = [];

        foreach ($merchantCollectionTransfer->getMerchants() as $merchantTransfer) {
            $merchantReference = $merchantTransfer->getMerchantReference();

            if ($merchantReference === null) {
                continue;
            }

            $activeMerchantReferences[$merchantReference] = true;
        }

        return $activeMerchantReferences;
    }
}
