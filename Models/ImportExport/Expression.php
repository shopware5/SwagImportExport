<?php

/*
  /**
 * Shopware 4.2
 * Copyright © shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */
/**
 * Shopware ImportExport Plugin
 *
 * @category  Shopware
 * @package   Shopware\Components\Console\Command
 * @copyright Copyright (c) 2014, shopware AG (http://www.shopware.de)
 */

namespace Shopware\CustomModels\ImportExport;

use Shopware\Components\Model\ModelEntity,
    Doctrine\ORM\Mapping AS ORM;

/**
 * Session Model
 *
 * @ORM\Table(name="s_import_export_expression")
 * @ORM\Entity(repositoryClass="Repository")
 * @ORM\HasLifecycleCallbacks
 */
class Expression
{

    /**
     * Primary Key - autoincrement value
     *
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Shopware\CustomModels\ImportExport\Profile", cascade={"persist", "refresh"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $profile;

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
    
    
    public function getId()
    {
        return $this->id;
    }

    public function getProfile()
    {
        return $this->profile;
    }

    public function getExportConversion()
    {
        return $this->exportConversion;
    }

    public function getImportConversion()
    {
        return $this->importConversion;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Sets the shop object.
     *
     * @param \Shopware\CustomModels\ImportExport\Profile $profile
     * @return Document
     */
    public function setProfile(\Shopware\CustomModels\ImportExport\Profile $profile = null)
    {
        $this->profile = $profile;

        return $this;
    }

    public function setExportConversion($exportConversion)
    {
        $this->exportConversion = $exportConversion;
        
        return $this;
    }

    public function setImportConversion($importConversion)
    {
        $this->importConversion = $importConversion;
        
        return $this;
    }
}
