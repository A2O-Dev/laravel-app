<?php

namespace App\Repositories;

use App\Models\Matching\MatchingFavorite;
use App\Models\ProfileMatching;
use App\Models\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

class MatchingFavoriteRepository extends BaseRepository
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
        $this->className = MatchingFavorite::class;
    }

    /**
     * @param ProfileMatching $profileMatching
     * @param User $owner
     * @param int $limit
     * @param int $offset
     * @return mixed
     */
    public function getAllByProfileMatching(ProfileMatching $profileMatching, User $owner = null, $limit = 10, $offset = 0) {
        $query = $this->em->createQuery();
        $dql = "select mf from " . $this->className . " mf
            where IDENTITY(mf.profileMatching) = :profileMatchingId
             ";
        $query->setParameter('profileMatchingId', $profileMatching->getId());

        if (!is_null($owner)) {
            $dql .= " and IDENTITY(mf.owner) = :userId";
            $query->setParameter('userId', $owner->getId());
        }

        $query->setMaxResults($limit);
        $query->setFirstResult($offset);
        $query->setDQL($dql);
        return $query->getResult();
    }

    /**
     * @param ProfileMatching $profileMatching
     * @return int
     */
    public function countAllByProfileMatching(ProfileMatching $profileMatching)
    {
        $query = $this->em->createQuery();
        $dql = "select COUNT (mf) from " . $this->className . " mf
            left join mf.profileMatching pm
            where pm.id = :profileMatchingId";

        $query->setParameter('profileMatchingId', $profileMatching->getId());

        $query->setDQL($dql);
        return $query->getSingleScalarResult();
    }
}