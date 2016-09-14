<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Helper;

use Shopware\Components\Console\Application;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\SwagImportExport\Utils\TreeHelper;
use Shopware\CustomModels\ImportExport\Profile;
use Shopware\Models\Newsletter\Address;
use Shopware\Models\Newsletter\Group;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;

class CommandTestHelper
{
    /**
     * @var array
     */
    private $createdFiles = [];

    const IMPORT_FILES_DIR = __DIR__ . '/ImportFiles/';

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

    const MAIN_ORDERS_PROFILE_TYPE = 'mainOrders';
    const MAIN_ORDERS_PROFILE_NAME = 'main_order_profile';
    const IMPORT_MAIN_ORDER_PROFILE_NAME = 'order_profile';

    const TRANSLATIONS_PROFILE_TYPE = 'translations';
    const TRANSLATIONS_PROFILE_NAME = 'translation_profile';

    const NEWSLETTER_PROFILE_TYPE = 'newsletter';
    const NEWSLETTER_PROFILE_NAME = 'newsletter_profile';
    const NEWSLETTER_TABLE = 's_campaigns_mailaddresses';

    /**
     * @var ModelManager
     */
    private $modelManager;

    public function __construct(ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;
    }

    public function setUp()
    {
        $this->modelManager->beginTransaction();
        $this->createProfiles();
    }

    public function tearDown()
    {
        foreach ($this->createdFiles as $path) {
            unlink($path);
        }

        $this->modelManager->rollback();
    }

    /**
     * @param string $command
     * @return string|StreamOutput
     */
    public function runCommand($command)
    {
        $application = new Application(Shopware()->Container()->get('kernel'));
        $application->setAutoExit(true);

        $fp = tmpfile();
        $input = new StringInput($command);
        $output = new StreamOutput($fp);

        $application->doRun($input, $output);

        return $this->readConsoleOutput($fp);
    }

    /**
     * @param string $fileName
     * @return string
     */
    public function getFilePath($fileName)
    {
        return Shopware()->DocPath() . $fileName;
    }

    /**
     * @param string $fileName
     */
    public function addFile($fileName)
    {
        $this->createdFiles[] = $this->getFilePath($fileName);
    }

    public function createNewsletterDemoData()
    {
        $newsletterGroup = $this->modelManager->find(Group::class, 1);

        for ($addressAmount = 0; $addressAmount < 25; $addressAmount++) {
            $address = new Address();
            $address->setEmail(uniqid('test_') . '@example.com');
            $address->setAdded(new \DateTime());
            $address->setNewsletterGroup($newsletterGroup);
            $address->setIsCustomer(false);

            $this->modelManager->persist($address);
        }

        $this->modelManager->flush();
    }

    private function createProfiles()
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

        $this->modelManager->flush();
    }

    /**
     * @param string $profileType
     * @param string $profileName
     * @return Profile
     */
    private function createProfile($profileType, $profileName)
    {
        $defaultTree = TreeHelper::getDefaultTreeByProfileType($profileType);

        $profile = new Profile();
        $profile->setHidden(0);
        $profile->setName($profileName);
        $profile->setType($profileType);
        $profile->setTree($defaultTree);
        $this->modelManager->persist($profile);

        return $profile;
    }

    /**
     * @param $fp
     * @return string
     */
    private function readConsoleOutput($fp)
    {
        fseek($fp, 0);
        $output = '';
        while (!feof($fp)) {
            $output = fread($fp, 4096);
        }
        fclose($fp);

        return $output;
    }
}
