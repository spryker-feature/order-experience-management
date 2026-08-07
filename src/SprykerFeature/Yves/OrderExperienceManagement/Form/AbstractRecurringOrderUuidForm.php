<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Form;

use Spryker\Yves\Kernel\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

abstract class AbstractRecurringOrderUuidForm extends AbstractType
{
    public const string FIELD_UUID = 'uuid';

    protected function addUuidField(FormBuilderInterface $builder, bool $isRequired = false): static
    {
        $options = [];

        if ($isRequired) {
            $options['constraints'] = [new NotBlank()];
        }

        $builder->add(static::FIELD_UUID, HiddenType::class, $options);

        return $this;
    }
}
