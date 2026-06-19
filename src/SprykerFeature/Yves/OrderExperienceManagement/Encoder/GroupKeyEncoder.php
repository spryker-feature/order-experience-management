<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Encoder;

class GroupKeyEncoder implements GroupKeyEncoderInterface
{
    public function encode(string $groupKey): string
    {
        return bin2hex($groupKey);
    }

    public function decode(string $encodedGroupKey): string
    {
        if ($encodedGroupKey === '' || strlen($encodedGroupKey) % 2 !== 0 || !ctype_xdigit($encodedGroupKey)) {
            return '';
        }

        return (string)hex2bin($encodedGroupKey);
    }
}
