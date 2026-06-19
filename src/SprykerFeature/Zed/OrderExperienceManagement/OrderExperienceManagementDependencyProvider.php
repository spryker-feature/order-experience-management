<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement;

use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use Spryker\Zed\Cart\Business\CartFacadeInterface;
use Spryker\Zed\Checkout\Business\CheckoutFacadeInterface;
use Spryker\Zed\CompanyUser\Business\CompanyUserFacadeInterface;
use Spryker\Zed\Customer\Business\CustomerFacadeInterface;
use Spryker\Zed\Kernel\AbstractBundleDependencyProvider;
use Spryker\Zed\Kernel\Container;
use Spryker\Zed\Locale\Business\LocaleFacadeInterface;
use Spryker\Zed\Mail\Business\MailFacadeInterface;
use Spryker\Zed\Payment\Business\PaymentFacadeInterface;
use Spryker\Zed\PriceCartConnector\Business\PriceCartConnectorFacadeInterface;
use Spryker\Zed\ProductPackagingUnit\Business\ProductPackagingUnitFacadeInterface;
use Spryker\Zed\Quote\Business\QuoteFacadeInterface;
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

    public const string FACADE_PAYMENT = 'FACADE_PAYMENT';

    public const string FACADE_COMPANY_USER = 'FACADE_COMPANY_USER';

    public const string FACADE_MAIL = 'FACADE_MAIL';

    public const string FACADE_QUOTE = 'FACADE_QUOTE';

    public const string FACADE_PRICE_CART_CONNECTOR = 'FACADE_PRICE_CART_CONNECTOR';

    public const string FACADE_PRODUCT_PACKAGING_UNIT = 'FACADE_PRODUCT_PACKAGING_UNIT';

    public const string SERVICE_ORDER_EXPERIENCE_MANAGEMENT = 'SERVICE_ORDER_EXPERIENCE_MANAGEMENT';

    public const string PLUGINS_CADENCE_TYPE = 'PLUGINS_CADENCE_TYPE';

    public const string PLUGINS_SCHEDULE_VALIDATOR = 'PLUGINS_SCHEDULE_VALIDATOR';

    public const string SERVICE_UTIL_ENCODING = 'SERVICE_UTIL_ENCODING';

    public function provideBusinessLayerDependencies(Container $container): Container
    {
        $container = parent::provideBusinessLayerDependencies($container);
        $container = $this->addCustomerFacade($container);
        $container = $this->addLocaleFacade($container);
        $container = $this->addStateMachineFacade($container);
        $container = $this->addCheckoutFacade($container);
        $container = $this->addCartFacade($container);
        $container = $this->addPaymentFacade($container);
        $container = $this->addCompanyUserFacade($container);
        $container = $this->addMailFacade($container);
        $container = $this->addQuoteFacade($container);
        $container = $this->addOrderExperienceManagementService($container);
        $container = $this->addCadenceTypePlugins($container);
        $container = $this->addScheduleValidatorPlugins($container);
        $container = $this->addUtilEncodingService($container);
        $container = $this->addPriceCartConnectorFacade($container);
        $container = $this->addProductPackagingUnitFacade($container);

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
}
