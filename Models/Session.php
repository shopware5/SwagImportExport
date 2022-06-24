<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Models;

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
     * @ORM\ManyToOne(targetEntity="SwagImportExport\Models\Profile", inversedBy="sessions", cascade={"persist", "refresh"})
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
    protected string $ids = '';

    /**
     * @ORM\Column(name="position", type="integer", nullable=false)
     */
    protected int $position = 0;

    /**
     * @ORM\Column(name="total_count", type="integer", nullable=false)
     */
    protected int $totalCount = 0;

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
    protected \DateTime $createdAt;

    /**
     * @var Collection<string, Logger>
     *
     * @ORM\OneToMany(targetEntity="SwagImportExport\Models\Logger", mappedBy="session")
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

    public function getState(): string
    {
        return $this->state;
    }

    public function getCreatedAt(): \DateTime
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
     */
    public function setProfile(?Profile $profile = null): Session
    {
        $this->profile = $profile;

        return $this;
    }

    public function setType(string $type): Session
    {
        $this->type = $type;

        return $this;
    }

    public function setIds(string $ids): Session
    {
        $this->ids = $ids;

        return $this;
    }

    public function setPosition(int $position): Session
    {
        $this->position = $position;

        return $this;
    }

    public function setTotalCount(int $totalCount): Session
    {
        $this->totalCount = $totalCount;

        return $this;
    }

    /**
     * @param ?string $userName
     */
    public function setUserName($userName): Session
    {
        $this->userName = $userName;

        return $this;
    }

    public function setFileName(string $fileName): Session
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function setFormat(string $format): Session
    {
        $this->format = $format;

        return $this;
    }

    public function setFileSize(int $fileSize): Session
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    public function setState(string $state): Session
    {
        $this->state = $state;

        return $this;
    }

    public function setCreatedAt(\DateTime $createdAt): Session
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
