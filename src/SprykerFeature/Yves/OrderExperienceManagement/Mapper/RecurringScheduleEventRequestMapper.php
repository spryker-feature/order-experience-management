<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Mapper;

use DateTimeInterface;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleCollectionRequestTransfer;
use Generated\Shared\Transfer\RecurringScheduleEventRequestTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemAdditionTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderAcceptedItemForm;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderAddedItemForm;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderApproveForm;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringScheduleEditForm;

class RecurringScheduleEventRequestMapper implements RecurringScheduleEventRequestMapperInterface
{
    protected const string DATE_FORMAT = 'Y-m-d';

    /**
     * @param array<string, mixed> $formData
     */
    public function mapApproveFormDataToRecurringScheduleEventRequest(
        array $formData,
        CustomerTransfer $customerTransfer,
        RecurringScheduleEventRequestTransfer $recurringScheduleEventRequestTransfer,
    ): RecurringScheduleEventRequestTransfer {
        $recurringScheduleEventRequestTransfer
            ->setUuid((string)$formData[RecurringOrderApproveForm::FIELD_UUID])
            ->setIdCustomer($customerTransfer->getIdCustomerOrFail())
            ->setCustomer($customerTransfer)
            ->setScope($formData[RecurringOrderApproveForm::FIELD_SCOPE] ?? null);

        $recurringScheduleEventRequestTransfer = $this->mapAcceptedItems(
            $recurringScheduleEventRequestTransfer,
            $formData[RecurringOrderApproveForm::FIELD_ACCEPTED_ITEMS] ?? [],
        );

        $recurringScheduleEventRequestTransfer = $this->mapAddedItems(
            $recurringScheduleEventRequestTransfer,
            $formData[RecurringOrderApproveForm::FIELD_ADDED_ITEMS] ?? [],
        );

        $baseFields = [
            RecurringOrderApproveForm::FIELD_UUID,
            RecurringOrderApproveForm::FIELD_ACCEPTED_ITEMS,
            RecurringOrderApproveForm::FIELD_ADDED_ITEMS,
            RecurringOrderApproveForm::FIELD_SCOPE,
        ];

        $quoteOverrideData = $this->extractExpanderFormData($formData, $baseFields);

        return $recurringScheduleEventRequestTransfer->setQuote(
            $this->buildQuoteOverride($recurringScheduleEventRequestTransfer->getQuote(), $quoteOverrideData),
        );
    }

    /**
     * @param array<string, mixed> $formData
     */
    public function mapEditFormDataToRecurringScheduleCollectionRequest(
        array $formData,
        CustomerTransfer $customerTransfer,
        RecurringScheduleCollectionRequestTransfer $recurringScheduleCollectionRequestTransfer,
    ): RecurringScheduleCollectionRequestTransfer {
        $nextExecutionDate = $formData[RecurringScheduleEditForm::FIELD_NEXT_EXECUTION_DATE] ?? null;

        $recurringScheduleTransfer = (new RecurringScheduleTransfer())
            ->setUuid((string)$formData[RecurringScheduleEditForm::FIELD_UUID])
            ->setName($formData[RecurringScheduleEditForm::FIELD_NAME] ?? null)
            ->setCadenceType($formData[RecurringScheduleEditForm::FIELD_CADENCE_TYPE] ?? null)
            ->setCadenceValue($this->toInt($formData[RecurringScheduleEditForm::FIELD_CADENCE_VALUE] ?? null))
            ->setNextTriggerDate($nextExecutionDate instanceof DateTimeInterface ? $nextExecutionDate->format(static::DATE_FORMAT) : null);

        $baseFields = [
            RecurringScheduleEditForm::FIELD_UUID,
            RecurringScheduleEditForm::FIELD_NAME,
            RecurringScheduleEditForm::FIELD_CADENCE_TYPE,
            RecurringScheduleEditForm::FIELD_CADENCE_VALUE,
            RecurringScheduleEditForm::FIELD_NEXT_EXECUTION_DATE,
        ];

        $quoteOverrideData = $this->extractExpanderFormData($formData, $baseFields);
        $recurringScheduleTransfer->setQuote($this->buildQuoteOverride(null, $quoteOverrideData));

        return $recurringScheduleCollectionRequestTransfer
            ->setCustomer($customerTransfer)
            ->addRecurringSchedule($recurringScheduleTransfer);
    }

    /**
     * @param array<string, mixed> $quoteOverrideData
     */
    protected function buildQuoteOverride(?QuoteTransfer $quoteTransfer, array $quoteOverrideData): ?QuoteTransfer
    {
        if ($quoteOverrideData === []) {
            return $quoteTransfer;
        }

        return ($quoteTransfer ?? new QuoteTransfer())->fromArray($quoteOverrideData, true);
    }

    protected function toInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int)$value;
    }

    /**
     * @param array<string, mixed> $formData
     * @param array<int, string> $baseFields
     *
     * @return array<string, mixed>
     */
    protected function extractExpanderFormData(array $formData, array $baseFields): array
    {
        $expanderFormData = array_diff_key($formData, array_flip($baseFields));

        foreach ($expanderFormData as $key => $value) {
            $expanderFormData[$key] = $value === '' ? null : $value;
        }

        return $expanderFormData;
    }

    /**
     * @param array<int, array<string, mixed>> $acceptedItems
     */
    protected function mapAcceptedItems(
        RecurringScheduleEventRequestTransfer $recurringScheduleEventRequestTransfer,
        array $acceptedItems,
    ): RecurringScheduleEventRequestTransfer {
        foreach ($acceptedItems as $acceptedItem) {
            $groupKey = $acceptedItem[RecurringOrderAcceptedItemForm::FIELD_GROUP_KEY] ?? null;

            if ($groupKey === null) {
                continue;
            }

            $acceptedPrice = $acceptedItem[RecurringOrderAcceptedItemForm::FIELD_PRICE] ?? null;
            $acceptedQuantity = $acceptedItem[RecurringOrderAcceptedItemForm::FIELD_ACCEPTED_QUANTITY] ?? null;

            $recurringScheduleItemReviewTransfer = (new RecurringScheduleItemReviewTransfer())
                ->setRecurringScheduleItem((new RecurringScheduleItemTransfer())->setGroupKey($groupKey))
                ->setCurrentPrice($acceptedPrice !== null ? (int)$acceptedPrice : null)
                ->setAcceptedQuantity($acceptedQuantity !== null ? (int)$acceptedQuantity : null)
                ->setIsRemoved((bool)($acceptedItem[RecurringOrderAcceptedItemForm::FIELD_IS_REMOVED] ?? false));

            $recurringScheduleEventRequestTransfer->addAcceptedItem($recurringScheduleItemReviewTransfer);
        }

        return $recurringScheduleEventRequestTransfer;
    }

    /**
     * @param array<int, array<string, mixed>> $addedItems
     */
    protected function mapAddedItems(
        RecurringScheduleEventRequestTransfer $recurringScheduleEventRequestTransfer,
        array $addedItems,
    ): RecurringScheduleEventRequestTransfer {
        foreach ($addedItems as $addedItem) {
            $sku = $addedItem[RecurringOrderAddedItemForm::FIELD_SKU] ?? null;
            $quantity = $addedItem[RecurringOrderAddedItemForm::FIELD_QUANTITY] ?? null;

            if ($sku === null || $quantity === null) {
                continue;
            }

            $productOfferReference = $addedItem[RecurringOrderAddedItemForm::FIELD_PRODUCT_OFFER_REFERENCE] ?? null;
            $idShipmentMethod = $addedItem[RecurringOrderAddedItemForm::FIELD_ID_SHIPMENT_METHOD] ?? null;
            $idShippingAddress = $addedItem[RecurringOrderAddedItemForm::FIELD_ID_SHIPPING_ADDRESS] ?? null;
            $shippingAddressKey = $addedItem[RecurringOrderAddedItemForm::FIELD_SHIPPING_ADDRESS_KEY] ?? null;

            $recurringScheduleItemAdditionTransfer = (new RecurringScheduleItemAdditionTransfer())
                ->setSku((string)$sku)
                ->setQuantity((int)$quantity)
                ->setProductOfferReference($productOfferReference !== null && $productOfferReference !== '' ? (string)$productOfferReference : null)
                ->setIdShipmentMethod($idShipmentMethod !== null && $idShipmentMethod !== '' ? (int)$idShipmentMethod : null)
                ->setIdShippingAddress($idShippingAddress !== null && $idShippingAddress !== '' ? (int)$idShippingAddress : null)
                ->setShippingAddressKey($shippingAddressKey !== null && $shippingAddressKey !== '' ? (string)$shippingAddressKey : null);

            $recurringScheduleEventRequestTransfer->addAddedItem($recurringScheduleItemAdditionTransfer);
        }

        return $recurringScheduleEventRequestTransfer;
    }
}
