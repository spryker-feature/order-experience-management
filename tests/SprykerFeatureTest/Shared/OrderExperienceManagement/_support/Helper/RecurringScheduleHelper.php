<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Shared\OrderExperienceManagement\Helper;

use Codeception\Module;
use Generated\Shared\DataBuilder\AddressBuilder;
use Generated\Shared\DataBuilder\ConfigurableBundleTemplateBuilder;
use Generated\Shared\DataBuilder\ConfigurableBundleTemplateSlotBuilder;
use Generated\Shared\DataBuilder\ItemBuilder;
use Generated\Shared\DataBuilder\RecurringScheduleBuilder;
use Generated\Shared\DataBuilder\RecurringScheduleHistoryBuilder;
use Generated\Shared\DataBuilder\RecurringScheduleItemBuilder;
use Generated\Shared\DataBuilder\ShipmentBuilder;
use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\CompanyRoleCollectionTransfer;
use Generated\Shared\Transfer\CompanyRoleTransfer;
use Generated\Shared\Transfer\CompanyTransfer;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\ConfigurableBundleTemplateSlotTransfer;
use Generated\Shared\Transfer\ConfigurableBundleTemplateSlotTranslationTransfer;
use Generated\Shared\Transfer\ConfigurableBundleTemplateTransfer;
use Generated\Shared\Transfer\ConfigurableBundleTemplateTranslationTransfer;
use Generated\Shared\Transfer\ConfiguredBundleItemTransfer;
use Generated\Shared\Transfer\ConfiguredBundleTransfer;
use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\PermissionCollectionTransfer;
use Generated\Shared\Transfer\ProductConfigurationInstanceTransfer;
use Generated\Shared\Transfer\ProductMeasurementSalesUnitTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleHistoryTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Generated\Shared\Transfer\TotalsTransfer;
use Orm\Zed\ConfigurableBundle\Persistence\SpyConfigurableBundleTemplateQuery;
use Orm\Zed\ConfigurableBundle\Persistence\SpyConfigurableBundleTemplateSlotQuery;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringSchedule;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleHistory;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleHistoryQuery;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleItem;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleItemQuery;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleQuery;
use Orm\Zed\StateMachine\Persistence\SpyStateMachineItemStateHistory;
use Orm\Zed\StateMachine\Persistence\SpyStateMachineItemStateQuery;
use Orm\Zed\StateMachine\Persistence\SpyStateMachineProcessQuery;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;
use SprykerTest\Shared\Testify\Helper\DataCleanupHelperTrait;
use SprykerTest\Shared\Testify\Helper\LocatorHelperTrait;

class RecurringScheduleHelper extends Module
{
    use DataCleanupHelperTrait;
    use LocatorHelperTrait;

    protected const string OVERRIDE_BUILD_QUOTE_DATA = 'build_quote_data';

    protected const string OVERRIDE_BUILD_ITEM_DATA = 'build_item_data';

    protected const string OVERRIDE_CONFIGURATOR_KEY = 'configurator_key';

    protected const string OVERRIDE_AMOUNT = 'amount';

    protected const string OVERRIDE_AMOUNT_SALES_UNIT_ID = 'id_product_measurement_sales_unit';

    /**
     * @param array<string, mixed> $overrides
     */
    public function haveRecurringSchedule(int $idCustomer, array $overrides = []): RecurringScheduleTransfer
    {
        $buildQuoteData = (bool)($overrides[static::OVERRIDE_BUILD_QUOTE_DATA] ?? false);
        unset($overrides[static::OVERRIDE_BUILD_QUOTE_DATA]);

        $recurringScheduleTransfer = (new RecurringScheduleBuilder($overrides))->build()
            ->setIdCustomer($idCustomer);

        $recurringScheduleTransfer = $this->persistRecurringSchedule($recurringScheduleTransfer, $buildQuoteData);
        $this->initializeStateMachineState(
            $recurringScheduleTransfer->getIdRecurringScheduleOrFail(),
            $recurringScheduleTransfer->getStatusOrFail(),
        );
        $this->scheduleRecurringScheduleCleanup($recurringScheduleTransfer->getIdRecurringScheduleOrFail());

        return $recurringScheduleTransfer;
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public function haveRecurringScheduleItem(int $idRecurringSchedule, array $overrides = []): RecurringScheduleItemTransfer
    {
        $buildItemData = (bool)($overrides[static::OVERRIDE_BUILD_ITEM_DATA] ?? false);
        unset($overrides[static::OVERRIDE_BUILD_ITEM_DATA]);

        $recurringScheduleItemTransfer = (new RecurringScheduleItemBuilder($overrides))->build()
            ->setIdRecurringSchedule($idRecurringSchedule);

        return $this->persistRecurringScheduleItem($recurringScheduleItemTransfer, $buildItemData);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public function haveRecurringScheduleItemWithProductConfigurationInstance(int $idRecurringSchedule, array $overrides = []): RecurringScheduleItemTransfer
    {
        $configuratorKey = (string)($overrides[static::OVERRIDE_CONFIGURATOR_KEY] ?? 'date-time-configurator-example');
        unset($overrides[static::OVERRIDE_CONFIGURATOR_KEY]);

        $buildItemData = (bool)($overrides[static::OVERRIDE_BUILD_ITEM_DATA] ?? false);
        unset($overrides[static::OVERRIDE_BUILD_ITEM_DATA]);

        $recurringScheduleItemTransfer = (new RecurringScheduleItemBuilder($overrides))->build()
            ->setIdRecurringSchedule($idRecurringSchedule);

        return $this->persistRecurringScheduleItem($recurringScheduleItemTransfer, $buildItemData, $configuratorKey);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public function haveRecurringScheduleItemWithAmountSalesUnit(int $idRecurringSchedule, array $overrides = []): RecurringScheduleItemTransfer
    {
        $amount = $overrides[static::OVERRIDE_AMOUNT] ?? 1;
        $idProductMeasurementSalesUnit = (int)($overrides[static::OVERRIDE_AMOUNT_SALES_UNIT_ID] ?? 0);
        unset($overrides[static::OVERRIDE_AMOUNT], $overrides[static::OVERRIDE_AMOUNT_SALES_UNIT_ID]);

        $buildItemData = (bool)($overrides[static::OVERRIDE_BUILD_ITEM_DATA] ?? false);
        unset($overrides[static::OVERRIDE_BUILD_ITEM_DATA]);

        $recurringScheduleItemTransfer = (new RecurringScheduleItemBuilder($overrides))->build()
            ->setIdRecurringSchedule($idRecurringSchedule);

        if ($buildItemData) {
            $recurringScheduleItemTransfer->setItemData(
                $this->resolveAmountSalesUnitItemData($recurringScheduleItemTransfer, $idProductMeasurementSalesUnit, $amount),
            );
        }

        return $this->persistRecurringScheduleItem($recurringScheduleItemTransfer, false);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public function haveRecurringScheduleHistory(int $idRecurringSchedule, array $overrides = []): RecurringScheduleHistoryTransfer
    {
        $recurringScheduleHistoryTransfer = (new RecurringScheduleHistoryBuilder($overrides))->build()
            ->setIdRecurringSchedule($idRecurringSchedule);

        return $this->persistRecurringScheduleHistory($recurringScheduleHistoryTransfer);
    }

    /**
     * @param array<string, mixed> $seed
     */
    public function haveConfigurableBundleTemplateWithTranslation(array $seed = []): ConfigurableBundleTemplateTransfer
    {
        $configurableBundleTemplateTransfer = (new ConfigurableBundleTemplateBuilder($seed))->build();
        $localeTransfer = $this->getLocator()->locale()->facade()->getCurrentLocale();

        $configurableBundleTemplateTransfer->addTranslation(
            (new ConfigurableBundleTemplateTranslationTransfer())
                ->setLocale($localeTransfer)
                ->setName($configurableBundleTemplateTransfer->getName() ?? 'Test Configurable Bundle'),
        );

        $configurableBundleTemplateTransfer = $this->getLocator()
            ->configurableBundle()
            ->facade()
            ->createConfigurableBundleTemplate($configurableBundleTemplateTransfer)
            ->getConfigurableBundleTemplate();

        $uuid = SpyConfigurableBundleTemplateQuery::create()
            ->filterByIdConfigurableBundleTemplate($configurableBundleTemplateTransfer->getIdConfigurableBundleTemplateOrFail())
            ->select(['Uuid'])
            ->findOne();

        return $configurableBundleTemplateTransfer->setUuid((string)$uuid);
    }

    /**
     * @param array<string, mixed> $seed
     */
    public function haveConfigurableBundleTemplateSlotWithTranslation(array $seed = []): ConfigurableBundleTemplateSlotTransfer
    {
        $slotTransfer = (new ConfigurableBundleTemplateSlotBuilder($seed))->build();
        $localeTransfer = $this->getLocator()->locale()->facade()->getCurrentLocale();

        $slotTransfer->addTranslation(
            (new ConfigurableBundleTemplateSlotTranslationTransfer())
                ->setLocale($localeTransfer)
                ->setName($slotTransfer->getName() ?? 'Test Configurable Bundle Slot'),
        );

        $slotTransfer = $this->getLocator()
            ->configurableBundle()
            ->facade()
            ->createConfigurableBundleTemplateSlot($slotTransfer)
            ->getConfigurableBundleTemplateSlot();

        $uuid = SpyConfigurableBundleTemplateSlotQuery::create()
            ->filterByIdConfigurableBundleTemplateSlot($slotTransfer->getIdConfigurableBundleTemplateSlotOrFail())
            ->select(['Uuid'])
            ->findOne();

        return $slotTransfer->setUuid((string)$uuid);
    }

    public function haveCompanyUserWithPermissions(
        CompanyTransfer $companyTransfer,
        PermissionCollectionTransfer $permissionCollectionTransfer,
    ): CompanyUserTransfer {
        $companyRoleTransfer = $this->getModule('\SprykerTest\Zed\CompanyRole\Helper\CompanyRoleHelper')
            ->haveCompanyRole([
                CompanyRoleTransfer::FK_COMPANY => $companyTransfer->getIdCompany(),
                CompanyRoleTransfer::PERMISSION_COLLECTION => $permissionCollectionTransfer,
            ]);

        $businessUnitTransfer = $this->getModule('\SprykerTest\Zed\CompanyBusinessUnit\Helper\CompanyBusinessUnitHelper')
            ->haveCompanyBusinessUnit([
                'fkCompany' => $companyTransfer->getIdCompany(),
            ]);

        $customerTransfer = $this->getModule('\SprykerTest\Shared\Customer\Helper\CustomerDataHelper')
            ->haveCustomer();

        $companyUserTransfer = $this->getModule('\SprykerTest\Shared\CompanyUser\Helper\CompanyUserHelper')
            ->haveCompanyUser([
                CompanyUserTransfer::CUSTOMER => $customerTransfer,
                CompanyUserTransfer::FK_COMPANY => $companyTransfer->getIdCompany(),
                CompanyUserTransfer::FK_COMPANY_BUSINESS_UNIT => $businessUnitTransfer->getIdCompanyBusinessUnit(),
            ]);

        $companyUserTransfer
            ->setCompanyRoleCollection((new CompanyRoleCollectionTransfer())->addRole($companyRoleTransfer))
            ->setCompany($companyTransfer)
            ->setCompanyBusinessUnit($businessUnitTransfer)
            ->setCustomer($customerTransfer);

        $this->getModule('\SprykerTest\Zed\CompanyRole\Helper\CompanyRoleHelper')
            ->assignCompanyRolesToCompanyUser($companyUserTransfer);

        return $companyUserTransfer;
    }

    public function ensureRecurringScheduleTablesAreEmpty(): void
    {
        SpyRecurringScheduleHistoryQuery::create()->deleteAll();
        SpyRecurringScheduleItemQuery::create()->deleteAll();
        SpyRecurringScheduleQuery::create()->deleteAll();
    }

    protected function initializeStateMachineState(int $idRecurringSchedule, string $status): void
    {
        $stateMachineProcessEntity = SpyStateMachineProcessQuery::create()
            ->filterByStateMachineName(OrderExperienceManagementConfig::STATE_MACHINE_NAME)
            ->filterByName(OrderExperienceManagementConfig::PROCESS_NAME)
            ->findOneOrCreate();
        $stateMachineProcessEntity->save();

        $stateMachineItemStateEntity = SpyStateMachineItemStateQuery::create()
            ->filterByFkStateMachineProcess($stateMachineProcessEntity->getIdStateMachineProcess())
            ->filterByName($status)
            ->findOneOrCreate();
        $stateMachineItemStateEntity->save();

        (new SpyStateMachineItemStateHistory())
            ->setFkStateMachineItemState($stateMachineItemStateEntity->getIdStateMachineItemState())
            ->setIdentifier($idRecurringSchedule)
            ->save();
    }

    protected function resolveQuoteData(RecurringScheduleTransfer $recurringScheduleTransfer, bool $buildMinimalQuoteData = false): string
    {
        $quoteData = $recurringScheduleTransfer->getQuoteData();

        if ($quoteData !== null && $quoteData !== '{}') {
            return $quoteData;
        }

        if ($buildMinimalQuoteData) {
            return $this->buildMinimalQuoteData($recurringScheduleTransfer);
        }

        return '{}';
    }

    protected function resolveItemData(
        RecurringScheduleItemTransfer $recurringScheduleItemTransfer,
        bool $buildMinimalItemData = false,
        ?string $configuratorKey = null,
    ): string {
        $existingItemData = $recurringScheduleItemTransfer->getItemData();

        if ($existingItemData !== null && $existingItemData !== '{}') {
            return $existingItemData;
        }

        if (!$buildMinimalItemData) {
            return '{}';
        }

        $sku = $recurringScheduleItemTransfer->getSkuOrFail();

        /** @var \SprykerTest\Shared\Shipment\Helper\ShipmentMethodDataHelper $shipmentMethodDataHelper */
        $shipmentMethodDataHelper = $this->getModule('\SprykerTest\Shared\Shipment\Helper\ShipmentMethodDataHelper');
        $shipmentMethodTransfer = $shipmentMethodDataHelper->haveShipmentMethod();

        $builder = (new ItemBuilder([
            ItemTransfer::SKU => $sku,
            ItemTransfer::GROUP_KEY => $recurringScheduleItemTransfer->getGroupKey() ?? $sku,
            ItemTransfer::QUANTITY => $recurringScheduleItemTransfer->getQuantityOrFail(),
            ItemTransfer::UNIT_GROSS_PRICE => $recurringScheduleItemTransfer->getReferenceGrossPrice() ?? 0,
            ItemTransfer::UNIT_NET_PRICE => $recurringScheduleItemTransfer->getReferenceNetPrice() ?? 0,
            ItemTransfer::PRODUCT_OFFER_REFERENCE => $recurringScheduleItemTransfer->getProductOfferReference(),
            ItemTransfer::MERCHANT_REFERENCE => $recurringScheduleItemTransfer->getMerchantReference(),
            ItemTransfer::BUNDLE_ITEM_IDENTIFIER => $recurringScheduleItemTransfer->getBundleItemIdentifier(),
            ItemTransfer::RELATED_BUNDLE_ITEM_IDENTIFIER => $recurringScheduleItemTransfer->getRelatedBundleItemIdentifier(),
        ]));

        if ($recurringScheduleItemTransfer->getConfigurableBundleTemplateUuid() !== null) {
            $builder = $builder
                ->withConfiguredBundle([
                    ConfiguredBundleTransfer::TEMPLATE => [
                        ConfigurableBundleTemplateTransfer::UUID => $recurringScheduleItemTransfer->getConfigurableBundleTemplateUuid(),
                        ConfigurableBundleTemplateTransfer::NAME => $recurringScheduleItemTransfer->getConfigurableBundleName(),
                    ],
                    ConfiguredBundleTransfer::GROUP_KEY => $recurringScheduleItemTransfer->getConfiguredBundleGroupKey(),
                    ConfiguredBundleTransfer::QUANTITY => $recurringScheduleItemTransfer->getConfiguredBundleQuantity(),
                ])
                ->withConfiguredBundleItem([
                    ConfiguredBundleItemTransfer::SLOT => [
                        ConfigurableBundleTemplateSlotTransfer::UUID => $recurringScheduleItemTransfer->getConfigurableBundleTemplateSlotUuid(),
                    ],
                ]);
        }

        $itemTransfer = $builder->withShipment(
            (new ShipmentBuilder())
                ->withMethod($shipmentMethodTransfer->toArray())
                ->withShippingAddress($this->buildMockAddressTransfer()->toArray()),
        )->build();

        if ($configuratorKey !== null) {
            $itemTransfer->setProductConfigurationInstance(
                (new ProductConfigurationInstanceTransfer())
                    ->setConfiguratorKey($configuratorKey)
                    ->setIsComplete(true)
                    ->setDisplayData('{}')
                    ->setConfiguration('{}'),
            );
        }

        return json_encode($itemTransfer->toArray(), JSON_THROW_ON_ERROR);
    }

    protected function resolveAmountSalesUnitItemData(
        RecurringScheduleItemTransfer $recurringScheduleItemTransfer,
        int $idProductMeasurementSalesUnit,
        mixed $amount,
    ): string {
        $sku = $recurringScheduleItemTransfer->getSkuOrFail();

        /** @var \SprykerTest\Shared\Shipment\Helper\ShipmentMethodDataHelper $shipmentMethodDataHelper */
        $shipmentMethodDataHelper = $this->getModule('\SprykerTest\Shared\Shipment\Helper\ShipmentMethodDataHelper');
        $shipmentMethodTransfer = $shipmentMethodDataHelper->haveShipmentMethod();

        $amountSalesUnitTransfer = (new ProductMeasurementSalesUnitTransfer())
            ->setIdProductMeasurementSalesUnit($idProductMeasurementSalesUnit);

        $itemTransfer = (new ItemBuilder([
            ItemTransfer::SKU => $sku,
            ItemTransfer::GROUP_KEY => $recurringScheduleItemTransfer->getGroupKey() ?? $sku,
            ItemTransfer::QUANTITY => $recurringScheduleItemTransfer->getQuantityOrFail(),
            ItemTransfer::UNIT_GROSS_PRICE => $recurringScheduleItemTransfer->getReferenceGrossPrice() ?? 0,
            ItemTransfer::UNIT_NET_PRICE => $recurringScheduleItemTransfer->getReferenceNetPrice() ?? 0,
        ]))->withShipment(
            (new ShipmentBuilder())
                ->withMethod($shipmentMethodTransfer->toArray())
                ->withShippingAddress($this->buildMockAddressTransfer()->toArray()),
        )->build()
            ->setAmount((string)$amount)
            ->setAmountSalesUnit($amountSalesUnitTransfer)
            ->setQuantitySalesUnit($amountSalesUnitTransfer);

        return json_encode($itemTransfer->toArray(), JSON_THROW_ON_ERROR);
    }

    protected function buildMinimalQuoteData(RecurringScheduleTransfer $recurringScheduleTransfer): string
    {
        $customerTransfer = $this->getModule('\SprykerTest\Shared\Customer\Helper\CustomerDataHelper')
            ->haveConfirmedCustomer(['locale_name' => 'en_US']);

        $storeTransfer = $this->getModule('\SprykerTest\Shared\Store\Helper\StoreDataHelper')
            ->haveStore([StoreTransfer::NAME => $recurringScheduleTransfer->getStoreNameOrFail()]);

        $paymentTransfer = (new PaymentTransfer())
            ->setPaymentMethod($recurringScheduleTransfer->getPaymentMethodOrFail())
            ->setPaymentProvider('DummyPayment')
            ->setPaymentSelection('dummyPaymentInvoice');

        $totalsTransfer = (new TotalsTransfer())
            ->setGrandTotal(0)
            ->setSubtotal(0);

        $currencyTransfer = (new CurrencyTransfer())
            ->setCode($recurringScheduleTransfer->getCurrencyIsoCodeOrFail());

        $addressTransfer = $this->buildMockAddressTransfer();

        $quoteTransfer = (new QuoteTransfer())
            ->setCustomer($customerTransfer)
            ->setCustomerReference($customerTransfer->getCustomerReferenceOrFail())
            ->setStore($storeTransfer)
            ->setCurrency($currencyTransfer)
            ->setPriceMode($recurringScheduleTransfer->getPriceModeOrFail())
            ->setPayment($paymentTransfer)
            ->setTotals($totalsTransfer)
            ->setBillingAddress($addressTransfer)
            ->setShippingAddress($addressTransfer);

        return json_encode($quoteTransfer->toArray(), JSON_THROW_ON_ERROR);
    }

    protected function buildMockAddressTransfer(): AddressTransfer
    {
        return (new AddressBuilder())->build();
    }

    protected function persistRecurringSchedule(RecurringScheduleTransfer $recurringScheduleTransfer, bool $buildQuoteData = false): RecurringScheduleTransfer
    {
        $recurringScheduleEntity = (new SpyRecurringSchedule())
            ->setFkCustomer($recurringScheduleTransfer->getIdCustomerOrFail())
            ->setFkCompanyUser($recurringScheduleTransfer->getIdCompanyUser())
            ->setName($recurringScheduleTransfer->getName())
            ->setCadenceType($recurringScheduleTransfer->getCadenceTypeOrFail())
            ->setCadenceValue($recurringScheduleTransfer->getCadenceValue())
            ->setFirstTriggerDate($recurringScheduleTransfer->getFirstTriggerDateOrFail())
            ->setNextTriggerDate($recurringScheduleTransfer->getNextTriggerDateOrFail())
            ->setStatus($recurringScheduleTransfer->getStatusOrFail())
            ->setPaymentMethod($recurringScheduleTransfer->getPaymentMethodOrFail())
            ->setStoreName($recurringScheduleTransfer->getStoreNameOrFail())
            ->setCurrencyIsoCode($recurringScheduleTransfer->getCurrencyIsoCodeOrFail())
            ->setPriceMode($recurringScheduleTransfer->getPriceModeOrFail())
            ->setCustomerReference($recurringScheduleTransfer->getCustomerReference())
            ->setQuoteData($this->resolveQuoteData($recurringScheduleTransfer, $buildQuoteData));

        $recurringScheduleEntity->save();

        return $recurringScheduleTransfer
            ->setIdRecurringSchedule($recurringScheduleEntity->getIdRecurringSchedule())
            ->setUuid($recurringScheduleEntity->getUuid());
    }

    protected function persistRecurringScheduleItem(
        RecurringScheduleItemTransfer $recurringScheduleItemTransfer,
        bool $buildItemData = false,
        ?string $configuratorKey = null,
    ): RecurringScheduleItemTransfer {
        $sku = $recurringScheduleItemTransfer->getSkuOrFail();
        $groupKey = $recurringScheduleItemTransfer->getGroupKey() ?? $sku;

        $recurringScheduleItemEntity = (new SpyRecurringScheduleItem())
            ->setFkRecurringSchedule($recurringScheduleItemTransfer->getIdRecurringScheduleOrFail())
            ->setSku($sku)
            ->setQuantity($recurringScheduleItemTransfer->getQuantityOrFail())
            ->setReferenceGrossPrice($recurringScheduleItemTransfer->getReferenceGrossPriceOrFail())
            ->setReferenceNetPrice($recurringScheduleItemTransfer->getReferenceNetPriceOrFail())
            ->setGroupKey($groupKey)
            ->setBundleItemIdentifier($recurringScheduleItemTransfer->getBundleItemIdentifier())
            ->setRelatedBundleItemIdentifier($recurringScheduleItemTransfer->getRelatedBundleItemIdentifier())
            ->setConfigurableBundleTemplateUuid($recurringScheduleItemTransfer->getConfigurableBundleTemplateUuid())
            ->setConfiguredBundleGroupKey($recurringScheduleItemTransfer->getConfiguredBundleGroupKey())
            ->setConfigurableBundleName($recurringScheduleItemTransfer->getConfigurableBundleName())
            ->setConfiguredBundleQuantity($recurringScheduleItemTransfer->getConfiguredBundleQuantity())
            ->setItemData($this->resolveItemData($recurringScheduleItemTransfer, $buildItemData, $configuratorKey));

        $recurringScheduleItemEntity->save();

        return $recurringScheduleItemTransfer
            ->setIdRecurringScheduleItem($recurringScheduleItemEntity->getIdRecurringScheduleItem());
    }

    protected function persistRecurringScheduleHistory(RecurringScheduleHistoryTransfer $recurringScheduleHistoryTransfer): RecurringScheduleHistoryTransfer
    {
        $recurringScheduleHistoryEntity = (new SpyRecurringScheduleHistory())
            ->setFkRecurringSchedule($recurringScheduleHistoryTransfer->getIdRecurringScheduleOrFail())
            ->setFkSalesOrder($recurringScheduleHistoryTransfer->getIdSalesOrder())
            ->setEventType($recurringScheduleHistoryTransfer->getEventTypeOrFail())
            ->setDetail($recurringScheduleHistoryTransfer->getDetail());

        $recurringScheduleHistoryEntity->save();

        return $recurringScheduleHistoryTransfer
            ->setIdRecurringScheduleHistory($recurringScheduleHistoryEntity->getIdRecurringScheduleHistory());
    }

    protected function scheduleRecurringScheduleCleanup(int $idRecurringSchedule): void
    {
        $this->getDataCleanupHelper()->_addCleanup(function () use ($idRecurringSchedule): void {
            SpyRecurringScheduleHistoryQuery::create()->filterByFkRecurringSchedule($idRecurringSchedule)->delete();
            SpyRecurringScheduleItemQuery::create()->filterByFkRecurringSchedule($idRecurringSchedule)->delete();
            SpyRecurringScheduleQuery::create()->filterByIdRecurringSchedule($idRecurringSchedule)->delete();
        });
    }
}
