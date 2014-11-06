<?php

namespace Shopware\CustomModels\ImportExport;

use Shopware\Components\Model\ModelEntity,
    Doctrine\ORM\Mapping AS ORM;

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
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var Sessions[] $session
     *
     * @ORM\OneToMany(targetEntity="Shopware\CustomModels\ImportExport\Session", mappedBy="logger")
     */
    protected $session;

    /**
     * @var string $message
     * 
     * @ORM\Column(name="message", type="string", length=255, nullable=true)
     */
    protected $message;

    /**
     * @var boolean $state
     * 
     * @ORM\Column(name="state", type="string", length=100, nullable=true)
     */
    protected $state;

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

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function getStatus()
    {
        return $this->state;
    }

    public function setStatus($status)
    {
        $this->state = $status;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set date
     *
     * @param \DateTime|string $createdAt
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

    public function getSession()
    {
        return $this->session;
    }

}
