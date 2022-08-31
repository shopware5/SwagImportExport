<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Transformers;

use SwagImportExport\Components\Profile\Profile;

/**
 * This interface defines the way the transformers must work.
 * Each of them must be able to compose headers and footers, and to transform the data in both directions.
 */
interface DataTransformerAdapter
{
    public function supports(string $type): bool;

    /**
     * Sets the main config which defines the data restructuring
     */
    public function initialize(Profile $profile): void;

    /**
     * Transforms the data in direction to formatted output file and returns the transformed data.
     *
     * @param array<string, array<int, mixed>> $data
     */
    public function transformForward(array $data): array;

    /**
     * Transforms the data in direction from formatted output file and returns the transformed data.
     *
     * @param array<string, array<int, mixed>> $data
     */
    public function transformBackward(array $data): array;
}
