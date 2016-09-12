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

    const ARTICLE_PROFILE = 'articles';
    const ARTICLE_PROFILE_NAME = 'article_profile';

    const CUSTOMER_PROFILE = 'customers';
    const CUSTOMER_PROFILE_NAME = 'customer_profile';

    const CATEGORY_PROFILE = 'categories';
    const CATEGORY_PROFILE_NAME = 'category_profile';

    const ARTICLES_INSTOCK_PROFILE = 'articlesInStock';
    const ARTICLES_INSTOCK_PROFILE_NAME = 'article_instock_profile';

    const ARTICLES_PRICES_PROFILE = 'articlesPrices';
    const ARTICLES_PRICES_PROFILE_NAME = 'articles_price_profile';

    const ARTICLES_IMAGES_PROFILE = 'articlesImages';
    const ARTICLES_IMAGE_PROFILE_NAME = 'articles_image_profile';

    const ARTICLES_TRANSLATIONS_PROFILE = 'articlesTranslations';
    const ARTICLES_TRANSLATIONS_PROFILE_NAME = 'articles_translations_name';

    const ORDERS_PROFILE = 'orders';
    const ORDERS_PROFILE_NAME = 'order_profile';

    const MAIN_ORDERS_PROFILE = 'mainOrders';
    const MAIN_ORDERS_PROFILE_NAME = 'main_order_profile';

    const TRANSLATIONS_PROFILE = 'translations';
    const TRANSLATIONS_PROFILE_NAME = 'translation_profile';

    const NEWSLETTER_PROFILE = 'newsletter';
    const NEWSLETTER_PROFILE_NAME = 'newsletter_profile';

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
        $this->createProfile(self::ARTICLE_PROFILE, self::ARTICLE_PROFILE_NAME);
        $this->createProfile(self::CUSTOMER_PROFILE, self::CUSTOMER_PROFILE_NAME);
        $this->createProfile(self::CATEGORY_PROFILE, self::CATEGORY_PROFILE_NAME);
        $this->createProfile(self::ARTICLES_INSTOCK_PROFILE, self::ARTICLES_INSTOCK_PROFILE_NAME);
        $this->createProfile(self::ARTICLES_PRICES_PROFILE, self::ARTICLES_PRICES_PROFILE_NAME);
        $this->createProfile(self::ARTICLES_IMAGES_PROFILE, self::ARTICLES_IMAGE_PROFILE_NAME);
        $this->createProfile(self::ARTICLES_TRANSLATIONS_PROFILE, self::ARTICLES_TRANSLATIONS_PROFILE_NAME);
        $this->createProfile(self::ORDERS_PROFILE, self::ORDERS_PROFILE_NAME);
        $this->createProfile(self::MAIN_ORDERS_PROFILE, self::MAIN_ORDERS_PROFILE_NAME);
        $this->createProfile(self::TRANSLATIONS_PROFILE, self::TRANSLATIONS_PROFILE_NAME);
        $this->createProfile(self::NEWSLETTER_PROFILE, self::NEWSLETTER_PROFILE_NAME);

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
