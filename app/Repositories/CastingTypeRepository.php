<?php
/**
 * Created by PhpStorm.
 * User: Ariel
 * Date: 5/26/2017
 * Time: 3:30 PM
 */

namespace App\Repositories;

use App\Models\Organization;
use App\Models\Project;
use App\Models\CastingType;
use Doctrine\ORM\EntityManagerInterface;

class CastingTypeRepository extends BaseRepository
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
        $this->className = CastingType::class;
    }

    /**
     * @param $code
     * @return Project|null
     */
    public function getByCode($code) {
        $query = $this->em->createQuery();
        $dql = "select p from ". $this->className ." p where p.code = :code";

        $query->setParameter("code", $code);
        $query->setDQL($dql);
        $query->setMaxResults(1);

        return $query->getOneOrNullResult();
    }


}