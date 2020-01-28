<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Helper\DataProvider;

use Doctrine\DBAL\Connection;
use Shopware\Components\SwagImportExport\Utils\TreeHelper;

class ProfileDataProvider
{
    const ARTICLE_PROFILE_TYPE = 'articles';
    const ARTICLE_PROFILE_NAME = 'article_profile';
    const ARTICLE_TABLE = 's_articles';

    const VARIANT_PROFILE_TYPE = 'articles';
    const VARIANT_PROFILE_NAME = 'variant_profile';
    const VARIANT_TABLE = 's_articles_details';

    const CUSTOMER_PROFILE_TYPE = 'customers';
    const CUSTOMER_PROFILE_NAME = 'customer_profile';
    const CUSTOMER_TABLE = 's_user';

    const CATEGORY_PROFILE_TYPE = 'categories';
    const CATEGORY_PROFILE_NAME = 'category_profile';
    const CATEGORY_TABLE = 's_categories';

    const ARTICLES_INSTOCK_PROFILE_TYPE = 'articlesInStock';
    const ARTICLES_INSTOCK_PROFILE_NAME = 'article_instock_profile';

    const ARTICLES_PRICES_PROFILE_TYPE = 'articlesPrices';
    const ARTICLES_PRICES_PROFILE_NAME = 'articles_price_profile';

    const ARTICLES_IMAGES_PROFILE_TYPE = 'articlesImages';
    const ARTICLES_IMAGE_PROFILE_NAME = 'articles_image_profile';

    const ARTICLES_TRANSLATIONS_PROFILE_TYPE = 'articlesTranslations';
    const ARTICLES_TRANSLATIONS_PROFILE_NAME = 'articles_translations_profile';

    const ORDERS_PROFILE_TYPE = 'orders';
    const ORDERS_PROFILE_NAME = 'order_profile';
    const ORDER_TABLE = 's_order';

    const MAIN_ORDERS_PROFILE_TYPE = 'mainOrders';
    const MAIN_ORDERS_PROFILE_NAME = 'main_order_profile';
    const IMPORT_MAIN_ORDER_PROFILE_NAME = 'import_main_order_profile';

    const TRANSLATIONS_PROFILE_TYPE = 'translations';
    const TRANSLATIONS_PROFILE_NAME = 'translation_profile';

    const NEWSLETTER_PROFILE_TYPE = 'newsletter';
    const NEWSLETTER_PROFILE_NAME = 'newsletter_profile';
    const NEWSLETTER_TABLE = 's_campaigns_mailaddresses';

    /**
     * @var array - Indexed by profile type
     */
    private $profileIds = [];

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function createProfiles()
    {
        $this->createProfile(self::ARTICLE_PROFILE_TYPE, self::ARTICLE_PROFILE_NAME);
        $this->createProfile(self::VARIANT_PROFILE_TYPE, self::VARIANT_PROFILE_NAME);
        $this->createProfile(self::CUSTOMER_PROFILE_TYPE, self::CUSTOMER_PROFILE_NAME);
        $this->createProfile(self::CATEGORY_PROFILE_TYPE, self::CATEGORY_PROFILE_NAME);
        $this->createProfile(self::ARTICLES_INSTOCK_PROFILE_TYPE, self::ARTICLES_INSTOCK_PROFILE_NAME);
        $this->createProfile(self::ARTICLES_PRICES_PROFILE_TYPE, self::ARTICLES_PRICES_PROFILE_NAME);
        $this->createProfile(self::ARTICLES_IMAGES_PROFILE_TYPE, self::ARTICLES_IMAGE_PROFILE_NAME);
        $this->createProfile(self::ARTICLES_TRANSLATIONS_PROFILE_TYPE, self::ARTICLES_TRANSLATIONS_PROFILE_NAME);
        $this->createProfile(self::ORDERS_PROFILE_TYPE, self::ORDERS_PROFILE_NAME);
        $this->createProfile(self::MAIN_ORDERS_PROFILE_TYPE, self::MAIN_ORDERS_PROFILE_NAME);
        $this->createProfile(self::TRANSLATIONS_PROFILE_TYPE, self::TRANSLATIONS_PROFILE_NAME);
        $this->createProfile(self::NEWSLETTER_PROFILE_TYPE, self::NEWSLETTER_PROFILE_NAME);
    }

    /**
     * @param string $type
     *
     * @return int
     */
    public function getIdByProfileType($type)
    {
        if (!array_key_exists($type, $this->profileIds)) {
            throw new \RuntimeException("Profile type {$type} not found.");
        }

        return $this->profileIds[$type];
    }

    public function getProfileId(string $profile): int
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        return (int) $queryBuilder->select('id')
            ->from('s_import_export_profile')
            ->where('type = :profileType')
            ->orWhere('name = :profileName')
            ->setParameter('profileType', $profile)
            ->setParameter('profileName', $profile)
            ->execute()
            ->fetch(\PDO::FETCH_COLUMN);
    }

    private function createProfile(string $profileType, string $profileName): void
    {
        if ($this->isProfileInstalled($profileType)) {
            return;
        }

        $defaultTree = TreeHelper::getDefaultTreeByProfileType($profileType);

        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->insert('s_import_export_profile')
            ->setValue('type', ':type')
            ->setValue('base_profile', 'NULL')
            ->setValue('name', ':name')
            ->setValue('description', ':description')
            ->setValue('tree', ':tree')
            ->setValue('hidden', 0)
            ->setValue('is_default', 1)
            ->setParameter('type', $profileType)
            ->setParameter('name', $profileName)
            ->setParameter('description', $profileName . '_description_unit_test')
            ->setParameter('tree', $defaultTree);

        $queryBuilder->execute();

        $this->profileIds[$profileType] = $queryBuilder->getConnection()->lastInsertId();
    }

    private function isProfileInstalled(string $profileType): bool
    {
        return (bool) $this->getProfileId($profileType);
    }
}
