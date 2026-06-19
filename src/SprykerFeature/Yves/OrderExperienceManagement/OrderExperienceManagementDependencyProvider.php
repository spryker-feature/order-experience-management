<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\OrderExperienceManagement;

use Spryker\Yves\Kernel\AbstractBundleDependencyProvider;
use Spryker\Yves\Kernel\Container;

class OrderExperienceManagementDependencyProvider extends AbstractBundleDependencyProvider
{
    public const string CLIENT_CUSTOMER = 'CLIENT_CUSTOMER';

    public const string CLIENT_GLOSSARY = 'CLIENT_GLOSSARY';

    public const string CLIENT_QUOTE = 'CLIENT_QUOTE';

    public const string CLIENT_COMPANY_BUSINESS_UNIT = 'CLIENT_COMPANY_BUSINESS_UNIT';

    public const string SERVICE_ORDER_EXPERIENCE_MANAGEMENT = 'SERVICE_ORDER_EXPERIENCE_MANAGEMENT';

    public const string SERVICE_FORM_CSRF_PROVIDER = 'form.csrf_provider';

    public function provideDependencies(Container $container): Container
    {
        $container = parent::provideDependencies($container);
        $container = $this->addCustomerClient($container);
        $container = $this->addGlossaryClient($container);
        $container = $this->addQuoteClient($container);
        $container = $this->addOrderExperienceManagementService($container);
        $container = $this->addCompanyBusinessUnitClient($container);
        $container = $this->addCsrfProviderService($container);

        return $container;
    }

    public function addCustomerClient(Container $container): Container
    {
        $container->set(static::CLIENT_CUSTOMER, static function (Container $container) {
            return $container->getLocator()->customer()->client();
        });

        return $container;
    }

    public function addGlossaryClient(Container $container): Container
    {
        $container->set(static::CLIENT_GLOSSARY, static function (Container $container) {
            return $container->getLocator()->glossary()->client();
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
