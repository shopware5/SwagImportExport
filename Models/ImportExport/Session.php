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
     * @ORM\ManyToOne(targetEntity="Shopware\CustomModels\ImportExport\Profile", cascade={"persist", "refresh"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $profile;

    /**
     * @ORM\OneToOne(targetEntity="Shopware\CustomModels\ImportExport\Logger", cascade={"persist", "refresh"})
     * @ORM\JoinColumn(name="logger_id", referencedColumnName="id")
     */
    protected $logger;

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
     * @var string $totalCount
     *
     * @ORM\Column(name="total_count", type="integer", nullable=false)
     */
    protected $totalCount;

    /**
     * @var string $userName
     *
     * @ORM\Column(name="username", type="string", length=200, nullable=true)
     */
    protected $userName;
    
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
    * Filesize of the file in bytes
    * @var integer $filesize
    * @ORM\Column(name="file_size", type="integer", nullable=true)
    */
    protected $fileSize;

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
    
    public function getProfile()
    {
        return $this->profile;
    }

    public function getLogger()
    {
        return $this->logger;
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

    public function getTotalCount()
    {
        return $this->totalCount;
    }
    
    public function getUserName()
    {
        return $this->userName;
    }

    public function getFileName()
    {
        return $this->fileName;
    }
    
    public function getFormat()
    {
        $this->format;
    }

    public function getFileSize()
    {
        return $this->fileSize;
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
    
    /**
     * Sets the profile object.
     *
     * @param \Shopware\CustomModels\ImportExport\Profile $profile
     * @return Session
     */
    public function setProfile(\Shopware\CustomModels\ImportExport\Profile $profile = null)
    {
        $this->profile = $profile;

        return $this;
    }

    public function setLogger(\Shopware\CustomModels\ImportExport\Logger $logger = null)
    {
        $this->logger = $logger;

        return $this;
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

    public function setTotalCount($totalCount)
    {
        $this->totalCount = $totalCount;
    }

    public function setUserName($userName)
    {
        $this->userName = $userName;
    }
    
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }
    
    public function setFormat($format)
    {
        $this->format = $format;
    }

    public function setFileSize($fileSize)
    {
        $this->fileSize = $fileSize;
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
