<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;

class ArticlesPricesDbAdapter implements DataDbAdapter
{

    /**
     * Shopware\Components\Model\ModelManager
     */
    protected $manager;
    protected $detailRepository;
    protected $groupRepository;

    public function readRecordIds($start, $limit, $filter)
    {
        $manager = $this->getManager();

        $builder = $manager->createQueryBuilder();

        $builder->select('price.id')
                ->from('Shopware\Models\Article\Article', 'article')
                ->leftJoin('article.details', 'detail')
                ->leftJoin('detail.prices', 'price')
                ->orderBy('price.id', 'ASC');

        if (!empty($filter)) {
            $builder->addFilter($filter);
        }

        $builder->setFirstResult($start)
                ->setMaxResults($limit);

        $records = $builder->getQuery()->getResult();

        $result = array();
        if ($records) {
            foreach ($records as $value) {
                $result[] = $value['id'];
            }
        }

        return $result;
    }

    public function read($ids, $columns)
    {
        if (!$ids && empty($ids)) {
            throw new \Exception('Can not read articles without ids.');
        }

        if (!$columns && empty($columns)) {
            throw new \Exception('Can not read articles without column names.');
        }

        $manager = $this->getManager();

        $builder = $manager->createQueryBuilder();
        $builder->select($columns)
                ->from('Shopware\Models\Article\Article', 'article')
                ->leftJoin('article.details', 'detail')
                ->leftJoin('detail.prices', 'price')
                ->where('price.id IN (:ids)')
                ->setParameter('ids', $ids);

        $result = $builder->getQuery()->getResult();

        return $result;
    }

    public function getDefaultColumns()
    {
        return array(
            'prices.id',
            'prices.articleId',
            'prices.articleDetailsId',
            'prices.customerGroupKey',
            'prices.from',
            'prices.to',
            'prices.price',
            'prices.pseudoPrice',
            'prices.basePrice',
            'prices.percent',
        );
    }

    public function write($records)
    {
        $manager = $this->getManager();
        foreach ($records as $record) {

            if (empty($record['ordernumber'])) {
                continue;
            }

            if (empty($record['pricegroup'])) {
                $record['pricegroup'] = 'EK';
            }
            
            $customerGroup = $this->getGroupRepository()->findOneBy(array("key" => $record['pricegroup']));
            if (!$customerGroup) {
                continue;
            }
            
            $articleDetail = $this->getDetailRepository()->findOneBy(array("number" => $record['ordernumber']));
            if (!$articleDetail) {
                continue;
            }

            if (isset($record['baseprice'])) {
                $record['baseprice'] = floatval(str_replace(",", ".", $record['baseprice']));
            } else {
                $record['baseprice'] = 0;
            }

            if (isset($record['percent'])) {
                $record['percent'] = floatval(str_replace(",", ".", $record['percent']));
            } else {
                $record['percent'] = 0;
            }

            if (empty($record['from'])) {
                $record['from'] = 1;
            } else {
                $record['from'] = intval($record['from']);
            }

            if (empty($record['price']) && empty($record['percent'])) {
                continue;
            }

            if ($record['from'] <= 1 && empty($record['price'])) {
                continue;
            }

            $query = $manager->createQuery('DELETE FROM Shopware\Models\Article\Price price WHERE price.customerGroup = :customerGroup AND price.articleDetailsId = :detailId AND price.from >= :from');
            $query->setParameters(array(
                'customerGroup' => $record['pricegroup'],
                'detailId' => $articleDetail->getId(),
                'from' => $record['from'],
            ));
            $query->execute();

            if ($record['from'] != 1) {
                $query = $manager->createQuery('UPDATE Shopware\Models\Article\Price price SET price.to = :to WHERE price.customerGroup = :customerGroup AND price.articleDetailsId = :detailId AND price.articleId = :articleId AND price.to LIKE \'beliebig\'');
                $query->setParameters(array(
                    'to' => $record['from'] - 1,
                    'customerGroup' => $record['pricegroup'],
                    'detailId' => $articleDetail->getId(),
                    'articleId' => $articleDetail->getArticle()->getId(),
                ));
                $query->execute();
            }
            
            $price = new \Shopware\Models\Article\Price();
            $price->setArticle($articleDetail->getArticle());
            $price->setDetail($articleDetail);
            $price->setCustomerGroup($customerGroup);
            $price->setFrom($record['from']);
            $price->setTo('beliebig');
            $price->setPrice($record['price']);
            $price->setPseudoPrice($record['pseudoprice']);
            $price->setBasePrice($record['baseprice']);
            $price->setPercent($record['percent']);


            $this->getManager()->persist($price);
            $this->getManager()->flush();
        }
    }

    /**
     * Returns article detail repository
     * 
     * @return Shopware\Models\Article\Detail
     */
    public function getDetailRepository()
    {
        if ($this->detailRepository === null) {
            $this->detailRepository = $this->getManager()->getRepository('Shopware\Models\Article\Detail');
        }

        return $this->detailRepository;
    }

    /**
     * Returns article detail repository
     * 
     * @return Shopware\Models\Customer\Group
     */
    public function getGroupRepository()
    {
        if ($this->groupRepository === null) {
            $this->groupRepository = $this->getManager()->getRepository('Shopware\Models\Customer\Group');
        }

        return $this->groupRepository;
    }

    /*
     * @return Shopware\Components\Model\ModelManager
     */

    public function getManager()
    {
        if ($this->manager === null) {
            $this->manager = Shopware()->Models();
        }

        return $this->manager;
    }

}
