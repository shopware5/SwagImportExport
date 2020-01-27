<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\CustomModels\ImportExport;

use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;

/**
 * Logger Model
 *
 * @ORM\Table(name="s_import_export_log")
 * @ORM\Entity(repositoryClass="Repository")
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
     * @var Session
     *
     * @ORM\ManyToOne(targetEntity="Shopware\CustomModels\ImportExport\Session", inversedBy="logs")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $session;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text", nullable=true)
     */
    protected $message;

    /**
     * Confusing naming here - indicates error state: false = no errors
     *
     * @var bool
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

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     *
     * @return Logger
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return bool
     */
    public function getStatus()
    {
        return $this->state;
    }

    /**
     * @param bool $status
     *
     * @return Logger
     */
    public function setStatus($status)
    {
        $this->state = $status;

        return $this;
    }

    /**
     * @return \Datetime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set date
     *
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

    /**
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    public function setSession(Session $session)
    {
        $this->session = $session;
    }
}
