<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Reader;

interface OrderCommentReaderInterface
{
    public function findIdSalesOrderByOrderReference(string $orderReference): ?int;

    /**
     * @return list<\Generated\Shared\Transfer\CommentTransfer>
     */
    public function getCommentsByIdSalesOrder(int $idSalesOrder): array;
}
