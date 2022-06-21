<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\Validators;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\Exception\AdapterException;
use SwagImportExport\Components\Validators\NewsletterValidator;

class NewsletterValidatorTest extends TestCase
{
    public function testCheckRequiredFieldsThrowsExceptionWithEmptyRecordArray(): void
    {
        $newsletterValidator = $this->createNewsletterValidator();

        $this->expectException(AdapterException::class);
        $this->expectExceptionMessage('Mail-Adresse zwingend erforderlich');
        $newsletterValidator->checkRequiredFields([]);
    }

    public function testCheckRequiredFieldsThrowsExceptionWithEmptyEmailAddress(): void
    {
        $newsletterValidator = $this->createNewsletterValidator();
        $record = [
            'email' => '',
        ];

        $this->expectException(AdapterException::class);
        $this->expectExceptionMessage('Mail-Adresse zwingend erforderlich');
        $newsletterValidator->checkRequiredFields($record);
    }

    private function createNewsletterValidator(): NewsletterValidator
    {
        return new NewsletterValidator();
    }
}
