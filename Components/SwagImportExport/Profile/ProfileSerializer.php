<?php

namespace Shopware\Components\SwagImportExport\Profile;

class ProfileSerializer
{
    /**
     * @var Profile $profile
     */
    private $profile;

    /**
     * @param Profile $profile
     */
    public function __construct(Profile $profile)
    {
        $this->profile = $profile;
    }

    /**
     * @param $key
     */
    public function readProfileConfig($key)
    {
        $key = ucfirst($key);
        $method = 'get' . $key;
        $this->profile->{$method}();
    }
}
