<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\CustomModels\ImportExport;

use Shopware\Components\Model\ModelRepository;
use Shopware\Components\Model\QueryBuilder;

/**
 * @extends ModelRepository<Profile>
 */
class ProfileRepository extends ModelRepository
{
    /**
     * Returns a query builder object to get all profiles.
     *
     * @param array<string, string>|array<array{property: string, value: mixed, expression?: string}> $filterBy
     * @param array<array{property: string, direction: string}>                                       $orderBy
     * @param int|null                                                                                $limit
     * @param int|null                                                                                $offset
     *
     * @return QueryBuilder
     */
    public function getProfilesListQuery(array $filterBy = [], array $orderBy = [], $limit = null, $offset = null)
    {
        /** @var QueryBuilder $builder */
        $builder = $this->createQueryBuilder('p');
        $builder->select(
            [
                'p.id as id',
                'p.type as type',
                'p.name as name',
                'p.description as description',
                'p.tree as tree',
                'p.default as default',
                'p.baseProfile as baseProfile',
            ]
        );

        $builder->addFilter(['hidden' => '0']);

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
