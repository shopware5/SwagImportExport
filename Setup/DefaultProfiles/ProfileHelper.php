<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Setup\DefaultProfiles;

class ProfileHelper
{
    /**
     * @return ProfileMetaData[]
     */
    public static function getProfileInstances(): array
    {
        return [
            new MinimalCategoryProfile(),
            new CategoryProfile(),
            new CategoryTranslationProfile(),
            new ProductCompleteProfile(),
            new ProductProfile(),
            new NewsletterRecipientProfile(),
            new MinimalProductProfile(),
            new ProductPriceProfile(),
            new ProductImageUrlProfile(),
            new MinimalProductVariantsProfile(),
            new ProductTranslationProfile(),
            new ProductTranslationUpdateProfile(),
            new ProductCategoriesProfile(),
            new ProductSimilarsProfile(),
            new ProductAccessoryProfile(),
            new ProductInStockProfile(),
            new MinimalOrdersProfile(),
            new OrderMainDataProfile(),
            new ProductPropertiesProfile(),
            new CustomerProfile(),
            new CustomerCompleteProfile(),
            new ProductImagesProfile(),
            new TranslationProfile(),
            new AddressProfile(),
            new OrderProfile(),
        ];
    }
}
