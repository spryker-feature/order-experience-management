<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Glue\OrderExperienceManagement\Api\Backend;

use Codeception\Test\Unit;
use ReflectionClass;
use ReflectionMethod;
use Spryker\Service\Container\Attributes\Plugins;
use Spryker\Service\Container\Pass\StackResolverPass;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Reader\OrderResourceReader;
use SprykerFeature\Glue\OrderExperienceManagement\OrderExperienceManagementDependencyProvider;
use SprykerFeatureTest\Glue\OrderExperienceManagement\OrderExperienceManagementGlueTester;

/**
 * The expander stack reaches both the provider and the processor through `OrderResourceReader`,
 * the single collaborator both inject and both build their resource off — so the `#[Plugins]`
 * attribute lives once, on `OrderResourceReader`'s own constructor, rather than being duplicated on
 * each entry point. It is resolved by a compiler pass at container build time — NOT by anything the
 * unit tests below it touch, since those inject the stack straight into the constructor.
 *
 * That leaves a gap only a test like this closes: a typo in the attribute's method name, a getter
 * renamed on the DependencyProvider, or a service moved to a namespace the convention no longer
 * maps back to OrderExperienceManagement all compile, pass every other test, and ship an endpoint
 * where no plugin ever runs. Each assertion here is one of those three failure modes.
 *
 * @group SprykerFeatureTest
 * @group Glue
 * @group OrderExperienceManagement
 * @group Api
 * @group Backend
 * @group OrderResourceExpanderWiringTest
 */
class OrderResourceExpanderWiringTest extends Unit
{
    protected const string EXPANDER_PLUGINS_GETTER = 'getOrderResourceExpanderPlugins';

    /**
     * @phpstan-var class-string
     */
    protected const string SERVICE_CLASS_NAME = OrderResourceReader::class;

    protected OrderExperienceManagementGlueTester $tester;

    public function testServiceDeclaresThePluginsAttributeNamingTheDependencyProviderGetter(): void
    {
        // Arrange
        $constructor = (new ReflectionClass(static::SERVICE_CLASS_NAME))->getConstructor();
        $this->assertNotNull($constructor);

        // Act
        $dependencyProviderMethods = [];

        foreach ($constructor->getParameters() as $reflectionParameter) {
            foreach ($reflectionParameter->getAttributes(Plugins::class) as $reflectionAttribute) {
                $dependencyProviderMethods[] = $reflectionAttribute->newInstance()->dependencyProviderMethod;
            }
        }

        // Assert
        $this->assertContains(static::EXPANDER_PLUGINS_GETTER, $dependencyProviderMethods);
    }

    public function testPluginsAttributeResolvesToThisModulesDependencyProvider(): void
    {
        // Arrange
        $resolveDependencyProvider = new ReflectionMethod(StackResolverPass::class, 'resolveDependencyProvider');

        // Act
        $dependencyProviderClassName = $resolveDependencyProvider->invoke(new StackResolverPass(), static::SERVICE_CLASS_NAME);

        // Assert
        $this->assertSame(OrderExperienceManagementDependencyProvider::class, $dependencyProviderClassName);
    }

    /**
     * The attribute is resolved by reflection, so a getter that does not exist fails at container
     * build time with a ReflectionException rather than anywhere near the code that renamed it.
     */
    public function testDependencyProviderExposesTheGetterTheAttributeNames(): void
    {
        // Act
        $hasGetter = method_exists(
            OrderExperienceManagementDependencyProvider::class,
            static::EXPANDER_PLUGINS_GETTER,
        );

        // Assert
        $this->assertTrue($hasGetter);
    }

    public function testDependencyProviderRegistersNoExpanderPluginByDefault(): void
    {
        // Arrange
        $getExpanderPlugins = new ReflectionMethod(
            OrderExperienceManagementDependencyProvider::class,
            static::EXPANDER_PLUGINS_GETTER,
        );

        // Act
        $orderResourceExpanderPlugins = $getExpanderPlugins->invoke(new OrderExperienceManagementDependencyProvider());

        // Assert
        $this->assertSame([], $orderResourceExpanderPlugins);
    }
}
