<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\CustomModels;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Table(name="s_import_export_profile")
 * @ORM\Entity(repositoryClass="ProfileRepository")
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
     * @var Collection<string|null, Expression>
     *
     * @ORM\OneToMany(targetEntity="SwagImportExport\CustomModels\Expression", mappedBy="profile")
     */
    protected $expressions;

    /**
     * @var Collection<string|null, Session>
     *
     * @ORM\OneToMany(targetEntity="SwagImportExport\CustomModels\Session", mappedBy="profile")
     */
    protected $sessions;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=200)
     */
    protected $type;

    /**
     * @var int|null
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
     * @var string|null
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

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getBaseProfile(): ?int
    {
        return $this->baseProfile;
    }

    public function getTree(): string
    {
        return $this->tree;
    }

    /**
     * @return iterable<Expression>|null
     */
    public function getExpressions(): ?iterable
    {
        return $this->expressions;
    }

    /**
     * @return iterable<Session>
     */
    public function getSessions(): iterable
    {
        return $this->sessions;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function setBaseProfile(?int $baseProfile): void
    {
        $this->baseProfile = $baseProfile;
    }

    public function setTree(string $tree): void
    {
        $this->tree = $tree;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getHidden(): int
    {
        return $this->hidden;
    }

    public function setHidden(int $hidden): void
    {
        $this->hidden = $hidden;
    }

    public function addExpression(Expression $expression): Profile
    {
        $this->expressions[] = $expression;
        $expression->setProfile($this);

        return $this;
    }

    /**
     * Adds an session to the profile.
     */
    public function addSession(Session $session): Profile
    {
        $this->sessions[] = $session;
        $session->setProfile($this);

        return $this;
    }

    public function getDefault(): bool
    {
        return $this->default;
    }

    public function setDefault(bool $default): void
    {
        $this->default = $default;
    }
}
