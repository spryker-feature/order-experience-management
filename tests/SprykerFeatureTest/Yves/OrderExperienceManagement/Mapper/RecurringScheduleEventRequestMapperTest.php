<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\OrderExperienceManagement\Mapper;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\RecurringScheduleCollectionRequestTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderPriceToleranceForm;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderPriceToleranceItemForm;
use SprykerFeature\Yves\OrderExperienceManagement\Mapper\RecurringScheduleEventRequestMapper;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group OrderExperienceManagement
 * @group Mapper
 * @group RecurringScheduleEventRequestMapperTest
 */
class RecurringScheduleEventRequestMapperTest extends Unit
{
    public function testMapsThePriceToleranceFormToASingleScheduleUpdate(): void
    {
        // Arrange
        $customerTransfer = (new CustomerTransfer())->setIdCustomer(7);

        // Act
        $requestTransfer = (new RecurringScheduleEventRequestMapper())->mapPriceToleranceFormDataToRecurringScheduleCollectionRequest(
            [
                RecurringOrderPriceToleranceForm::FIELD_UUID => 'schedule-uuid',
                RecurringOrderPriceToleranceForm::FIELD_ORDER_TOLERANCE => '2.5',
            ],
            $customerTransfer,
            new RecurringScheduleCollectionRequestTransfer(),
        );

        // Assert
        $this->assertSame($customerTransfer, $requestTransfer->getCustomer());
        $this->assertCount(1, $requestTransfer->getRecurringSchedules());

        $recurringScheduleTransfer = $requestTransfer->getRecurringSchedules()->offsetGet(0);
        $this->assertSame('schedule-uuid', $recurringScheduleTransfer->getUuid());
        $this->assertSame('2.5', $recurringScheduleTransfer->getPriceTolerancePercent()->toString());
    }

    public function testTreatsAnEmptyToleranceAsZero(): void
    {
        // Act
        $requestTransfer = (new RecurringScheduleEventRequestMapper())->mapPriceToleranceFormDataToRecurringScheduleCollectionRequest(
            [
                RecurringOrderPriceToleranceForm::FIELD_UUID => 'schedule-uuid',
                RecurringOrderPriceToleranceForm::FIELD_ORDER_TOLERANCE => null,
            ],
            new CustomerTransfer(),
            new RecurringScheduleCollectionRequestTransfer(),
        );

        // Assert
        $this->assertTrue($requestTransfer->getRecurringSchedules()->offsetGet(0)->getPriceTolerancePercent()->isZero());
    }

    public function testLeavesEveryOtherScheduleFieldUnset(): void
    {
        // Act
        $recurringScheduleTransfer = (new RecurringScheduleEventRequestMapper())->mapPriceToleranceFormDataToRecurringScheduleCollectionRequest(
            [
                RecurringOrderPriceToleranceForm::FIELD_UUID => 'schedule-uuid',
                RecurringOrderPriceToleranceForm::FIELD_ORDER_TOLERANCE => '1',
            ],
            new CustomerTransfer(),
            new RecurringScheduleCollectionRequestTransfer(),
        )->getRecurringSchedules()->offsetGet(0);

        // Assert: an update must not blank the name, cadence or next trigger date
        $this->assertNull($recurringScheduleTransfer->getName());
        $this->assertNull($recurringScheduleTransfer->getCadenceType());
        $this->assertNull($recurringScheduleTransfer->getNextTriggerDate());
    }

    public function testMapsEveryProductRowWithItsOwnToleranceAndSkipsRowsWithoutOne(): void
    {
        // Act
        $recurringScheduleTransfer = $this->mapItems([
            [RecurringOrderPriceToleranceItemForm::FIELD_GROUP_KEY => 'group-a', RecurringOrderPriceToleranceItemForm::FIELD_BUNDLE_ITEM_IDENTIFIER => null, RecurringOrderPriceToleranceItemForm::FIELD_TOLERANCE => '5'],
            [RecurringOrderPriceToleranceItemForm::FIELD_GROUP_KEY => 'group-b', RecurringOrderPriceToleranceItemForm::FIELD_BUNDLE_ITEM_IDENTIFIER => null, RecurringOrderPriceToleranceItemForm::FIELD_TOLERANCE => '0'],
            [RecurringOrderPriceToleranceItemForm::FIELD_GROUP_KEY => 'group-c', RecurringOrderPriceToleranceItemForm::FIELD_BUNDLE_ITEM_IDENTIFIER => null, RecurringOrderPriceToleranceItemForm::FIELD_TOLERANCE => null],
            [RecurringOrderPriceToleranceItemForm::FIELD_GROUP_KEY => 'group-d', RecurringOrderPriceToleranceItemForm::FIELD_BUNDLE_ITEM_IDENTIFIER => null, RecurringOrderPriceToleranceItemForm::FIELD_TOLERANCE => ''],
        ]);

        // Assert: a row posted without a value (its input is disabled while product price changes are ignored) keeps its stored tolerance
        $itemTransfers = $recurringScheduleTransfer->getItems();
        $this->assertCount(2, $itemTransfers);
        $this->assertSame('5', $itemTransfers[0]->getPriceTolerancePercent()->toString());
        $this->assertTrue($itemTransfers[1]->getPriceTolerancePercent()->isZero());
    }

    public function testIdentifiesAProductByItsGroupKeyAndABundleParentByItsBundleItemIdentifier(): void
    {
        // Act
        $itemTransfers = $this->mapItems([
            [RecurringOrderPriceToleranceItemForm::FIELD_GROUP_KEY => 'group-a', RecurringOrderPriceToleranceItemForm::FIELD_BUNDLE_ITEM_IDENTIFIER => null, RecurringOrderPriceToleranceItemForm::FIELD_TOLERANCE => '1'],
            [RecurringOrderPriceToleranceItemForm::FIELD_GROUP_KEY => null, RecurringOrderPriceToleranceItemForm::FIELD_BUNDLE_ITEM_IDENTIFIER => 'bundle-1', RecurringOrderPriceToleranceItemForm::FIELD_TOLERANCE => '2'],
        ])->getItems();

        // Assert
        $this->assertSame('group-a', $itemTransfers[0]->getGroupKey());
        $this->assertNull($itemTransfers[0]->getBundleItemIdentifier());
        $this->assertNull($itemTransfers[1]->getGroupKey());
        $this->assertSame('bundle-1', $itemTransfers[1]->getBundleItemIdentifier());
    }

    public function testMapsTheIgnoreSettingFromTheForm(): void
    {
        // Act
        $ignored = $this->mapWithIgnoreSetting(true);
        $notIgnored = $this->mapWithIgnoreSetting(false);

        // Assert: false is a value too, it has to reach the facade to switch the setting back off
        $this->assertTrue($ignored->getIsProductPriceChangeIgnored());
        $this->assertFalse($notIgnored->getIsProductPriceChangeIgnored());
        $this->assertTrue($notIgnored->isPropertyModified(RecurringScheduleTransfer::IS_PRODUCT_PRICE_CHANGE_IGNORED));
    }

    protected function mapWithIgnoreSetting(bool $isIgnored): RecurringScheduleTransfer
    {
        return (new RecurringScheduleEventRequestMapper())->mapPriceToleranceFormDataToRecurringScheduleCollectionRequest(
            [
                RecurringOrderPriceToleranceForm::FIELD_UUID => 'schedule-uuid',
                RecurringOrderPriceToleranceForm::FIELD_ORDER_TOLERANCE => '10',
                RecurringOrderPriceToleranceForm::FIELD_IGNORE_PRODUCT_PRICE_CHANGES => $isIgnored,
            ],
            new CustomerTransfer(),
            new RecurringScheduleCollectionRequestTransfer(),
        )->getRecurringSchedules()->offsetGet(0);
    }

    /**
     * @param array<int, array<string, mixed>> $itemFormData
     */
    protected function mapItems(array $itemFormData): RecurringScheduleTransfer
    {
        return (new RecurringScheduleEventRequestMapper())->mapPriceToleranceFormDataToRecurringScheduleCollectionRequest(
            [
                RecurringOrderPriceToleranceForm::FIELD_UUID => 'schedule-uuid',
                RecurringOrderPriceToleranceForm::FIELD_ORDER_TOLERANCE => '1',
                RecurringOrderPriceToleranceForm::FIELD_ITEMS => $itemFormData,
            ],
            new CustomerTransfer(),
            new RecurringScheduleCollectionRequestTransfer(),
        )->getRecurringSchedules()->offsetGet(0);
    }
}
