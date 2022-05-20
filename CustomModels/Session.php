<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\CustomModels;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Table(name="s_import_export_session")
 * @ORM\Entity(repositoryClass="SessionRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Session extends ModelEntity
{
    /**
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="SwagImportExport\CustomModels\Profile", inversedBy="sessions", cascade={"persist", "refresh"})
     * @ORM\JoinColumn(name="profile_id", onDelete="CASCADE")
     */
    protected ?Profile $profile;

    /**
     * @ORM\Column(name="type", type="string", length=200)
     */
    protected string $type;

    /**
     * @ORM\Column(name="ids", type="text", nullable=false)
     */
    protected ?string $ids = null;

    /**
     * @ORM\Column(name="position", type="integer", nullable=false)
     */
    protected ?int $position = null;

    /**
     * @ORM\Column(name="total_count", type="integer", nullable=false)
     */
    protected int $totalCount;

    /**
     * @ORM\Column(name="username", type="string", length=200, nullable=true)
     */
    protected ?string $userName;

    /**
     * @ORM\Column(name="file_name", type="string", length=200)
     */
    protected string $fileName;

    /**
     * @ORM\Column(name="format", type="string", length=100)
     */
    protected string $format;

    /**
     * Filesize of the file in bytes
     *
     * @ORM\Column(name="file_size", type="integer", nullable=true)
     */
    protected ?int $fileSize;

    /**
     * @ORM\Column(name="state", type="string", length=100)
     */
    protected string $state = 'new';

    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected \Datetime $createdAt;

    /**
     * @var Collection<string, Logger>
     *
     * @ORM\OneToMany(targetEntity="SwagImportExport\CustomModels\Logger", mappedBy="session")
     */
    protected Collection $logs;

    public function __construct()
    {
        $this->logs = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getProfile(): Profile
    {
        return $this->profile;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getIds(): ?string
    {
        return $this->ids;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * Sets the profile object.
     *
     * @param ?Profile $profile
     *
     * @return Session
     */
    public function setProfile(?Profile $profile = null)
    {
        $this->profile = $profile;

        return $this;
    }

    /**
     * @param string $type
     *
     * @return Session
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @param string $ids
     *
     * @return Session
     */
    public function setIds($ids)
    {
        $this->ids = $ids;

        return $this;
    }

    /**
     * @param int $position
     *
     * @return Session
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * @param int $totalCount
     *
     * @return Session
     */
    public function setTotalCount($totalCount)
    {
        $this->totalCount = $totalCount;

        return $this;
    }

    /**
     * @param ?string $userName
     *
     * @return Session
     */
    public function setUserName($userName)
    {
        $this->userName = $userName;

        return $this;
    }

    /**
     * @param string $fileName
     *
     * @return Session
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * @param string $format
     *
     * @return Session
     */
    public function setFormat($format)
    {
        $this->format = $format;

        return $this;
    }

    /**
     * @param int $fileSize
     *
     * @return Session
     */
    public function setFileSize($fileSize)
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    /**
     * @param string $state
     *
     * @return Session
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @param \DateTime $createdAt
     *
     * @return Session
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
