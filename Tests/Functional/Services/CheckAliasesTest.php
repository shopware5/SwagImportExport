<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Services;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\DbAdapters\DataDbAdapter;
use SwagImportExport\Components\Providers\DataProvider;
use SwagImportExport\Tests\Helper\ContainerTrait;
use Symfony\Component\HttpFoundation\RequestStack;

class CheckAliasesTest extends TestCase
{
    use ContainerTrait;
    public const WHITELIST = [
        'customer',
        'attribute',
        'unhashedPassword',
        'price.price',
    ];

    public function testAliases(): void
    {
        /** @var RequestStack $requestStack */
        $requestStack = $this->getContainer()->get('request_stack');
        $requestStack->push(new \Enlight_Controller_Request_RequestTestCase());

        foreach ($this->getDbAdapters() as $adapter) {
            foreach ($adapter->getSections() as $section) {
                $sectionName = $section['id'];

                foreach ($adapter->getColumns($sectionName) as $column) {
                    static::assertIsString($column);
                    if (\in_array($column, self::WHITELIST)) {
                        continue;
                    }
                    static::assertStringContainsString(' as ', $column, sprintf('The column %s from adapter %s does not contain  " as " called from the section %s', $column, \get_class($adapter), $sectionName));
                }
            }
        }
    }

    /**
     * @return iterable<DataDbAdapter>
     */
    private function getDbAdapters(): iterable
    {
        $container = $this->getContainer()->get(DataProvider::class);

        return $container->getAdapters();
    }
}
