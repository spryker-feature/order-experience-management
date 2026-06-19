<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\OrderExperienceManagement\Form;

use Spryker\Yves\Kernel\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * A single accepted item in the Review Required approval: the price the buyer accepted for a kept line,
 * carried together with its group key. The group key is transported as a value (not as the collection
 * field name) because group keys may contain characters (e.g. ".") that are illegal in form field names.
 */
class RecurringOrderAcceptedItemForm extends AbstractType
{
    public const string FORM_NAME = 'recurringOrderAcceptedItemForm';

    public const string FIELD_GROUP_KEY = 'groupKey';

    public const string FIELD_PRICE = 'price';

    public function getBlockPrefix(): string
    {
        return static::FORM_NAME;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(static::FIELD_GROUP_KEY, HiddenType::class, [
            'label' => false,
            'constraints' => [new NotBlank()],
        ]);

        $builder->add(static::FIELD_PRICE, HiddenType::class, [
            'label' => false,
            'constraints' => [
                new NotBlank(),
                new Type('numeric'),
                new GreaterThanOrEqual(0),
            ],
        ]);
    }
}
