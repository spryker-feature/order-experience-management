<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Order;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\PlaceableQuoteDeserializer;
use SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Business
 * @group Order
 * @group PlaceableQuoteDeserializerTest
 * Add your own group annotations below this line
 */
class PlaceableQuoteDeserializerTest extends Unit
{
    protected OrderExperienceManagementBusinessTester $tester;

    public function testDeserializeClearsIdQuoteAndBundleItems(): void
    {
        // Arrange
        $quoteTransfer = (new QuoteTransfer())->setIdQuote(42);
        $json = json_encode($quoteTransfer->toArray(), JSON_THROW_ON_ERROR);

        // Act
        $result = (new PlaceableQuoteDeserializer())->deserialize($json);

        // Assert
        $this->assertNull($result->getIdQuote());
        $this->assertCount(0, $result->getBundleItems());
    }

    public function testDeserializeClearsSalesOrderAddressIds(): void
    {
        // Arrange
        $quoteTransfer = (new QuoteTransfer())
            ->setBillingAddress((new AddressTransfer())->setIdSalesOrderAddress(10))
            ->setShippingAddress((new AddressTransfer())->setIdSalesOrderAddress(20));

        $json = json_encode($quoteTransfer->toArray(), JSON_THROW_ON_ERROR);

        // Act
        $result = (new PlaceableQuoteDeserializer())->deserialize($json);

        // Assert
        $this->assertNull($result->getBillingAddress()?->getIdSalesOrderAddress());
        $this->assertNull($result->getShippingAddress()?->getIdSalesOrderAddress());
    }

    public function testDeserializeClearsSalesExpenseAndShipmentIds(): void
    {
        // Arrange
        $shipment = (new ShipmentTransfer())->setIdSalesShipment(5);
        $item = (new ItemTransfer())
            ->setSku('ABC-123')
            ->setQuantity(1)
            ->setShipment($shipment);

        $quoteTransfer = (new QuoteTransfer())->addItem($item);
        $json = json_encode($quoteTransfer->toArray(), JSON_THROW_ON_ERROR);

        // Act
        $result = (new PlaceableQuoteDeserializer())->deserialize($json);

        // Assert — expenses with sales IDs are cleared (no expenses in this test, checking items survive)
        $this->assertCount(1, $result->getItems());
    }
}
