<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\SwagImportExport\Validators;

use PHPUnit\Framework\TestCase;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Validators\NewsletterValidator;

class NewsletterValidatorTest extends TestCase
{
    public function test_checkRequiredFields_throws_exception_with_empty_record_array()
    {
        $newsletterValidator = $this->createNewsletterValidator();

        $this->expectException(AdapterException::class);
        $this->expectExceptionMessage('Mail-Adresse zwingend erforderlich');
        $newsletterValidator->checkRequiredFields([]);
    }

    public function test_checkRequiredFields_throws_exception_with_empty_email_address()
    {
        $newsletterValidator = $this->createNewsletterValidator();
        $record = [
            'email' => '',
        ];

        $this->expectException(AdapterException::class);
        $this->expectExceptionMessage('Mail-Adresse zwingend erforderlich');
        $newsletterValidator->checkRequiredFields($record);
    }

    /**
     * @return NewsletterValidator
     */
    private function createNewsletterValidator()
    {
        return new NewsletterValidator();
    }
}
