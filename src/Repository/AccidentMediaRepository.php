<?php

namespace App\Repository;

use App\Entity\AccidentMedia;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccidentMedia>
 *
 * @method AccidentMedia|null find($id, $lockMode = null, $lockVersion = null)
 * @method AccidentMedia|null findOneBy(array $criteria, array $orderBy = null)
 * @method AccidentMedia[]    findAll()
 * @method AccidentMedia[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AccidentMediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccidentMedia::class);
    }
}
