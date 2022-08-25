<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DbAdapters;

use Doctrine\DBAL\Connection;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Translation\Translation;
use SwagImportExport\Components\Exception\AdapterException;
use SwagImportExport\Components\Utils\SnippetsHelper;
use SwagImportExport\Components\Validators\CategoryTranslationValidator;

class CategoryTranslationDbAdapter implements DataDbAdapter, \Enlight_Hook
{
    protected ModelManager $manager;

    protected \Shopware_Components_Translation $translationComponent;

    protected bool $importExportErrorMode;

    /**
     * @var array<string>
     */
    protected array $logMessages = [];

    protected ?string $logState = null;

    protected CategoryTranslationValidator $validator;

    protected \Enlight_Event_EventManager $eventManager;

    private Connection $connection;

    public function __construct(
        \Shopware_Components_Translation $translationComponent,
        ModelManager $entityManager,
        Connection $connection,
        \Enlight_Event_EventManager $eventManager,
        \Shopware_Components_Config $config
    ) {
        $this->translationComponent = $translationComponent;
        $this->manager = $entityManager;
        $this->connection = $connection;
        $this->eventManager = $eventManager;
        $this->importExportErrorMode = (bool) $config->get('SwagImportExportErrorMode');

        $this->validator = new CategoryTranslationValidator();
    }

    public function supports(string $adapter): bool
    {
        return $adapter === DataDbAdapter::CATEGORIES_TRANSLATION_ADAPTER;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultColumns(): array
    {
        return [
            'c.categoryId as categoryId',
            't.languageId as languageId',
            't.description as description',
            't.external as external',
            't.externalTarget as externalTarget',
            't.imagePath as imagePath',
            't.cmsheadline as cmsheadline',
            't.cmstext as cmstext',
            't.metatitle as metatitle',
            't.metadescription as metadescription',
            't.metakeywords as metakeywords',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function read(array $ids, array $columns): array
    {
        if (empty($ids)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/translations/no_ids', 'Can not read translations without ids.');
            throw new \Exception($message);
        }

        if (empty($columns)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/translations/no_column_names', 'Can not read translations without column names.');
            throw new \Exception($message);
        }

        $translationRows = $this->getTranslations($ids);

        if (!$translationRows) {
            return [];
        }

        foreach ($translationRows as &$translationRow) {
            $translation = \unserialize($translationRow['objectdata']);

            if (!$translation) {
                continue;
            }

            $translationRow = \array_merge($translationRow, $translation);
        }

        return ['default' => $translationRows];
    }

    /**
     * {@inheritdoc}
     */
    public function readRecordIds(?int $start, ?int $limit, array $filter = []): array
    {
        $builder = $this->manager->createQueryBuilder();

        $builder->select('t.id')
            ->from(Translation::class, 't')
            ->where('t.type = :type')
            ->setParameter('type', 'category');

        if ($start) {
            $builder->setFirstResult($start);
        }

        if ($limit) {
            $builder->setMaxResults($limit);
        }

        $records = $builder->getQuery()->getArrayResult();

        return \array_column($records, 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function getSections(): array
    {
        return [
            ['id' => 'default', 'name' => 'default '],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getColumns(string $section): array
    {
        $method = 'get' . \ucfirst($section) . 'Columns';

        if (\method_exists($this, $method)) {
            return $this->{$method}();
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $records): void
    {
        $records = $this->eventManager->filter(
            'Shopware_Components_SwagImportExport_DbAdapters_CategoryTranslationsDbAdapter_Write',
            $records,
            ['subject' => $this]
        );

        try {
            foreach ($records['default'] as $record) {
                $this->validator->checkRequiredFields($record);

                $objectKey = (int) $record['categoryId'];
                $objectLanguage = (int) $record['languageId'];
                unset($record['categoryId'], $record['languageId']);

                $this->checkIfShopExist($objectLanguage);
                $this->checkIfCategoryExist($objectKey);

                $this->translationComponent->write(
                    $objectLanguage,
                    'category',
                    $objectKey,
                    $record
                );
            }
        } catch (\Exception $exception) {
            if (!$this->importExportErrorMode) {
                throw new \Exception($exception->getMessage());
            }

            $this->logMessages[] = $exception->getMessage();
            $this->logState = 'true';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLogMessages(): array
    {
        return $this->logMessages;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogState(): ?string
    {
        return $this->logState;
    }

    /**
     * @param array<int> $ids
     *
     * @return array<array<string, mixed>>
     */
    private function getTranslations(array $ids): array
    {
        return $this->connection->createQueryBuilder()
            ->select([
                'category.id AS categoryId',
                'translation.id',
                'translation.objecttype',
                'translation.objectdata',
                'translation.objectkey',
                'translation.objectlanguage AS languageId',
                'translation.dirty',
            ])
            ->from('s_core_translations', 'translation')
            ->leftJoin('translation', 's_categories', 'category', 'translation.objectkey = category.id')
            ->where('translation.id IN (:translationIds)')
            ->andWhere('translation.objecttype = "category"')
            ->orderBy('translation.objectlanguage', 'ASC')
            ->setParameter('translationIds', $ids, Connection::PARAM_INT_ARRAY)
            ->execute()
            ->fetchAllAssociative();
    }

    /**
     * @throws AdapterException
     */
    private function checkIfShopExist(int $objectLanguage): void
    {
        $shopExists = $this->connection->createQueryBuilder()
            ->select('id')
            ->from('s_core_shops')
            ->where('id = :id')
            ->setParameter('id', $objectLanguage)
            ->execute()
            ->fetchOne();

        if (!$shopExists) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/translations/lang_id_not_found', 'Language with ID %s does not exist');

            throw new AdapterException(\sprintf($message, $objectLanguage));
        }
    }

    /**
     * @throws AdapterException
     */
    private function checkIfCategoryExist(int $objectKey): void
    {
        $categoryExists = $this->connection->createQueryBuilder()
            ->select('id')
            ->from('s_categories')
            ->where('id = :id')
            ->setParameter('id', $objectKey)
            ->execute()
            ->fetchOne();

        if (!$categoryExists) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/category_id_not_found', 'Category with ID: %s does not exist');

            throw new AdapterException(\sprintf($message, $objectKey));
        }
    }
}
