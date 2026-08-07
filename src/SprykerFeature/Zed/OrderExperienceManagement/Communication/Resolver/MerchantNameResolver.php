<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Communication\Resolver;

use Generated\Shared\Transfer\MerchantCriteriaTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Spryker\Zed\Merchant\Business\MerchantFacadeInterface;

class MerchantNameResolver
{
    public function __construct(protected readonly MerchantFacadeInterface $merchantFacade)
    {
    }

    /**
     * @return array<string, string> Keys are merchant references, values are the merchant names.
     */
    public function getMerchantNamesByReference(RecurringScheduleTransfer $recurringScheduleTransfer): array
    {
        $merchantReferences = $this->extractMerchantReferences($recurringScheduleTransfer);

        if ($merchantReferences === []) {
            return [];
        }

        $merchantCollectionTransfer = $this->merchantFacade->get(
            (new MerchantCriteriaTransfer())->setMerchantReferences($merchantReferences),
        );

        $merchantNamesByReference = [];

        foreach ($merchantCollectionTransfer->getMerchants() as $merchantTransfer) {
            $merchantNamesByReference[$merchantTransfer->getMerchantReferenceOrFail()] = $merchantTransfer->getNameOrFail();
        }

        return $merchantNamesByReference;
    }

    /**
     * @return list<string>
     */
    protected function extractMerchantReferences(RecurringScheduleTransfer $recurringScheduleTransfer): array
    {
        $merchantReferences = [];

        foreach ($recurringScheduleTransfer->getItems() as $recurringScheduleItemTransfer) {
            $merchantReference = $recurringScheduleItemTransfer->getMerchantReference();

            if ($merchantReference === null) {
                continue;
            }

            $merchantReferences[$merchantReference] = true;
        }

        return array_keys($merchantReferences);
    }
}
