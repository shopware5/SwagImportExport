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
 * Session Model
 *
 * @ORM\Table(name="s_import_export_expression")
 * @ORM\Entity(repositoryClass="ExpressionRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Expression extends ModelEntity
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
     * @var Profile|null
     *
     * @ORM\ManyToOne(targetEntity="SwagImportExport\CustomModels\Profile", cascade={"persist", "refresh"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $profile;

    /**
     * @var string
     *
     * @ORM\Column(name="variable", type="string", length=200)
     */
    protected $variable;

    /**
     * @var string
     *
     * @ORM\Column(name="export_conversion", type="text")
     */
    protected $exportConversion;

    /**
     * @var string
     *
     * @ORM\Column(name="import_conversion", type="text")
     */
    protected $importConversion;

    public function getId(): int
    {
        return $this->id;
    }

    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    public function getExportConversion(): string
    {
        return $this->exportConversion;
    }

    public function getImportConversion(): string
    {
        return $this->importConversion;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getVariable(): string
    {
        return $this->variable;
    }

    public function setProfile(Profile $profile = null): Expression
    {
        $this->profile = $profile;

        return $this;
    }

    public function setExportConversion(string $exportConversion): Expression
    {
        $this->exportConversion = $exportConversion;

        return $this;
    }

    public function setImportConversion(string $importConversion): Expression
    {
        $this->importConversion = $importConversion;

        return $this;
    }

    public function setVariable(string $variable): Expression
    {
        $this->variable = $variable;

        return $this;
    }
}
