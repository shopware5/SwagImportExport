<?php

namespace Shopware\Components\SwagImportExport\Profile;

class ProfileSerializer
{

    private $profile;

    public function __construct(Shopware\Components\SwagImportExport\Profile\Profile $profile)
    {
        $this->profile = $profile;
    }

    public function readProfileConfig($key)
    {
        $key = ucfirst($key);
        $method = 'get' . $key;
        $this->profile->{$method}();
    }

}
