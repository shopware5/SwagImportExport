<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Helper;

class TestViewMock extends \Enlight_View_Default
{
    public function __construct()
    {
        parent::__construct(new \Enlight_Template_Manager());
    }
}
