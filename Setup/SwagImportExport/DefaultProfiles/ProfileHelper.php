<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Setup\SwagImportExport\DefaultProfiles;

/**
 * Class ProfileHelper
 * @package Shopware\Components\SwagImportExport\Profile
 */
class ProfileHelper
{
    /**
     * @return ProfileMetaData[]
     */
    public static function getProfileInstances()
    {
        return [
            new MinimalCategoryProfile(),
            new CategoryProfile(),
            new ArticleCompleteProfile(),
            new ArticleProfile(),
            new NewsletterRecipientProfile(),
            new MinimalArticleProfile(),
            new ArticlePriceProfile(),
            new ArticleImageUrlProfile(),
            new MinimalArticleVariantsProfile(),
            new ArticleTranslationProfile(),
            new ArticleTranslationUpdateProfile(),
            new ArticleCategoriesProfile(),
            new ArticleSimilarsProfile(),
            new ArticleAccessoryProfile(),
            new ArticleInStockProfile(),
            new MinimalOrdersProfile(),
            new OrderMainDataProfile(),
            new ArticlePropertiesProfile(),
            new CustomerProfile(),
            new ArticleImagesProfile(),
            new TranslationProfile(),
            new AddressProfile()
        ];
    }
}
