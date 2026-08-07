<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Order;

use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringOrderReloadResultTransfer;
use Spryker\Zed\Cart\Business\CartFacadeInterface;
use Spryker\Zed\Messenger\Business\MessengerFacadeInterface;

class PlaceableQuoteReloader implements PlaceableQuoteReloaderInterface
{
    public function __construct(
        protected CartFacadeInterface $cartFacade,
        protected MessengerFacadeInterface $messengerFacade,
    ) {
    }

    public function reloadItems(QuoteTransfer $quoteTransfer): RecurringOrderReloadResultTransfer
    {
        $messengerSnapshotTransfer = $this->messengerFacade->getStoredMessages();
        $previousErrorMessageCount = count($messengerSnapshotTransfer->getErrorMessages());
        $previousInfoMessageCount = count($messengerSnapshotTransfer->getInfoMessages());

        $quoteResponseTransfer = $this->cartFacade->reloadItemsInQuote($this->cloneQuoteTransfer($quoteTransfer));

        $currentMessagesTransfer = $this->messengerFacade->getStoredMessages();

        return (new RecurringOrderReloadResultTransfer())
            ->setQuoteResponse($quoteResponseTransfer)
            ->setNewErrorMessages($this->collectNewMessages($currentMessagesTransfer->getErrorMessages(), $previousErrorMessageCount))
            ->setNewInfoMessages($this->collectNewMessages($currentMessagesTransfer->getInfoMessages(), $previousInfoMessageCount));
    }

    protected function cloneQuoteTransfer(QuoteTransfer $quoteTransfer): QuoteTransfer
    {
        return (new QuoteTransfer())->fromArray($quoteTransfer->toArray(), true);
    }

    /**
     * @param array<string> $currentMessages
     *
     * @return array<string>
     */
    protected function collectNewMessages(array $currentMessages, int $previousMessageCount): array
    {
        return array_slice($currentMessages, $previousMessageCount);
    }
}
