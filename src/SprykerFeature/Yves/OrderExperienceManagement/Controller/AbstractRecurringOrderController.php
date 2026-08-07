<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Controller;

use ArrayObject;
use Generated\Shared\Transfer\CustomerTransfer;
use SprykerShop\Yves\ShopApplication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementFactory getFactory()
 */
abstract class AbstractRecurringOrderController extends AbstractController
{
    protected const string REQUEST_PARAM_PRODUCT_OFFER_REFERENCE = 'productOfferReference';

    protected function resolveAuthenticatedCustomer(): ?CustomerTransfer
    {
        $customerTransfer = $this->getFactory()->getCustomerClient()->getCustomer();

        if ($customerTransfer === null || $customerTransfer->getIdCustomer() === null) {
            return null;
        }

        return $customerTransfer;
    }

    protected function resolveProductOfferReference(Request $request): ?string
    {
        $productOfferReference = (string)$request->query->get(static::REQUEST_PARAM_PRODUCT_OFFER_REFERENCE, '');

        return $productOfferReference !== '' ? $productOfferReference : null;
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ErrorTransfer> $errorTransfers
     */
    protected function addResponseErrorMessages(ArrayObject $errorTransfers, string $fallbackGlossaryKey): void
    {
        $messagedErrorTransfers = $this->getErrorTransfersWithMessage($errorTransfers);

        if ($messagedErrorTransfers === []) {
            $this->addErrorMessage($fallbackGlossaryKey);

            return;
        }

        $keyNames = [];

        foreach ($messagedErrorTransfers as $errorTransfer) {
            $keyNames[] = $errorTransfer->getMessageOrFail();
        }

        $translations = $this->getFactory()->getGlossaryStorageClient()->translateBulk(
            array_values(array_unique($keyNames)),
            $this->getFactory()->getLocaleClient()->getCurrentLocale(),
        );

        foreach ($messagedErrorTransfers as $errorTransfer) {
            $keyName = $errorTransfer->getMessageOrFail();
            $this->addErrorMessage(strtr($translations[$keyName] ?? $keyName, $errorTransfer->getParameters()));
        }
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ErrorTransfer> $errorTransfers
     *
     * @return array<\Generated\Shared\Transfer\ErrorTransfer>
     */
    protected function getErrorTransfersWithMessage(ArrayObject $errorTransfers): array
    {
        $messagedErrorTransfers = [];

        foreach ($errorTransfers as $errorTransfer) {
            if ($errorTransfer->getMessage() === null) {
                continue;
            }

            $messagedErrorTransfers[] = $errorTransfer;
        }

        return $messagedErrorTransfers;
    }
}
