<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\CustomModels;

use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Table(name="s_import_export_log")
 * @ORM\Entity(repositoryClass="LoggerRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Logger extends ModelEntity
{
    /**
     * Primary Key - autoincrement value
     *
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var Session|null
     *
     * @ORM\ManyToOne(targetEntity="SwagImportExport\CustomModels\Session", inversedBy="logs")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $session;

    /**
     * @var string|null
     *
     * @ORM\Column(name="message", type="text", nullable=true)
     */
    protected $message;

    /**
     * Confusing naming here - indicates error state: false = no errors
     *
     * @var string|null
     *
     * @ORM\Column(name="state", type="string", length=100, nullable=true)
     */
    protected $state;

    /**
     * @var \Datetime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): Logger
    {
        $this->message = $message;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->state;
    }

    public function setStatus(string $status): Logger
    {
        $this->state = $status;

        return $this;
    }

    public function getCreatedAt(): \Datetime
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime|string $createdAt
     *
     * @return Logger
     */
    public function setCreatedAt($createdAt = 'now')
    {
        if (!($createdAt instanceof \DateTime)) {
            $this->createdAt = new \DateTime($createdAt);
        } else {
            $this->createdAt = $createdAt;
        }

        return $this;
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(Session $session): void
    {
        $this->session = $session;
    }
}
