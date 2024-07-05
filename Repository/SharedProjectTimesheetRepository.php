<?php

/*
 * This file is part of the "Shared Project Timesheets Bundle" for Kimai.
 * All rights reserved by Fabian Vetter (https://vettersolutions.de).
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\SharedProjectTimesheetsBundle\Repository;

use App\Entity\Project;
use App\Repository\Loader\DefaultLoader;
use App\Repository\Paginator\LoaderPaginator;
use App\Repository\Query\BaseQuery;
use App\Utils\Pagination;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use KimaiPlugin\SharedProjectTimesheetsBundle\Entity\SharedProjectTimesheet;

/**
 * @extends EntityRepository<SharedProjectTimesheet>
 */
class SharedProjectTimesheetRepository extends EntityRepository
{
    public function findAllSharedProjects(BaseQuery $query): Pagination
    {
        $qb = $this->createQueryBuilder('spt')
            ->join(Project::class, 'p', Join::WITH, 'spt.project = p')
            ->orderBy('p.name, spt.shareKey', 'ASC');

        $loader = new LoaderPaginator(new DefaultLoader(), $qb, $this->count([]));

        return new Pagination($loader, $query);
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

    public function findByProjectAndShareKey(Project|int|null $project, ?string $shareKey): ?SharedProjectTimesheet
    {
        try {
            return $this->createQueryBuilder('spt')
                ->where('spt.project = :project')
                ->andWhere('spt.shareKey = :shareKey')
                ->setMaxResults(1)
                ->setParameter('project', $project)
                ->setParameter('shareKey', $shareKey)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            // We can ignore that as we have a unique database key for project and shareKey
            return null;
        }
    }
}
