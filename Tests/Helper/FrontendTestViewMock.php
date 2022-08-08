<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Helper;

class FrontendTestViewMock extends \Enlight_View_Default
{
    /**
     * @var array<string, mixed>
     */
    public $viewAssign = [];

    /**
     * @var string
     */
    public $templateDir;

    public function __construct()
    {
        $this->viewAssign = [];
    }

    /**
     * @param string|array<string, mixed> $key
     * @param null                        $nocache
     * @param null                        $scope
     */
    public function assign($key, $value = null, $nocache = null, $scope = null)
    {
        if (\is_array($key)) {
            $this->viewAssign = $key;

            return $this;
        }

        $this->viewAssign[$key] = $value;

        return $this;
    }

    /**
     * @param string $key
     */
    public function getAssign($key = null)
    {
        return $this->viewAssign[$key];
    }

    /**
     * @param null $key
     */
    public function addTemplateDir($templateDir, $key = null)
    {
        $this->templateDir = $templateDir;

        return $this;
    }
}
