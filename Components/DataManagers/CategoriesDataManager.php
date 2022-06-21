<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DataManagers;

use SwagImportExport\Components\DataType\CategoryDataType;

class CategoriesDataManager extends DataManager implements \Enlight_Hook
{
    /**
     * @return array<string, array<string>>
     */
    public function getDefaultFields(): array
    {
        return CategoryDataType::$defaultFieldsForCreate;
    }

    /**
     * Return fields which should be set by default
     *
     * @return array<string>
     */
    public function getDefaultFieldsName(): array
    {
        $defaultFieldsForCreate = $this->getDefaultFields();

        return $this->getFields($defaultFieldsForCreate);
    }

    /**
     * Sets fields which are empty by default.
     *
     * @param array<string, string|int> $record
     * @param array<string, mixed>      $defaultValues
     *
     * @return array<string, mixed>
     */
    public function setDefaultFieldsForCreate(array $record, array $defaultValues): array
    {
        $getDefaultFields = $this->getDefaultFieldsName();
        foreach ($getDefaultFields as $key) {
            if (isset($record[$key])) {
                continue;
            }

            if (isset($defaultValues[$key])) {
                $record[$key] = $defaultValues[$key];
            }
        }

        return $record;
    }
}
