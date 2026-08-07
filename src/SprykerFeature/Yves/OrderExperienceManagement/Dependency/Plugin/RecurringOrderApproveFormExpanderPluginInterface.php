<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Dependency\Plugin;

use Symfony\Component\Form\FormBuilderInterface;

interface RecurringOrderApproveFormExpanderPluginInterface
{
    /**
     * Specification:
     * - Adds the plugin's fields (and their validation constraints) to the review approval form builder.
     *
     * @api
     *
     * @param array<string, mixed> $options
     */
    public function expandForm(FormBuilderInterface $builder, array $options): void;
}
