<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Reader;

use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;

class AddedItemAddressChoicesReader implements AddedItemAddressChoicesReaderInterface
{
    protected const string GLOSSARY_KEY_GROUP_SCHEDULE = 'recurring_orders.review.add_product.shipment_address.group.schedule';

    protected const string GLOSSARY_KEY_GROUP_COMPANY_UNIT_ADDRESS = 'recurring_orders.review.add_product.shipment_address.group.company_unit_address';

    /**
     * @var array<string, string>
     */
    protected const array GROUP_GLOSSARY_KEY_BY_SOURCE = [
        SharedOrderExperienceManagementConfig::SHIPPING_ADDRESS_SOURCE_SCHEDULE => self::GLOSSARY_KEY_GROUP_SCHEDULE,
        SharedOrderExperienceManagementConfig::SHIPPING_ADDRESS_SOURCE_COMPANY_UNIT_ADDRESS => self::GLOSSARY_KEY_GROUP_COMPANY_UNIT_ADDRESS,
    ];

    /**
     * @return array<string, array<string, array{label: string, idCompanyUnitAddress: string}>>
     */
    public function getAddressChoices(
        RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer,
    ): array {
        $addressChoices = [];

        foreach ($recurringScheduleReviewResponseTransfer->getShippingAddressChoices() as $choiceTransfer) {
            $key = $choiceTransfer->getKey();
            $addressTransfer = $choiceTransfer->getAddress();

            if ($key === null || $addressTransfer === null) {
                continue;
            }

            $groupGlossaryKey = $this->resolveGroupGlossaryKey((string)$choiceTransfer->getSource());

            // These keys are read as `addressChoice.label` / `addressChoice.idCompanyUnitAddress` by
            // review-shipment-selection.twig, which can only name them literally.
            $addressChoices[$groupGlossaryKey][$key] = [
                'label' => $this->buildAddressLabel($addressTransfer),
                'idCompanyUnitAddress' => (string)$choiceTransfer->getIdCompanyUnitAddress(),
            ];
        }

        return $this->sortGroups($addressChoices);
    }

    protected function resolveGroupGlossaryKey(string $source): string
    {
        return static::GROUP_GLOSSARY_KEY_BY_SOURCE[$source] ?? static::GLOSSARY_KEY_GROUP_COMPANY_UNIT_ADDRESS;
    }

    /**
     * @param array<string, array<string, array{label: string, idCompanyUnitAddress: string}>> $addressChoices
     *
     * @return array<string, array<string, array{label: string, idCompanyUnitAddress: string}>>
     */
    protected function sortGroups(array $addressChoices): array
    {
        $sortedAddressChoices = [];

        foreach (static::GROUP_GLOSSARY_KEY_BY_SOURCE as $groupGlossaryKey) {
            if (isset($addressChoices[$groupGlossaryKey])) {
                $sortedAddressChoices[$groupGlossaryKey] = $addressChoices[$groupGlossaryKey];
            }
        }

        return $sortedAddressChoices;
    }

    protected function buildAddressLabel(AddressTransfer $addressTransfer): string
    {
        $addressParts = array_filter([
            $addressTransfer->getAddress1(),
            $addressTransfer->getZipCode(),
            $addressTransfer->getCity(),
            $addressTransfer->getIso2Code(),
        ]);

        return implode(', ', $addressParts);
    }
}
