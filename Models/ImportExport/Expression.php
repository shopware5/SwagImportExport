<?php

/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Shopware ImportExport Plugin
 *
 * @category  Shopware
 * @package   Shopware\Components\Console\Command
 * @copyright Copyright (c) 2014, shopware AG (http://www.shopware.de)
 */

namespace Shopware\CustomModels\ImportExport;

use Shopware\Components\Model\ModelEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * Session Model
 *
 * @ORM\Table(name="s_import_export_expression")
 * @ORM\Entity(repositoryClass="Repository")
 * @ORM\HasLifecycleCallbacks
 */
class Expression extends ModelEntity
{
    /**
     * Primary Key - autoincrement value
     *
     * @var int $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var Profile
     *
     * @ORM\ManyToOne(targetEntity="Shopware\CustomModels\ImportExport\Profile", cascade={"persist", "refresh"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $profile;

    /**
     * @var string $variable
     *
     * @ORM\Column(name="variable", type="string", length=200)
     */
    protected $variable;

    /**
     * @var string $exportConversion
     *
     * @ORM\Column(name="export_conversion", type="text")
     */
    protected $exportConversion;

    /**
     * @var string $importConversion
     *
     * @ORM\Column(name="import_conversion", type="text")
     */
    protected $importConversion;


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Profile
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * @return string
     */
    public function getExportConversion()
    {
        return $this->exportConversion;
    }

    /**
     * @return string
     */
    public function getImportConversion()
    {
        return $this->importConversion;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getVariable()
    {
        return $this->variable;
    }

    /**
     * Sets the profile object.
     *
     * @param Profile $profile
     * @return Expression
     */
    public function setProfile(Profile $profile = null)
    {
        $this->profile = $profile;

        return $this;
    }

    /**
     * @param string $exportConversion
     * @return Expression
     */
    public function setExportConversion($exportConversion)
    {
        $this->exportConversion = $exportConversion;

        return $this;
    }

    /**
     * @param string $importConversion
     * @return Expression
     */
    public function setImportConversion($importConversion)
    {
        $this->importConversion = $importConversion;

        return $this;
    }

    /**
     * @param string $variable
     * @return Expression
     */
    public function setVariable($variable)
    {
        $this->variable = $variable;

        return $this;
    }
}
