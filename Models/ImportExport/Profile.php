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
 * @ORM\Table(name="s_import_export_profile")
 * @ORM\Entity(repositoryClass="Repository")
 * @ORM\HasLifecycleCallbacks
 */
class Profile
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
     * var integer $profileId
     * ORM\Column(name="profile", type="integer", nullable=false)
     * ORM\OneToOne(targetEntity="\Shopware\Models\Media\Album", mappedBy="album")

      private $profileId;
     */

    /**
     * @var string $type
     *
     * @ORM\Column(name="type", type="string", length=200)
     */
    protected $type;
    
    /**
     * @var text $name
     *
     * @ORM\Column(name="name", type="string", length=200) 
     */
    protected $name;

    /**
     * @var text $format
     *
     * @ORM\Column(name="tree", type="text") 
     */
    protected $tree;

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

    public function getType()
    {
        return $this->type;
    }

    public function getTree()
    {
        return $this->tree;
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

    public function setType($type)
    {
        $this->type = $type;
    }

    public function setTree(text $tree)
    {
        $this->tree = $tree;
    }

    public function setExportConversion($exportConversion)
    {
        $this->exportConversion = $exportConversion;
    }

    public function setImportConversion($importConversion)
    {
        $this->importConversion = $importConversion;
    }

}
