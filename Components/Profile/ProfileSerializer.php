<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Profile;

class ProfileSerializer
{
    private Profile $profile;

    public function __construct(Profile $profile)
    {
        $this->profile = $profile;
    }

    public function readProfileConfig(string $key)
    {
        $key = \ucfirst($key);
        $method = 'get' . $key;
        $this->profile->{$method}();
    }
}
