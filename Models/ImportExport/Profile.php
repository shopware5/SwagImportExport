<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Shopware ImportExport Plugin
 */

namespace Shopware\CustomModels\ImportExport;

use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;

/**
 * Session Model
 *
 * @ORM\Table(name="s_import_export_profile")
 * @ORM\Entity(repositoryClass="Repository")
 * @ORM\HasLifecycleCallbacks
 */
class Profile extends ModelEntity
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
     * @var Expression[]
     *
     * @ORM\OneToMany(targetEntity="Shopware\CustomModels\ImportExport\Expression", mappedBy="profile")
     */
    protected $expressions;

    /**
     * @var Session[]
     *
     * @ORM\OneToMany(targetEntity="Shopware\CustomModels\ImportExport\Session", mappedBy="profile")
     */
    protected $sessions;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=200)
     */
    protected $type;

    /**
     * @var int
     *
     * @ORM\Column(name="base_profile", type="integer", nullable=true)
     */
    protected $baseProfile;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=200, unique=true)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var string
     *
     * @ORM\Column(name="tree", type="text")
     */
    protected $tree;

    /**
     * @var int hidden
     *
     * @ORM\Column(name="hidden", type="integer")
     */
    protected $hidden = 0;

    /**
     * @var bool
     * @ORM\Column(name="is_default", type="boolean")
     */
    protected $default = false;

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
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getBaseProfile()
    {
        return $this->baseProfile;
    }

    /**
     * @return string
     */
    public function getTree()
    {
        return $this->tree;
    }

    /**
     * @return Expression[]
     */
    public function getExpressions()
    {
        return $this->expressions;
    }

    /**
     * @return Session[]
     */
    public function getSessions()
    {
        return $this->sessions;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @param int $baseProfile
     */
    public function setBaseProfile($baseProfile)
    {
        $this->baseProfile = $baseProfile;
    }

    /**
     * @param string $tree
     */
    public function setTree($tree)
    {
        $this->tree = $tree;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return int
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * @param int $hidden
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;
    }

    /**
     * Adds an expression to the profile.
     *
     * @param Expression $expression
     *
     * @return $this
     */
    public function addExpression($expression)
    {
        $this->expressions[] = $expression;
        $expression->setProfile($this);

        return $this;
    }

    /**
     * Adds an session to the profile.
     *
     * @param Session $session
     *
     * @return $this
     */
    public function addSession($session)
    {
        $this->sessions[] = $session;
        $session->setProfile($this);

        return $this;
    }

    /**
     * @return bool
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param bool $default
     */
    public function setDefault($default)
    {
        $this->default = $default;
    }
}
