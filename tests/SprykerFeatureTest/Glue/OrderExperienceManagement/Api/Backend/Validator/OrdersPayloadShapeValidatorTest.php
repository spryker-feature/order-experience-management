<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Glue\OrderExperienceManagement\Api\Backend\Validator;

use Codeception\Test\Unit;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Validator\OrdersPayloadShapeValidator;
use Symfony\Component\HttpFoundation\Request;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Glue
 * @group OrderExperienceManagement
 * @group Api
 * @group Backend
 * @group Validator
 * @group OrdersPayloadShapeValidatorTest
 * Add your own group annotations below this line
 */
class OrdersPayloadShapeValidatorTest extends Unit
{
    /**
     * The serializer casts a scalar found where an object belongs to an array, so `"shippingAddress": "Berlin"`
     * becomes an empty address reported as five missing fields, and a scalar where `items` belongs cannot be
     * denormalized at all. Reading the raw body first is what lets the offending path be named.
     *
     * @dataProvider provideMisshapenPayloads
     */
    public function testValidatePayloadShapeReportsTheOffendingPath(string $json, string $expectedIssue): void
    {
        // Act
        $issues = (new OrdersPayloadShapeValidator())->validatePayloadShape($this->createJsonRequest($json));

        // Assert
        $this->assertSame([$expectedIssue], $issues);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public function provideMisshapenPayloads(): array
    {
        return [
            'string where the shipping address belongs' => [
                '{"shipment":{"shipmentMethod":"Standard","shippingAddress":"Berlin"}}',
                '"shipment.shippingAddress" must be an object.',
            ],

            'list where the shipping address belongs' => [
                '{"shipment":{"shippingAddress":["Berlin","10115"]}}',
                '"shipment.shippingAddress" must be an object.',
            ],

            'number where the billing address belongs' => [
                '{"billingAddress":42}',
                '"billingAddress" must be an object.',
            ],

            'string where the shipment belongs' => [
                '{"shipment":"Standard"}',
                '"shipment" must be an object.',
            ],

            'string where items belong' => [
                '{"items":"abc"}',
                '"items" must be an array of objects.',
            ],

            'object where items belong' => [
                '{"items":{"sku":"001"}}',
                '"items" must be an array of objects.',
            ],

            'scalar item inside items' => [
                '{"items":["abc"]}',
                '"items[0]" must be an object.',
            ],

            'string where a per-line shipping address belongs' => [
                '{"items":[{"sku":"001","shipment":{"shippingAddress":"Berlin"}}]}',
                '"items[0].shipment.shippingAddress" must be an object.',
            ],

            'decimal quantity' => [
                '{"items":[{"sku":"001","quantity":2.5}]}',
                '"items[0].quantity" must be an integer.',
            ],

            'whole-number quantity sent as a float' => [
                '{"items":[{"sku":"001","quantity":2.0}]}',
                '"items[0].quantity" must be an integer.',
            ],

            'quantity sent as a numeric string' => [
                '{"items":[{"sku":"001","quantity":"2"}]}',
                '"items[0].quantity" must be an integer.',
            ],

            'quantity sent as a non-numeric string' => [
                '{"items":[{"sku":"001","quantity":"abc"}]}',
                '"items[0].quantity" must be an integer.',
            ],

            'JSON:API envelope is unwrapped before checking' => [
                '{"data":{"type":"orders","attributes":{"billingAddress":"Berlin"}}}',
                '"billingAddress" must be an object.',
            ],
        ];
    }

    public function testValidatePayloadShapeReportsEveryProblemInOnePass(): void
    {
        // Act
        $issues = (new OrdersPayloadShapeValidator())->validatePayloadShape(
            $this->createJsonRequest('{"billingAddress":42,"items":[{"quantity":"abc"},"abc"]}'),
        );

        // Assert
        $this->assertSame([
            '"billingAddress" must be an object.',
            '"items[0].quantity" must be an integer.',
            '"items[1]" must be an object.',
        ], $issues);
    }

    /**
     * Whether the VALUES are valid is the business validator's concern, not the shape check's.
     *
     * @dataProvider provideWellShapedPayloads
     */
    public function testValidatePayloadShapeAcceptsAWellShapedPayload(string $json): void
    {
        // Act
        $issues = (new OrdersPayloadShapeValidator())->validatePayloadShape($this->createJsonRequest($json));

        // Assert
        $this->assertSame([], $issues);
    }

    /**
     * @return array<string, array{string}>
     */
    public function provideWellShapedPayloads(): array
    {
        return [
            'complete objects' => ['{"billingAddress":{"city":"Berlin"},"shipment":{"shippingAddress":{"city":"Berlin"}},"items":[{"sku":"001"}]}'],
            'integer quantity' => ['{"items":[{"sku":"001","quantity":2}]}'],
            'absent optional objects' => ['{"items":[{"sku":"001"}]}'],
            'explicit nulls' => ['{"billingAddress":null,"shipment":{"shippingAddress":null},"items":[]}'],

            // `{}` and the `[]` a PHP client sends for an empty associative array mean the same: no fields.
            'empty object and empty array both read as an empty object' => ['{"billingAddress":{},"shipment":{"shippingAddress":[]}}'],
            'non-json body is left alone' => ['<order/>'],
            'empty body is left alone' => [''],
        ];
    }

    public function testValidatePayloadShapeLeavesARequestThatIsNotJsonAlone(): void
    {
        // Arrange
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/xml'], '{"billingAddress":42}');

        // Act
        $issues = (new OrdersPayloadShapeValidator())->validatePayloadShape($request);

        // Assert
        $this->assertSame([], $issues);
    }

    protected function createJsonRequest(string $content): Request
    {
        return new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], $content);
    }
}
