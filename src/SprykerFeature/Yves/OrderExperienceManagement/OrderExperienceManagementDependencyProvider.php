<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\OrderExperienceManagement;

use Spryker\Yves\Kernel\AbstractBundleDependencyProvider;
use Spryker\Yves\Kernel\Container;
use SprykerShop\Yves\MerchantProductOfferWidget\Form\MerchantProductOffersSelectForm;

class OrderExperienceManagementDependencyProvider extends AbstractBundleDependencyProvider
{
    public const string CLIENT_CUSTOMER = 'CLIENT_CUSTOMER';

    public const string CLIENT_GLOSSARY_STORAGE = 'CLIENT_GLOSSARY_STORAGE';

    public const string CLIENT_QUOTE = 'CLIENT_QUOTE';

    public const string CLIENT_COMPANY_BUSINESS_UNIT = 'CLIENT_COMPANY_BUSINESS_UNIT';

    public const string CLIENT_PRODUCT_OFFER_STORAGE = 'CLIENT_PRODUCT_OFFER_STORAGE';

    public const string CLIENT_SHIPMENT = 'CLIENT_SHIPMENT';

    public const string CLIENT_SHIPMENT_TYPE_STORAGE = 'CLIENT_SHIPMENT_TYPE_STORAGE';

    public const string CLIENT_PRODUCT_ALTERNATIVE_STORAGE = 'CLIENT_PRODUCT_ALTERNATIVE_STORAGE';

    public const string CLIENT_LOCALE = 'CLIENT_LOCALE';

    public const string CLIENT_CURRENCY = 'CLIENT_CURRENCY';

    public const string CLIENT_PRICE = 'CLIENT_PRICE';

    public const string CLIENT_CATALOG = 'CLIENT_CATALOG';

    public const string CLIENT_PRODUCT_STORAGE = 'CLIENT_PRODUCT_STORAGE';

    public const string CLIENT_MERCHANT_STORAGE = 'CLIENT_MERCHANT_STORAGE';

    public const string CLIENT_PRODUCT_PACKAGING_UNIT_STORAGE = 'CLIENT_PRODUCT_PACKAGING_UNIT_STORAGE';

    public const string SERVICE_ORDER_EXPERIENCE_MANAGEMENT = 'SERVICE_ORDER_EXPERIENCE_MANAGEMENT';

    public const string SERVICE_FORM_CSRF_PROVIDER = 'form.csrf_provider';

    public const string PLUGINS_RECURRING_ORDER_APPROVE_FORM_EXPANDER = 'PLUGINS_RECURRING_ORDER_APPROVE_FORM_EXPANDER';

    public const string PLUGINS_RECURRING_SCHEDULE_EDIT_FORM_EXPANDER = 'PLUGINS_RECURRING_SCHEDULE_EDIT_FORM_EXPANDER';

    public const string FORM_TYPE_ADDED_PRODUCT_OFFER = 'FORM_TYPE_ADDED_PRODUCT_OFFER';

    public const string PLUGINS_ADDED_PRODUCT_CONCRETE_RESTRICTION = 'PLUGINS_ADDED_PRODUCT_CONCRETE_RESTRICTION';

    public function provideDependencies(Container $container): Container
    {
        $container = parent::provideDependencies($container);
        $container = $this->addCustomerClient($container);
        $container = $this->addGlossaryStorageClient($container);
        $container = $this->addQuoteClient($container);
        $container = $this->addOrderExperienceManagementService($container);
        $container = $this->addCompanyBusinessUnitClient($container);
        $container = $this->addProductOfferStorageClient($container);
        $container = $this->addShipmentClient($container);
        $container = $this->addShipmentTypeStorageClient($container);
        $container = $this->addProductAlternativeStorageClient($container);
        $container = $this->addLocaleClient($container);
        $container = $this->addCurrencyClient($container);
        $container = $this->addPriceClient($container);
        $container = $this->addCatalogClient($container);
        $container = $this->addProductStorageClient($container);
        $container = $this->addMerchantStorageClient($container);
        $container = $this->addCsrfProviderService($container);
        $container = $this->addRecurringOrderApproveFormExpanderPlugins($container);
        $container = $this->addRecurringScheduleEditFormExpanderPlugins($container);
        $container = $this->addAddedProductOfferFormType($container);
        $container = $this->addProductPackagingUnitStorageClient($container);
        $container = $this->addAddedProductConcreteRestrictionPlugins($container);

        return $container;
    }

    public function addAddedProductOfferFormType(Container $container): Container
    {
        $container->set(static::FORM_TYPE_ADDED_PRODUCT_OFFER, static function () {
            return MerchantProductOffersSelectForm::class;
        });

        return $container;
    }

    public function addProductPackagingUnitStorageClient(Container $container): Container
    {
        $container->set(static::CLIENT_PRODUCT_PACKAGING_UNIT_STORAGE, static function (Container $container) {
            return $container->getLocator()->productPackagingUnitStorage()->client();
        });

        return $container;
    }

    public function addAddedProductConcreteRestrictionPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_ADDED_PRODUCT_CONCRETE_RESTRICTION, function () {
            return $this->getAddedProductConcreteRestrictionPlugins();
        });

        return $container;
    }

    public function addRecurringOrderApproveFormExpanderPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_RECURRING_ORDER_APPROVE_FORM_EXPANDER, function () {
            return $this->getRecurringOrderApproveFormExpanderPlugins();
        });

        return $container;
    }

    public function addRecurringScheduleEditFormExpanderPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_RECURRING_SCHEDULE_EDIT_FORM_EXPANDER, function () {
            return $this->getRecurringScheduleEditFormExpanderPlugins();
        });

        return $container;
    }

    /**
     * @return array<\SprykerFeature\Yves\OrderExperienceManagement\Dependency\Plugin\RecurringOrderApproveFormExpanderPluginInterface>
     */
    protected function getRecurringOrderApproveFormExpanderPlugins(): array
    {
        return [];
    }

    /**
     * @return array<\SprykerFeature\Yves\OrderExperienceManagement\Dependency\Plugin\RecurringScheduleEditFormExpanderPluginInterface>
     */
    protected function getRecurringScheduleEditFormExpanderPlugins(): array
    {
        return [];
    }

    /**
     * @return array<\SprykerFeature\Yves\OrderExperienceManagement\Dependency\Plugin\AddedProductConcreteRestrictionPluginInterface>
     */
    protected function getAddedProductConcreteRestrictionPlugins(): array
    {
        return [];
    }

    public function addCurrencyClient(Container $container): Container
    {
        $container->set(static::CLIENT_CURRENCY, static function (Container $container) {
            return $container->getLocator()->currency()->client();
        });

        return $container;
    }

    public function addPriceClient(Container $container): Container
    {
        $container->set(static::CLIENT_PRICE, static function (Container $container) {
            return $container->getLocator()->price()->client();
        });

        return $container;
    }

    public function addCatalogClient(Container $container): Container
    {
        $container->set(static::CLIENT_CATALOG, static function (Container $container) {
            return $container->getLocator()->catalog()->client();
        });

        return $container;
    }

    public function addProductStorageClient(Container $container): Container
    {
        $container->set(static::CLIENT_PRODUCT_STORAGE, static function (Container $container) {
            return $container->getLocator()->productStorage()->client();
        });

        return $container;
    }

    public function addMerchantStorageClient(Container $container): Container
    {
        $container->set(static::CLIENT_MERCHANT_STORAGE, static function (Container $container) {
            return $container->getLocator()->merchantStorage()->client();
        });

        return $container;
    }

    public function addShipmentTypeStorageClient(Container $container): Container
    {
        $container->set(static::CLIENT_SHIPMENT_TYPE_STORAGE, static function (Container $container) {
            return $container->getLocator()->shipmentTypeStorage()->client();
        });

        return $container;
    }

    public function addProductOfferStorageClient(Container $container): Container
    {
        $container->set(static::CLIENT_PRODUCT_OFFER_STORAGE, static function (Container $container) {
            return $container->getLocator()->productOfferStorage()->client();
        });

        return $container;
    }

    public function addShipmentClient(Container $container): Container
    {
        $container->set(static::CLIENT_SHIPMENT, static function (Container $container) {
            return $container->getLocator()->shipment()->client();
        });

        return $container;
    }

    public function addProductAlternativeStorageClient(Container $container): Container
    {
        $container->set(static::CLIENT_PRODUCT_ALTERNATIVE_STORAGE, static function (Container $container) {
            return $container->getLocator()->productAlternativeStorage()->client();
        });

        return $container;
    }

    public function addLocaleClient(Container $container): Container
    {
        $container->set(static::CLIENT_LOCALE, static function (Container $container) {
            return $container->getLocator()->locale()->client();
        });

        return $container;
    }

    public function addCustomerClient(Container $container): Container
    {
        $container->set(static::CLIENT_CUSTOMER, static function (Container $container) {
            return $container->getLocator()->customer()->client();
        });

        return $container;
    }

    public function addGlossaryStorageClient(Container $container): Container
    {
        $container->set(static::CLIENT_GLOSSARY_STORAGE, static function (Container $container) {
            return $container->getLocator()->glossaryStorage()->client();
        });

        return $container;
    }

    public function addQuoteClient(Container $container): Container
    {
        $container->set(static::CLIENT_QUOTE, static function (Container $container) {
            return $container->getLocator()->quote()->client();
        });

        return $container;
    }

    public function addOrderExperienceManagementService(Container $container): Container
    {
        $container->set(static::SERVICE_ORDER_EXPERIENCE_MANAGEMENT, static function (Container $container) {
            return $container->getLocator()->orderExperienceManagement()->service();
        });

        return $container;
    }

    public function addCompanyBusinessUnitClient(Container $container): Container
    {
        $container->set(static::CLIENT_COMPANY_BUSINESS_UNIT, static function (Container $container) {
            return $container->getLocator()->companyBusinessUnit()->client();
        });

        return $container;
    }

    public function addCsrfProviderService(Container $container): Container
    {
        $container->set(static::SERVICE_FORM_CSRF_PROVIDER, static function (Container $container) {
            return $container->getApplicationService(static::SERVICE_FORM_CSRF_PROVIDER);
        });

        return $container;
    }
}
