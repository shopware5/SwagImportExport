<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DataManagers;

use Enlight_Components_Db_Adapter_Pdo_Mysql as PDOConnection;

class ArticleImageDataManager implements \Enlight_Hook
{
    private PDOConnection $db;

    /**
     * Define which field should be set by default
     */
    private array $defaultFields = [
        'main',
        'position',
        'thumbnail',
        'description',
    ];

    /**
     * initialises the class properties
     */
    public function __construct(PDOConnection $pdo)
    {
        $this->db = $pdo;
    }

    /**
     * Sets fields which are empty by default.
     *
     * @param array<string, mixed> $record
     */
    public function setDefaultFields(array $record, int $articleId)
    {
        foreach ($this->defaultFields as $key) {
            if (isset($record[$key])) {
                continue;
            }

            switch ($key) {
                case 'main':
                    $record[$key] = 1;
                    break;
                case 'position':
                    $record[$key] = $this->getPosition($articleId);
                    break;
                case 'thumbnail':
                    $record[$key] = true;
                    break;
                case 'description':
                    $record[$key] = '';
                    break;
            }
        }

        return $record;
    }

    /**
     * @return int
     */
    private function getPosition(int $articleId)
    {
        $sql = 'SELECT MAX(position) FROM s_articles_img WHERE articleID = ?;';
        $result = $this->db->fetchOne($sql, $articleId);

        return isset($result) ? ((int) $result + 1) : 1;
    }
}
