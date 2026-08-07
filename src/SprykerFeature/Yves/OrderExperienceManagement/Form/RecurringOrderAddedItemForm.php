<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\OrderExperienceManagement\Form;

use Spryker\Yves\Kernel\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * A product the buyer adds to the schedule on the Review Required page, carried as SKU + quantity. Rows are
 * appended client-side by the add-product control; the server resolves the current price (stored as the
 * reference price) and validates the addition. The scope (this order vs every future order) is the single
 * request-level choice on the parent form, not per line.
 */
class RecurringOrderAddedItemForm extends AbstractType
{
    public const string FORM_NAME = 'recurringOrderAddedItemForm';

    public const string FIELD_SKU = 'sku';

    public const string FIELD_QUANTITY = 'quantity';

    public const string FIELD_PRODUCT_OFFER_REFERENCE = 'productOfferReference';

    public const string FIELD_ID_SHIPMENT_METHOD = 'idShipmentMethod';

    /**
     * Identifies the chosen delivery address from either source. Set for every choice; the id below stays empty
     * for an address that is only stored with the schedule and therefore has no database identifier.
     */
    public const string FIELD_SHIPPING_ADDRESS_KEY = 'shippingAddressKey';

    public const string FIELD_ID_SHIPPING_ADDRESS = 'idShippingAddress';

    public const string FIELD_PRODUCT_NAME = 'productName';

    public const string FIELD_UNIT_PRICE = 'unitPrice';

    public function getBlockPrefix(): string
    {
        return static::FORM_NAME;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(static::FIELD_SKU, HiddenType::class, [
            'label' => false,
            'constraints' => [new NotBlank()],
        ]);

        $builder->add(static::FIELD_QUANTITY, IntegerType::class, [
            'label' => false,
            'attr' => ['min' => 1],
            'constraints' => [new GreaterThan(0)],
        ]);

        $builder->add(static::FIELD_PRODUCT_OFFER_REFERENCE, HiddenType::class, [
            'required' => false,
            'label' => false,
        ]);

        $builder->add(static::FIELD_ID_SHIPMENT_METHOD, HiddenType::class, [
            'label' => false,
            'constraints' => [new NotBlank()],
        ]);

        $builder->add(static::FIELD_SHIPPING_ADDRESS_KEY, HiddenType::class, [
            'label' => false,
            'constraints' => [new NotBlank()],
        ]);

        $builder->add(static::FIELD_ID_SHIPPING_ADDRESS, HiddenType::class, [
            'required' => false,
            'label' => false,
        ]);

        $builder->add(static::FIELD_PRODUCT_NAME, HiddenType::class, [
            'required' => false,
            'label' => false,
        ]);

        $builder->add(static::FIELD_UNIT_PRICE, HiddenType::class, [
            'required' => false,
            'label' => false,
        ]);
    }
}
