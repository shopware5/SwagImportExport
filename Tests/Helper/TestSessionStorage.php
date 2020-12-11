<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Helper;

use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

class TestSessionStorage extends NativeSessionStorage
{
    public function start()
    {
        session_start();

        $this->loadSession();

        return true;
    }

    public function setBags($bags)
    {
        $this->bags = $bags;
    }
}
