<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Validator;

use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\OrderIntakeValidationIssueTransfer;
use Generated\Shared\Transfer\ProductOfferCriteriaTransfer;
use Generated\Shared\Transfer\ProductOfferTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Zed\ProductOffer\Business\ProductOfferFacadeInterface;

/**
 * Checks the offer a marketplace line says it is buying.
 */
class OrderIntakeProductOfferValidator implements OrderIntakeProductOfferValidatorInterface
{
    protected const string APPROVAL_STATUS_APPROVED = 'approved';

    protected const string FIELD_PATTERN = 'items[%d].productOfferReference';

    protected const string MESSAGE_OFFER_NOT_FOUND = 'Product offer "%productOfferReference%" was not found, or is not active and approved.';

    protected const string MESSAGE_OFFER_SKU_MISMATCH = 'Product offer "%productOfferReference%" sells "%offerSku%", not "%itemSku%".';

    protected const string MESSAGE_OFFER_MERCHANT_MISMATCH = 'Product offer "%productOfferReference%" belongs to merchant "%offerMerchantReference%", not "%itemMerchantReference%".';

    public function __construct(
        protected readonly ProductOfferFacadeInterface $productOfferFacade,
    ) {
    }

    public function validateProductOffers(
        QuoteTransfer $quoteTransfer,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
    ): QuoteTransfer {
        $itemTransfersByIndex = $this->findItemsWithProductOffer($quoteTransfer);

        if ($itemTransfersByIndex === []) {
            return $quoteTransfer;
        }

        $productOfferTransfersByReference = $this->findProductOffersByReference($itemTransfersByIndex);

        foreach ($itemTransfersByIndex as $index => $itemTransfer) {
            $productOfferReference = (string)$itemTransfer->getProductOfferReference();
            $productOfferTransfer = $productOfferTransfersByReference[$productOfferReference] ?? null;

            if ($productOfferTransfer === null) {
                $this->addIssue(
                    $orderIntakeResponseTransfer,
                    $index,
                    static::MESSAGE_OFFER_NOT_FOUND,
                    ['%productOfferReference%' => $productOfferReference],
                );

                continue;
            }

            $this->validateAgainstItem($orderIntakeResponseTransfer, $productOfferTransfer, $itemTransfer, $index);
        }

        return $quoteTransfer;
    }

    /**
     * @return array<int, \Generated\Shared\Transfer\ItemTransfer>
     */
    protected function findItemsWithProductOffer(QuoteTransfer $quoteTransfer): array
    {
        $itemTransfersByIndex = [];

        foreach ($quoteTransfer->getItems() as $index => $itemTransfer) {
            $productOfferReference = $itemTransfer->getProductOfferReference();

            if ($productOfferReference === null || $productOfferReference === '') {
                continue;
            }

            $itemTransfersByIndex[$index] = $itemTransfer;
        }

        return $itemTransfersByIndex;
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\ItemTransfer> $itemTransfersByIndex
     *
     * @return array<string, \Generated\Shared\Transfer\ProductOfferTransfer>
     */
    protected function findProductOffersByReference(array $itemTransfersByIndex): array
    {
        $productOfferReferences = [];

        foreach ($itemTransfersByIndex as $itemTransfer) {
            $productOfferReferences[] = (string)$itemTransfer->getProductOfferReference();
        }

        $productOfferCollectionTransfer = $this->productOfferFacade->getProductOfferCollection(
            (new ProductOfferCriteriaTransfer())
                ->setProductOfferReferences(array_values(array_unique($productOfferReferences)))
                ->setIsActive(true)
                ->setApprovalStatuses([static::APPROVAL_STATUS_APPROVED]),
        );

        $productOfferTransfersByReference = [];

        foreach ($productOfferCollectionTransfer->getProductOffers() as $productOfferTransfer) {
            $productOfferTransfersByReference[(string)$productOfferTransfer->getProductOfferReference()] = $productOfferTransfer;
        }

        return $productOfferTransfersByReference;
    }

    protected function validateAgainstItem(
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        ProductOfferTransfer $productOfferTransfer,
        ItemTransfer $itemTransfer,
        int $index,
    ): void {
        $productOfferReference = (string)$productOfferTransfer->getProductOfferReference();

        if ($productOfferTransfer->getConcreteSku() !== $itemTransfer->getSku()) {
            $this->addIssue(
                $orderIntakeResponseTransfer,
                $index,
                static::MESSAGE_OFFER_SKU_MISMATCH,
                [
                    '%productOfferReference%' => $productOfferReference,
                    '%offerSku%' => (string)$productOfferTransfer->getConcreteSku(),
                    '%itemSku%' => (string)$itemTransfer->getSku(),
                ],
            );

            return;
        }

        $merchantReference = $itemTransfer->getMerchantReference();

        if ($merchantReference === null || $merchantReference === '') {
            $itemTransfer->setMerchantReference($productOfferTransfer->getMerchantReference());

            return;
        }

        if ($merchantReference !== $productOfferTransfer->getMerchantReference()) {
            $this->addIssue(
                $orderIntakeResponseTransfer,
                $index,
                static::MESSAGE_OFFER_MERCHANT_MISMATCH,
                [
                    '%productOfferReference%' => $productOfferReference,
                    '%offerMerchantReference%' => (string)$productOfferTransfer->getMerchantReference(),
                    '%itemMerchantReference%' => $merchantReference,
                ],
            );
        }
    }

    /**
     * Adds a validation issue; `$parameters` fills the named placeholders `$message` still carries untranslated.
     *
     * @param array<string, string> $parameters
     */
    protected function addIssue(OrderIntakeResponseTransfer $orderIntakeResponseTransfer, int $index, string $message, array $parameters = []): void
    {
        $orderIntakeResponseTransfer->addValidationIssue(
            (new OrderIntakeValidationIssueTransfer())
                ->setField(sprintf(static::FIELD_PATTERN, $index))
                ->setMessage($message)
                ->setParameters($parameters),
        );
    }
}
