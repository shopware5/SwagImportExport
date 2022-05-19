<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\SwagImportExport\Validators;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\Exception\AdapterException;
use SwagImportExport\Components\Validators\NewsletterValidator;

class NewsletterValidatorTest extends TestCase
{
    public function testCheckRequiredFieldsThrowsExceptionWithEmptyRecordArray()
    {
        $newsletterValidator = $this->createNewsletterValidator();

        $this->expectException(AdapterException::class);
        $this->expectExceptionMessage('Mail-Adresse zwingend erforderlich');
        $newsletterValidator->checkRequiredFields([]);
    }

    public function testCheckRequiredFieldsThrowsExceptionWithEmptyEmailAddress()
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
