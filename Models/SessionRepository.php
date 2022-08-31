<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Models;

use Shopware\Components\Model\ModelRepository;
use Shopware\Components\Model\QueryBuilder;

/**
 * @extends ModelRepository<Session>
 */
class SessionRepository extends ModelRepository
{
    /**
     * Returns a query builder object to get all sessions.
     *
     * @param array<string, string>|array<array{property: string, value: mixed, expression?: string}> $filterBy
     * @param array<array{property: string, direction: string}>                                       $orderBy
     */
    public function getSessionsListQuery(array $filterBy = [], array $orderBy = [], ?int $limit = null, ?int $offset = null): QueryBuilder
    {
        $builder = $this->createQueryBuilder('s');

        $builder->select(
            [
                's.id as id',
                'p.id as profileId',
                'p.name as profileName',
                's.type as type',
                's.position as position',
                's.totalCount as totalCount',
                's.userName as username',
                's.fileName as fileName',
                's.format as format',
                's.fileSize as fileSize',
                's.state as state',
                's.createdAt as createdAt',
            ]
        );

        $builder->join('s.profile', 'p');

        if (!empty($filterBy)) {
            $builder->addFilter($filterBy);
        }

        if (!empty($orderBy)) {
            $builder->addOrderBy($orderBy);
        }

        if ($offset !== null && $limit !== null) {
            $builder->setFirstResult($offset)
                ->setMaxResults($limit);
        }

        return $builder;
    }
}
