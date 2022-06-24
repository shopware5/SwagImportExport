<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Models;

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
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="SwagImportExport\Models\Profile", cascade={"persist", "refresh"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected ?Profile $profile;

    /**
     * @ORM\Column(name="variable", type="string", length=200)
     */
    protected string $variable;

    /**
     * @ORM\Column(name="export_conversion", type="text")
     */
    protected string $exportConversion;

    /**
     * @ORM\Column(name="import_conversion", type="text")
     */
    protected string $importConversion;

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
