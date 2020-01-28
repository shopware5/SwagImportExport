<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\DbAdapters;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\AbstractQuery;
use Shopware\Components\SwagImportExport\Utils\DbAdapterHelper;
use Shopware\Components\SwagImportExport\Utils\SwagVersionHelper;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Order\Order;

class CustomerCompleteDbAdapter extends CustomerDbAdapter
{
    /**
     * @return array
     */
    public function getDefaultColumns()
    {
        return $this->getCustomerColumns();
    }

    /**
     * @return array
     */
    public function getCustomerColumns()
    {
        return [
            'customer',
            'attribute',
        ];
    }

    /**
     * @param array $columns
     * @param array $ids
     *
     * @return \Shopware\Components\Model\QueryBuilder
     */
    public function getBuilder($columns, $ids)
    {
        $builder = $this->manager->createQueryBuilder();
        $builder->select($columns)
            ->from(Customer::class, 'customer')
            ->leftJoin('customer.attribute', 'attribute')
            ->groupBy('customer.id')
            ->where('customer.id IN (:ids)')
            ->setParameter('ids', $ids);

        return $builder;
    }

    /**
     * @param int   $start
     * @param int   $limit
     * @param array $filter
     *
     * @return array
     */
    public function readRecordIds($start, $limit, $filter)
    {
        $query = $this->manager->getConnection()->createQueryBuilder();
        $query->select(['customer.id']);
        $query->from('s_user', 'customer');

        if ($start) {
            $query->setFirstResult($start);
        }

        if ($limit) {
            $query->setMaxResults($limit);
        }

        if (SwagVersionHelper::hasMinimumVersion('5.4.0') && array_key_exists('customerId', $filter)) {
            $query->andWhere('customer.id = :customerId');
            $query->setParameter('customerId', $filter['customerId']);
        }

        $ids = $query->execute()->fetchAll(\PDO::FETCH_COLUMN);

        return array_map(function ($id) {
            return (int) $id;
        }, $ids);
    }

    /**
     * @param array $ids
     * @param array $columns
     *
     * @return array
     */
    public function read($ids, $columns)
    {
        foreach ($columns as $key => $value) {
            if ($value === 'unhashedPassword') {
                unset($columns[$key]);
            }
        }

        $builder = $this->getBuilder($columns, $ids);
        $query = $builder->getQuery();
        $query->setHydrationMode(AbstractQuery::HYDRATE_ARRAY);

        $paginator = $this->manager->createPaginator($query);
        $customers = $paginator->getIterator()->getArrayCopy();

        $customerIds = array_column($customers, 'id');
        $addresses = $this->getAddresses($customerIds);
        $orders = $this->getCustomerOrders($customerIds);

        $newsletterRecipients = $this->getNewsletterRecipients($customerIds);

        foreach ($customers as &$customer) {
            $customer['newsletter'] = false;
            if ($newsletterRecipients[$customer['id']]) {
                $customer['newsletter'] = true;
            }
            if ($addresses[$customer['id']]) {
                $customer['addresses'] = DbAdapterHelper::decodeHtmlEntities($addresses[$customer['id']]);
            }
            if ($orders[$customer['id']]) {
                $customer['orders'] = DbAdapterHelper::decodeHtmlEntities($orders[$customer['id']]);
            }
            if (array_key_exists('attribute', $customer)) {
                unset($customer['attribute']['id'], $customer['attribute']['customerId']);
            }
        }
        unset($customer);

        $result['customers'] = DbAdapterHelper::decodeHtmlEntities($customers);

        return $result;
    }

    /**
     * @return array
     */
    private function getAddresses(array $ids)
    {
        $dbalQueryBuilder = $this->manager->getConnection()->createQueryBuilder();

        return $dbalQueryBuilder->select('address.user_id, address.*', 'attribute.*')
            ->from('s_user_addresses', 'address')
            ->leftJoin('address', 's_user_addresses_attributes', 'attribute', 'address.id = attribute.address_id')
            ->where('address.user_id IN (:ids)')
            ->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY)
            ->execute()
            ->fetchAll(\PDO::FETCH_GROUP);
    }

    /**
     * @param array $ids
     *
     * @return array
     */
    private function getCustomerOrders($ids)
    {
        $builder = $this->manager->createQueryBuilder();

        $orders = $builder->from(Order::class, 'o')
            ->select([
                'o',
                'attr',
                'partial payment.{id, name, description}',
                'paymentStatus',
                'orderStatus',
                'details',
                'detailAttr',
                'billingAddress',
                'shippingAddress',
            ])
            ->leftJoin('o.details', 'details')
            ->leftJoin('details.attribute', 'detailAttr')
            ->leftJoin('o.billing', 'billingAddress')
            ->leftJoin('o.shipping', 'shippingAddress')
            ->leftJoin('o.payment', 'payment')
            ->leftJoin('o.paymentStatus', 'paymentStatus')
            ->leftJoin('o.orderStatus', 'orderStatus')
            ->leftJoin('o.attribute', 'attr')
            ->where('o.customerId IN (:ids)')
            ->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY)
            ->getQuery()
            ->getArrayResult();

        $indexedOrders = [];
        foreach ($orders as $order) {
            /** @var \DateTime $orderTime */
            $orderTime = $order['orderTime'];
            $order['orderTime'] = $orderTime->format('Y-m-d H:i:s');
            foreach ($order['details'] as &$detail) {
                /** @var \DateTime $releaseDate */
                $releaseDate = $detail['releaseDate'];

                if (!$releaseDate instanceof \DateTime) {
                    $releaseDate = new \DateTime('1970-01-01');
                }

                $detail['releaseDate'] = $releaseDate->format('Y-m-d H:i:s');
            }
            unset($detail);
            if (!array_key_exists($order['customerId'], $indexedOrders)) {
                $indexedOrders[$order['customerId']] = [];
            }
            $indexedOrders[$order['customerId']][] = $order;
        }

        return $indexedOrders;
    }

    /**
     * @return array
     */
    private function getNewsletterRecipients($ids)
    {
        $dbalQueryBuilder = $this->manager->getConnection()->createQueryBuilder();

        return $dbalQueryBuilder->select('customer.id, mailaddress.email')
            ->from('s_campaigns_mailaddresses', 'mailaddress')
            ->innerJoin('mailaddress', 's_user', 'customer', 'customer.email = mailaddress.email')
            ->where('customer.id IN (:ids)')
            ->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY)
            ->execute()
            ->fetchAll(\PDO::FETCH_GROUP | \PDO::FETCH_UNIQUE);
    }
}
