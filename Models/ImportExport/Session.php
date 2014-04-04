<?php

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
 * @ORM\Table(name="s_import_export_session")
 * @ORM\Entity(repositoryClass="Repository")
 * @ORM\HasLifecycleCallbacks
 */
class Session extends ModelEntity
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
     * @var string $ids
     *
     * @ORM\Column(name="ids", type="text", nullable=false)
     */
    protected $ids;

    /**
     * @var string $position
     *
     * @ORM\Column(name="position", type="integer", nullable=false)
     */
    protected $position;

    /**
     * @var string $count
     *
     * @ORM\Column(name="count", type="integer", nullable=false)
     */
    protected $count;

    /**
     * @var string $fileName
     *
     * @ORM\Column(name="file_name", type="string", length=200)
     */
    protected $fileName;
    
    /**
     * @var string $format
     *
     * @ORM\Column(name="format", type="string", length=100) 
     */
    protected $format;

    /**
     * @var boolean $state
     * 
     * @ORM\Column(name="state", type="string", length=100)
     */
    protected $state = 'new';

    /**
     * @var datetime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    public function getId()
    {
        return $this->id;
    }

    public function getProfileId()
    {
        return $this->profileId;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getIds()
    {
        return $this->ids;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function getCount()
    {
        return $this->count;
    }

    public function getFileName()
    {
        return $this->fileName;
    }
    
    public function getFormat()
    {
        $this->format;
    }

    public function getState()
    {
        return $this->state;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
    
    public function setId($id)
    {
        $this->id = $id;
    }

    public function setProfileId($profileId)
    {
        $this->profileId = $profileId;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function setIds($ids)
    {
        $this->ids = $ids;
    }

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function setCount($count)
    {
        $this->count = $count;
    }

    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }
    
    public function setFormat($format)
    {
        $this->format = $format;
    }

    public function setState($state)
    {
        $this->state = $state;
    }
    
    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Module
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

}
