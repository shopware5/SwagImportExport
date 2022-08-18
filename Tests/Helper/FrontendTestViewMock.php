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
    public array $viewAssign = [];

    public string $templateDir;

    public function __construct()
    {
        $this->viewAssign = [];
    }

    /**
     * @param string|array<string, mixed> $spec
     * @param null                        $nocache
     * @param null                        $scope
     */
    public function assign($spec, $value = null, $nocache = null, $scope = null)
    {
        if (\is_array($spec)) {
            $this->viewAssign = $spec;

            return $this;
        }

        $this->viewAssign[$spec] = $value;

        return $this;
    }

    /**
     * @param string $spec
     */
    public function getAssign($spec = null)
    {
        return $this->viewAssign[$spec];
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
