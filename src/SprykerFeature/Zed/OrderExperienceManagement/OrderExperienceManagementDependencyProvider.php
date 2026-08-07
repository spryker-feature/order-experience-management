<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement;

use Spryker\Service\Customer\CustomerServiceInterface;
use Spryker\Service\Shipment\ShipmentServiceInterface;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use Spryker\Zed\Calculation\Business\CalculationFacadeInterface;
use Spryker\Zed\Cart\Business\CartFacadeInterface;
use Spryker\Zed\Checkout\Business\CheckoutFacadeInterface;
use Spryker\Zed\Company\Business\CompanyFacadeInterface;
use Spryker\Zed\CompanyBusinessUnit\Business\CompanyBusinessUnitFacadeInterface;
use Spryker\Zed\CompanyUnitAddress\Business\CompanyUnitAddressFacadeInterface;
use Spryker\Zed\CompanyUser\Business\CompanyUserFacadeInterface;
use Spryker\Zed\Customer\Business\CustomerFacadeInterface;
use Spryker\Zed\Glossary\Business\GlossaryFacadeInterface;
use Spryker\Zed\Kernel\AbstractBundleDependencyProvider;
use Spryker\Zed\Kernel\Container;
use Spryker\Zed\Locale\Business\LocaleFacadeInterface;
use Spryker\Zed\Mail\Business\MailFacadeInterface;
use Spryker\Zed\Merchant\Business\MerchantFacadeInterface;
use Spryker\Zed\MerchantProduct\Business\MerchantProductFacadeInterface;
use Spryker\Zed\Messenger\Business\MessengerFacadeInterface;
use Spryker\Zed\Payment\Business\PaymentFacadeInterface;
use Spryker\Zed\PriceCartConnector\Business\PriceCartConnectorFacadeInterface;
use Spryker\Zed\ProductMeasurementUnit\Business\ProductMeasurementUnitFacadeInterface;
use Spryker\Zed\ProductOffer\Business\ProductOfferFacadeInterface;
use Spryker\Zed\ProductPackagingUnit\Business\ProductPackagingUnitFacadeInterface;
use Spryker\Zed\Quote\Business\QuoteFacadeInterface;
use Spryker\Zed\Sales\Business\SalesFacadeInterface;
use Spryker\Zed\Shipment\Business\ShipmentFacadeInterface;
use Spryker\Zed\StateMachine\Business\StateMachineFacadeInterface;

/**
 * @method \SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig getConfig()
 */
class OrderExperienceManagementDependencyProvider extends AbstractBundleDependencyProvider
{
    public const string FACADE_CUSTOMER = 'FACADE_CUSTOMER';

    public const string FACADE_LOCALE = 'FACADE_LOCALE';

    public const string FACADE_STATE_MACHINE = 'FACADE_STATE_MACHINE';

    public const string FACADE_CHECKOUT = 'FACADE_CHECKOUT';

    public const string FACADE_CART = 'FACADE_CART';

    public const string FACADE_CALCULATION = 'FACADE_CALCULATION';

    public const string FACADE_PAYMENT = 'FACADE_PAYMENT';

    public const string FACADE_COMPANY_USER = 'FACADE_COMPANY_USER';

    public const string FACADE_COMPANY = 'FACADE_COMPANY';

    public const string FACADE_COMPANY_BUSINESS_UNIT = 'FACADE_COMPANY_BUSINESS_UNIT';

    public const string FACADE_SALES = 'FACADE_SALES';

    public const string FACADE_MERCHANT = 'FACADE_MERCHANT';

    public const string FACADE_GLOSSARY = 'FACADE_GLOSSARY';

    public const string FACADE_MAIL = 'FACADE_MAIL';

    public const string FACADE_QUOTE = 'FACADE_QUOTE';

    public const string FACADE_PRICE_CART_CONNECTOR = 'FACADE_PRICE_CART_CONNECTOR';

    public const string FACADE_MESSENGER = 'FACADE_MESSENGER';

    public const string FACADE_PRODUCT_MEASUREMENT_UNIT = 'FACADE_PRODUCT_MEASUREMENT_UNIT';

    public const string FACADE_PRODUCT_PACKAGING_UNIT = 'FACADE_PRODUCT_PACKAGING_UNIT';

    public const string FACADE_PRODUCT_OFFER = 'FACADE_PRODUCT_OFFER';

    public const string FACADE_MERCHANT_PRODUCT = 'FACADE_MERCHANT_PRODUCT';

    public const string FACADE_SHIPMENT = 'FACADE_SHIPMENT';

    public const string SERVICE_SHIPMENT = 'SERVICE_SHIPMENT';

    public const string FACADE_COMPANY_UNIT_ADDRESS = 'FACADE_COMPANY_UNIT_ADDRESS';

    public const string SERVICE_ORDER_EXPERIENCE_MANAGEMENT = 'SERVICE_ORDER_EXPERIENCE_MANAGEMENT';

    public const string PLUGINS_CADENCE_TYPE = 'PLUGINS_CADENCE_TYPE';

    public const string PLUGINS_SCHEDULE_VALIDATOR = 'PLUGINS_SCHEDULE_VALIDATOR';

    public const string PLUGINS_ADDED_ITEM_VALIDATOR = 'PLUGINS_ADDED_ITEM_VALIDATOR';

    public const string PLUGINS_RECURRING_ORDER_CHECKOUT_VALIDATOR = 'PLUGINS_RECURRING_ORDER_CHECKOUT_VALIDATOR';

    public const string SERVICE_UTIL_ENCODING = 'SERVICE_UTIL_ENCODING';

    public const string SERVICE_CUSTOMER = 'SERVICE_CUSTOMER';

    public function provideBusinessLayerDependencies(Container $container): Container
    {
        $container = parent::provideBusinessLayerDependencies($container);
        $container = $this->addCustomerFacade($container);
        $container = $this->addLocaleFacade($container);
        $container = $this->addStateMachineFacade($container);
        $container = $this->addCheckoutFacade($container);
        $container = $this->addCartFacade($container);
        $container = $this->addCalculationFacade($container);
        $container = $this->addPaymentFacade($container);
        $container = $this->addCompanyUserFacade($container);
        $container = $this->addMailFacade($container);
        $container = $this->addQuoteFacade($container);
        $container = $this->addOrderExperienceManagementService($container);
        $container = $this->addCadenceTypePlugins($container);
        $container = $this->addScheduleValidatorPlugins($container);
        $container = $this->addAddedItemValidatorPlugins($container);
        $container = $this->addUtilEncodingService($container);
        $container = $this->addPriceCartConnectorFacade($container);
        $container = $this->addProductPackagingUnitFacade($container);
        $container = $this->addProductMeasurementUnitFacade($container);
        $container = $this->addProductOfferFacade($container);
        $container = $this->addMerchantProductFacade($container);
        $container = $this->addShipmentFacade($container);
        $container = $this->addShipmentService($container);
        $container = $this->addCompanyUnitAddressFacade($container);
        $container = $this->addMessengerFacade($container);
        $container = $this->addCustomerService($container);
        $container = $this->addRecurringOrderCheckoutValidatorPlugins($container);

        return $container;
    }

    public function provideCommunicationLayerDependencies(Container $container): Container
    {
        $container = parent::provideCommunicationLayerDependencies($container);
        $container = $this->addCompanyFacade($container);
        $container = $this->addCompanyBusinessUnitFacade($container);
        $container = $this->addSalesFacade($container);
        $container = $this->addMerchantFacade($container);
        $container = $this->addGlossaryFacade($container);
        $container = $this->addLocaleFacade($container);

        return $container;
    }

    protected function addMerchantFacade(Container $container): Container
    {
        $container->set(static::FACADE_MERCHANT, function (Container $container): MerchantFacadeInterface {
            return $container->getLocator()->merchant()->facade();
        });

        return $container;
    }

    protected function addGlossaryFacade(Container $container): Container
    {
        $container->set(static::FACADE_GLOSSARY, function (Container $container): GlossaryFacadeInterface {
            return $container->getLocator()->glossary()->facade();
        });

        return $container;
    }

    protected function addCompanyFacade(Container $container): Container
    {
        $container->set(static::FACADE_COMPANY, function (Container $container): CompanyFacadeInterface {
            return $container->getLocator()->company()->facade();
        });

        return $container;
    }

    protected function addCompanyBusinessUnitFacade(Container $container): Container
    {
        $container->set(static::FACADE_COMPANY_BUSINESS_UNIT, function (Container $container): CompanyBusinessUnitFacadeInterface {
            return $container->getLocator()->companyBusinessUnit()->facade();
        });

        return $container;
    }

    protected function addSalesFacade(Container $container): Container
    {
        $container->set(static::FACADE_SALES, function (Container $container): SalesFacadeInterface {
            return $container->getLocator()->sales()->facade();
        });

        return $container;
    }

    protected function addCustomerFacade(Container $container): Container
    {
        $container->set(static::FACADE_CUSTOMER, function (Container $container): CustomerFacadeInterface {
            return $container->getLocator()->customer()->facade();
        });

        return $container;
    }

    protected function addLocaleFacade(Container $container): Container
    {
        $container->set(static::FACADE_LOCALE, function (Container $container): LocaleFacadeInterface {
            return $container->getLocator()->locale()->facade();
        });

        return $container;
    }

    protected function addStateMachineFacade(Container $container): Container
    {
        $container->set(static::FACADE_STATE_MACHINE, function (Container $container): StateMachineFacadeInterface {
            return $container->getLocator()->stateMachine()->facade();
        });

        return $container;
    }

    protected function addCheckoutFacade(Container $container): Container
    {
        $container->set(static::FACADE_CHECKOUT, function (Container $container): CheckoutFacadeInterface {
            return $container->getLocator()->checkout()->facade();
        });

        return $container;
    }

    protected function addCartFacade(Container $container): Container
    {
        $container->set(static::FACADE_CART, function (Container $container): CartFacadeInterface {
            return $container->getLocator()->cart()->facade();
        });

        return $container;
    }

    protected function addCalculationFacade(Container $container): Container
    {
        $container->set(static::FACADE_CALCULATION, function (Container $container): CalculationFacadeInterface {
            return $container->getLocator()->calculation()->facade();
        });

        return $container;
    }

    protected function addPaymentFacade(Container $container): Container
    {
        $container->set(static::FACADE_PAYMENT, function (Container $container): PaymentFacadeInterface {
            return $container->getLocator()->payment()->facade();
        });

        return $container;
    }

    protected function addCompanyUserFacade(Container $container): Container
    {
        $container->set(static::FACADE_COMPANY_USER, function (Container $container): CompanyUserFacadeInterface {
            return $container->getLocator()->companyUser()->facade();
        });

        return $container;
    }

    protected function addMailFacade(Container $container): Container
    {
        $container->set(static::FACADE_MAIL, function (Container $container): MailFacadeInterface {
            return $container->getLocator()->mail()->facade();
        });

        return $container;
    }

    protected function addQuoteFacade(Container $container): Container
    {
        $container->set(static::FACADE_QUOTE, function (Container $container): QuoteFacadeInterface {
            return $container->getLocator()->quote()->facade();
        });

        return $container;
    }

    protected function addOrderExperienceManagementService(Container $container): Container
    {
        $container->set(static::SERVICE_ORDER_EXPERIENCE_MANAGEMENT, function (Container $container) {
            return $container->getLocator()->orderExperienceManagement()->service();
        });

        return $container;
    }

    protected function addUtilEncodingService(Container $container): Container
    {
        $container->set(static::SERVICE_UTIL_ENCODING, function (Container $container): UtilEncodingServiceInterface {
            return $container->getLocator()->utilEncoding()->service();
        });

        return $container;
    }

    protected function addCustomerService(Container $container): Container
    {
        $container->set(static::SERVICE_CUSTOMER, function (Container $container): CustomerServiceInterface {
            return $container->getLocator()->customer()->service();
        });

        return $container;
    }

    protected function addCadenceTypePlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_CADENCE_TYPE, function (): array {
            return $this->getCadenceTypePlugins();
        });

        return $container;
    }

    /**
     * @return array<\SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\CadenceTypePluginInterface>
     */
    protected function getCadenceTypePlugins(): array
    {
        return [];
    }

    protected function addPriceCartConnectorFacade(Container $container): Container
    {
        $container->set(static::FACADE_PRICE_CART_CONNECTOR, function (Container $container): PriceCartConnectorFacadeInterface {
            return $container->getLocator()->priceCartConnector()->facade();
        });

        return $container;
    }

    protected function addProductPackagingUnitFacade(Container $container): Container
    {
        $container->set(static::FACADE_PRODUCT_PACKAGING_UNIT, function (Container $container): ProductPackagingUnitFacadeInterface {
            return $container->getLocator()->productPackagingUnit()->facade();
        });

        return $container;
    }

    protected function addProductMeasurementUnitFacade(Container $container): Container
    {
        $container->set(static::FACADE_PRODUCT_MEASUREMENT_UNIT, function (Container $container): ProductMeasurementUnitFacadeInterface {
            return $container->getLocator()->productMeasurementUnit()->facade();
        });

        return $container;
    }

    protected function addProductOfferFacade(Container $container): Container
    {
        $container->set(static::FACADE_PRODUCT_OFFER, function (Container $container): ProductOfferFacadeInterface {
            return $container->getLocator()->productOffer()->facade();
        });

        return $container;
    }

    protected function addMerchantProductFacade(Container $container): Container
    {
        $container->set(static::FACADE_MERCHANT_PRODUCT, function (Container $container): MerchantProductFacadeInterface {
            return $container->getLocator()->merchantProduct()->facade();
        });

        return $container;
    }

    protected function addMessengerFacade(Container $container): Container
    {
        $container->set(static::FACADE_MESSENGER, function (Container $container): MessengerFacadeInterface {
            return $container->getLocator()->messenger()->facade();
        });

        return $container;
    }

    protected function addShipmentFacade(Container $container): Container
    {
        $container->set(static::FACADE_SHIPMENT, function (Container $container): ShipmentFacadeInterface {
            return $container->getLocator()->shipment()->facade();
        });

        return $container;
    }

    protected function addShipmentService(Container $container): Container
    {
        $container->set(static::SERVICE_SHIPMENT, function (Container $container): ShipmentServiceInterface {
            return $container->getLocator()->shipment()->service();
        });

        return $container;
    }

    protected function addCompanyUnitAddressFacade(Container $container): Container
    {
        $container->set(static::FACADE_COMPANY_UNIT_ADDRESS, function (Container $container): CompanyUnitAddressFacadeInterface {
            return $container->getLocator()->companyUnitAddress()->facade();
        });

        return $container;
    }

    protected function addScheduleValidatorPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_SCHEDULE_VALIDATOR, function (): array {
            return $this->getScheduleValidatorPlugins();
        });

        return $container;
    }

    /**
     * @return array<\SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\ScheduleValidatorPluginInterface>
     */
    protected function getScheduleValidatorPlugins(): array
    {
        return [];
    }

    protected function addAddedItemValidatorPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_ADDED_ITEM_VALIDATOR, function (): array {
            return $this->getAddedItemValidatorPlugins();
        });

        return $container;
    }

    /**
     * @return array<\SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\AddedItemValidatorPluginInterface>
     */
    protected function getAddedItemValidatorPlugins(): array
    {
        return [];
    }

    protected function addRecurringOrderCheckoutValidatorPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_RECURRING_ORDER_CHECKOUT_VALIDATOR, function (): array {
            return $this->getRecurringOrderCheckoutValidatorPlugins();
        });

        return $container;
    }

    /**
     * @return array<\SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\RecurringOrderCheckoutValidatorPluginInterface>
     */
    protected function getRecurringOrderCheckoutValidatorPlugins(): array
    {
        return [];
    }
}
