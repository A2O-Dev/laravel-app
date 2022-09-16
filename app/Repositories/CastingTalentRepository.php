<?php
/**
 * Created by PhpStorm.
 * User: Ariel
 * Date: 11/28/2017
 * Time: 11:46 AM
 */

namespace App\Repositories;


use App\Models\CastingDirector;
use App\Models\CastingTalent;
use App\Models\Casting;
use App\Models\Talent;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;

class CastingTalentRepository extends BaseRepository
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
        $this->className = CastingTalent::class;
    }

    public function deleteAllPendingByCasting(Casting $casting) {
        $q = $this->em->createQuery();
        $dql = "delete from ".$this->className." m
            where IDENTITY(m.casting) = :castingId 
            and (m.status = :statusMatched or m.status = :statusChecked)
            ";
        $q->setParameter('castingId', $casting->getId());
        $q->setParameter('statusMatched', CastingTalent::STATUS_MATCHED);
        $q->setParameter('statusChecked', CastingTalent::STATUS_CHECKED);

        $q->setDQL($dql);
        $numDeleted = $q->execute();
        return $numDeleted;
    }

    public function deleteAllPendingByTalent(Talent $talent) {
        $q = $this->em->createQuery();
        $dql = "delete from ".$this->className." m
            where IDENTITY(m.talent) = :talentId
            and (m.status = :statusMatched or m.status = :statusChecked)
            ";
        $q->setParameter('talentId', $talent->getId());
        $q->setParameter('statusMatched', CastingTalent::STATUS_MATCHED);
        $q->setParameter('statusChecked', CastingTalent::STATUS_CHECKED);

        $q->setDQL($dql);
        $numDeleted = $q->execute();
        return $numDeleted;
    }

    public function getAllByCasting(Casting $casting, $limit = 10, $offset = 0, $inStatus = null,
                                     $order = [['column' => 'id', 'dir' => 'desc']]) {
        $q = $this->em->createQuery();
        $dql = "select m from ".$this->className." m
            join m.talent t
            where IDENTITY(m.casting) = :castingId
        ";
        $q->setParameter('castingId', $casting->getId());

        if (!is_null($inStatus)) {
            $dql .= ' and m.status in (:inStatus)';
            $q->setParameter('inStatus', $inStatus);
        }

        $columnsMap = [
            't.isPlus' => ['isPlus'],
            'm.matchingPercentage' => ['matchingPercentage'],
            'm.id' => ['id']
        ];

        $dql .= $this->dqlOrder($order, $columnsMap);

        $q->setDQL($dql);
        $q->setMaxResults($limit)->setFirstResult($offset);

        return $q->getResult();
    }

    /**
     * @param Casting $casting
     * @param null $inStatus
     * @return integer
     */
    public function countAllByCasting(Casting $casting, $inStatus = null) {
        $q = $this->em->createQuery();
        $dql = "select count(m.id) from ".$this->className." m
            where IDENTITY(m.casting) = :castingId
        ";
        $q->setParameter('castingId', $casting->getId());

        if (!is_null($inStatus)) {
            $dql .= ' and m.status in (:inStatus)';
            $q->setParameter('inStatus', $inStatus);
        }

        $q->setDQL($dql);

        return $q->getSingleScalarResult();
    }

    public function countAllByCastingDirector(CastingDirector $castingDirector, $status = null){
        $params = [];
        $sql = "Select count(rct.id) as total
                from casting_talents rct
                inner join castings rc on rct.casting_id = rc.id
                inner join casting_directors cd on rc.castingDirector_id = cd.id
                WHERE cd.id = ? ";
        $params[] = $castingDirector->getId();

        if(!is_null($status)){
            $sql .= " AND rct.status = ? ";
            $params[] = $status;
        }

        $query = $this->em->getConnection()->prepare($sql);
        foreach ($params as $key => $param) {
            $query->bindValue($key + 1, $param);
        }

        $query->execute();

        return $query->fetchAll();
    }

    public function countAllByTalent(Talent $talent, $status = null){
        $q = $this->em->createQuery();
        $dql = "select COUNT(m) from ".$this->className." m
            where IDENTITY(m.talent) = :talentId ";
        $q->setParameter('talentId', $talent->getId());

        if (!is_null($status)) {
            $dql .= ' and m.status = :status';
            $q->setParameter('status', $status);
        }

        $q->setDQL($dql);

        return $q->getSingleScalarResult();
    }


    public function getAllByTalent(Talent $talent, $limit = 10, $offset = 0, $inStatus = null,
                                   $order = [[
                                        'column' => 'id',
                                        'dir' => 'desc']])
    {
        $q = $this->em->createQuery();
        $dql = "select m from ".$this->className." m
            where IDENTITY(m.talent) = :talentId
        ";
        $q->setParameter('talentId', $talent->getId());

        if (!is_null($inStatus)) {
            $dql .= ' and m.status in (:inStatus)';
            $q->setParameter('inStatus', $inStatus);
        }

        $columnsMap = [
            'm.id' => ['id'],
            'm.updatedAt' => ['updatedAt']
        ];
        $dql .= $this->dqlOrder($order, $columnsMap);

        $q->setDQL($dql);
        $q->setMaxResults($limit);
        $q->setFirstResult($offset);

        return $q->getResult();
    }

    public function getByCastingAndTalent(Casting $casting, Talent $talent){

        $q = $this->em->createQuery();
        $dql = "select m from ".$this->className." m
            where IDENTITY(m.casting) = :castingId and 
                  IDENTITY(m.talent) = :talentId";
        $q->setParameter('castingId', $casting->getId());
        $q->setParameter('talentId', $talent->getId());

        $q->setDQL($dql);

        return $q->getResult();
    }

    /**
     * @param $status
     * @param int $limit
     * @param int $offset
     * @return CastingTalent[]|ArrayCollection
     */
    public function getAllByStatus($status, $limit = 10, $offset = 0) {
        $q = $this->em->createQuery();
        $dql = "select rt from ".$this->className." rt
            where rt.status = :status";
        $q->setParameter('status', $status);
        $q->setMaxResults($limit);
        $q->setFirstResult($offset);

        $q->setDQL($dql);

        return $q->getResult();
    }

}