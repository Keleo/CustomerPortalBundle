<?php

/*
 * This file is part of the "Shared Project Timesheets Bundle" for Kimai.
 * All rights reserved by Fabian Vetter (https://vettersolutions.de).
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\SharedProjectTimesheetsBundle\Repository;

use App\Entity\Customer;
use App\Entity\Project;
use App\Repository\Paginator\QueryPaginator;
use App\Repository\Query\BaseQuery;
use App\Repository\Query\ProjectQuery;
use App\Utils\Pagination;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use KimaiPlugin\SharedProjectTimesheetsBundle\Entity\SharedProjectTimesheet;

/**
 * @extends EntityRepository<SharedProjectTimesheet>
 */
class SharedProjectTimesheetRepository extends EntityRepository
{
    public function findAllSharedProjects(BaseQuery $baseQuery): Pagination
    {
        $query = $this->createQueryBuilder('spt')
            ->leftJoin(Project::class, 'p', Join::WITH, 'spt.project = p')
            ->leftJoin(Customer::class, 'c', Join::WITH, 'spt.customer = c')
            ->orderBy('p.name, c.name, spt.shareKey', 'ASC')
            ->getQuery()
        ;

        $loader = new QueryPaginator($query, $this->count([]));

        return new Pagination($loader, $baseQuery);
    }

    public function save(SharedProjectTimesheet $sharedProject): void
    {
        $em = $this->getEntityManager();
        $em->persist($sharedProject);
        $em->flush();
    }

    public function remove(SharedProjectTimesheet $sharedProject): void
    {
        $em = $this->getEntityManager();
        $em->remove($sharedProject);
        $em->flush();
    }

    public function findBySharedProjectTimesheet(SharedProjectTimesheet $sharedProjectTimesheet): ?SharedProjectTimesheet
    {
        if ($sharedProjectTimesheet->isCustomerSharing()) {
            return $this->findByCustomerAndShareKey($sharedProjectTimesheet->getCustomer(), $sharedProjectTimesheet->getShareKey());
        } else {
            return $this->findByProjectAndShareKey($sharedProjectTimesheet->getProject(), $sharedProjectTimesheet->getShareKey());
        }
    }

    public function findByProjectAndShareKey(Project|int|null $project, ?string $shareKey): ?SharedProjectTimesheet
    {
        return $this->createQueryBuilder('spt')
            ->where('spt.project = :project')
            ->andWhere('spt.customer is null')
            ->andWhere('spt.shareKey = :shareKey')
            ->setMaxResults(1)
            ->setParameter('project', $project)
            ->setParameter('shareKey', $shareKey)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByCustomerAndShareKey(Customer|int $customer, ?string $shareKey): ?SharedProjectTimesheet
    {
        return $this->createQueryBuilder('spt')
            ->where('spt.project is null')
            ->andWhere('spt.customer = :customer')
            ->andWhere('spt.shareKey = :shareKey')
            ->setMaxResults(1)
            ->setParameter('customer', $customer)
            ->setParameter('shareKey', $shareKey)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Project[]
     */
    public function getProjects(SharedProjectTimesheet $sharedProject): array
    {
        if ($sharedProject->isProjectSharing()) {
            return [$sharedProject->getProject()];
        }

        /** @var \App\Repository\ProjectRepository $projectRepository */
        $projectRepository = $this->_em->getRepository(Project::class);

        return $projectRepository->getProjectsForQuery((new ProjectQuery())->setCustomers([$sharedProject->getCustomer()]));
    }
}
