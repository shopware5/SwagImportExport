<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DataManagers;

abstract class DataManager
{
    public function supports(string $managerType): bool
    {
        throw new \Exception('Supports is not yet implemented');
    }

    /**
     * @return array<string, array<string>>
     */
    abstract public function getDefaultFields(): array;

    /**
     * Return type of default field
     */
    public static function getFieldType(string $record, array $mapper): string
    {
        foreach ($mapper as $type => $fields) {
            if (\in_array($record, $fields)) {
                return $type;
            }
        }

        throw new \RuntimeException('Field not found');
    }

    /**
     * Cast default value to it proper type
     */
    public static function castDefaultValue(string $value, string $type): int
    {
        switch ($type) {
            case 'id':
            case 'integer':
                return (int) $value;
            case 'boolean':
                return ($value === 'true') ? 1 : 0;
        }

        throw new \Exception(sprintf('Unknown type provided with %s', $type));
    }

    /**
     * Return fields which should be set by default
     *
     * @param array<string, array<string>> $defaultFields Contains default fields name and types
     *
     * @return array<string>
     */
    protected function getFields(array $defaultFields): array
    {
        $defaultValues = [];
        foreach ($defaultFields as $fields) {
            foreach ($fields as $field) {
                $defaultValues[] = $field;
            }
        }

        return $defaultValues;
    }

    /**
     * Return proper values for fields which have values NULL
     *
     * @return array<string, int|string>
     */
    protected function fixFieldsValues(array $records, array $fieldsValues): array
    {
        foreach ($fieldsValues as $type => $fields) {
            foreach ($fields as $field) {
                if (empty($records[$field])) {
                    switch ($type) {
                        case 'string':
                            $records[$field] = '';
                            break;
                        case 'int':
                            $records[$field] = '0';
                            break;
                        case 'float':
                            $records[$field] = '0.0';
                            break;
                        case 'date':
                            $records[$field] = \date('Y-m-d H:i:s');
                    }
                }
            }
        }

        return $records;
    }

    /**
     * Add columns which are missing because
     * doctrine property and database mismatch
     *
     * @param array<string, string|int> $records
     * @param array<string, string|int> $adapterFields
     *
     * @return array<string, mixed>
     */
    protected function mapFields(array $records, array $adapterFields): array
    {
        foreach ($adapterFields as $tableField => $adapterField) {
            if (isset($records[$adapterField])) {
                $records[$tableField] = $records[$adapterField];
            }
        }

        return $records;
    }
}
