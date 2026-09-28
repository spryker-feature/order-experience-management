<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Validator;

use Symfony\Component\HttpFoundation\Request;

/**
 * Checks the JSON types of the raw POST /orders body before API Platform deserializes it: the serializer
 * turns a scalar where an object belongs into an empty object, and cannot denormalize a scalar where a list
 * belongs at all, so neither answer names the field the client got wrong.
 */
class OrdersPayloadShapeValidator implements OrdersPayloadShapeValidatorInterface
{
    /**
     * @var list<string>
     */
    protected const array OBJECT_PATHS = ['billingAddress', 'customer', 'shipment', 'shipment.shippingAddress'];

    /**
     * @var list<string>
     */
    protected const array ITEM_OBJECT_PATHS = ['shipment', 'shipment.shippingAddress'];

    protected const string COLLECTION_PATH = 'items';

    protected const string ITEM_QUANTITY_FIELD = 'quantity';

    protected const string CONTENT_TYPE_JSON = 'json';

    protected const string GLOSSARY_KEY_MUST_BE_OBJECT = '"%s" must be an object.';

    protected const string GLOSSARY_KEY_MUST_BE_COLLECTION = '"%s" must be an array of objects.';

    protected const string GLOSSARY_KEY_QUANTITY_MUST_BE_AN_INTEGER = '"%s" must be an integer.';

    /**
     * @return list<string>
     */
    public function validatePayloadShape(Request $request): array
    {
        $attributes = $this->findRequestAttributes($request);

        if ($attributes === null) {
            return [];
        }

        $issues = [];

        foreach (static::OBJECT_PATHS as $path) {
            $issues = array_merge($issues, $this->collectObjectIssues($attributes, $path, $path));
        }

        return array_merge($issues, $this->collectCollectionIssues($attributes));
    }

    protected function findRequestAttributes(Request $request): ?object
    {
        $content = $request->getContent();

        if (trim($content) === '' || !str_contains((string)$request->headers->get('Content-Type'), static::CONTENT_TYPE_JSON)) {
            return null;
        }

        $decoded = json_decode($content);

        if (!is_object($decoded)) {
            return null;
        }

        $attributes = $decoded->data->attributes ?? $decoded;

        return is_object($attributes) ? $attributes : null;
    }

    /**
     * @return list<string>
     */
    protected function collectObjectIssues(object $attributes, string $path, string $reportedPath): array
    {
        $value = $this->findByPath($attributes, $path);

        if ($value === null || $this->isJsonObject($value)) {
            return [];
        }

        return [sprintf(static::GLOSSARY_KEY_MUST_BE_OBJECT, $reportedPath)];
    }

    /**
     * @return list<string>
     */
    protected function collectCollectionIssues(object $attributes): array
    {
        $items = $attributes->{static::COLLECTION_PATH} ?? null;

        if ($items === null) {
            return [];
        }

        if (!is_array($items) || !array_is_list($items)) {
            return [sprintf(static::GLOSSARY_KEY_MUST_BE_COLLECTION, static::COLLECTION_PATH)];
        }

        $issues = [];

        foreach ($items as $index => $item) {
            $issues = array_merge($issues, $this->collectItemIssues($item, sprintf('%s[%d]', static::COLLECTION_PATH, $index)));
        }

        return $issues;
    }

    /**
     * @return list<string>
     */
    protected function collectItemIssues(mixed $item, string $itemPath): array
    {
        if (!is_object($item)) {
            return $item === [] ? [] : [sprintf(static::GLOSSARY_KEY_MUST_BE_OBJECT, $itemPath)];
        }

        $issues = [];

        foreach (static::ITEM_OBJECT_PATHS as $path) {
            $issues = array_merge($issues, $this->collectObjectIssues($item, $path, sprintf('%s.%s', $itemPath, $path)));
        }

        return array_merge($issues, $this->collectItemQuantityIssues($item, $itemPath));
    }

    /**
     * @return list<string>
     */
    protected function collectItemQuantityIssues(object $item, string $itemPath): array
    {
        $quantity = $item->{static::ITEM_QUANTITY_FIELD} ?? null;

        if ($quantity === null || is_int($quantity)) {
            return [];
        }

        return [sprintf(static::GLOSSARY_KEY_QUANTITY_MUST_BE_AN_INTEGER, sprintf('%s.%s', $itemPath, static::ITEM_QUANTITY_FIELD))];
    }

    protected function isJsonObject(mixed $value): bool
    {
        return is_object($value) || $value === [];
    }

    protected function findByPath(object $attributes, string $path): mixed
    {
        $value = $attributes;

        foreach (explode('.', $path) as $segment) {
            if (!is_object($value) || !property_exists($value, $segment)) {
                return null;
            }

            $value = $value->{$segment};
        }

        return $value;
    }
}
