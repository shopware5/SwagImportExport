<?php

/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Shopware ImportExport Plugin
 *
 * @category  Shopware
 * @package   Shopware\Components\Console\Command
 * @copyright Copyright (c) 2014, shopware AG (http://www.shopware.de)
 */

namespace Shopware\CustomModels\ImportExport;

use Shopware\Components\Model\ModelEntity;
use Doctrine\ORM\Mapping as ORM;

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
     * @var int $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var Expression[] $expressions
     *
     * @ORM\OneToMany(targetEntity="Shopware\CustomModels\ImportExport\Expression", mappedBy="profile")
     */
    protected $expressions;

    /**
     * @var Session[] $sessions
     *
     * @ORM\OneToMany(targetEntity="Shopware\CustomModels\ImportExport\Session", mappedBy="profile")
     */
    protected $sessions;

    /**
     * @var string $type
     *
     * @ORM\Column(name="type", type="string", length=200)
     */
    protected $type;

    /**
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=200)
     */
    protected $name;

    /**
     * @var string $format
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
}
