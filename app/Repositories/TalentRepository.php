<?php
/**
 * Created by PhpStorm.
 * User: Ariel
 * Date: 5/26/2017
 * Time: 3:30 PM
 */

namespace App\Repositories;

use App\Models\Location;
use App\Models\Casting;
use App\Models\CastingTalent;
use App\Models\Talent;
use App\Models\User;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

class TalentRepository extends BaseRepository
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
        $this->className = Talent::class;
    }

    public function getByUser(User $user) {
        $query = $this->em->createQuery();
        $dql = "select e from ".$this->className. " e
            where IDENTITY(e.user) = :userId
        ";
        $query->setParameter('userId', $user->getId());

        $query->setDQL($dql);
        $query->setMaxResults(1);
        return $query->getOneOrNullResult();
    }

    public function getAllAvailableByCasting(Casting $casting, $limit = 10, $offset = 0) {
        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata($this->className, 't');
        $paramCount = 0;

        $query = $this->em->createNativeQuery("", $rsm);

        $sqlSelect = "
            select t.* from talents as t
            inner join talent_casting_types trt on trt.talent_id = t.id
            inner join talent_project_types tpt on tpt.talent_id = t.id
            inner join talent_casting_attribute_values trav on trav.talent_id = t.id
            left join casting_talents rt on rt.talent_id = t.id and rt.casting_id = ?
        ";

        $paramCount++;
        $query->setParameter($paramCount, $casting->getId(), Type::INTEGER);

        if (!is_null($casting->getSearchRadius())) {
            $sqlSelect .= "
                inner join (
                    SELECT
                        t1.id,
                        (
                            3959 * acos(
                                cos(radians(?)) * cos(radians(l.latitude)) * cos(
                                    radians(l.longitude) - radians(?)
                                ) + sin(radians(?)) * sin(radians(l.latitude))
                            )
                        ) AS distance
                    FROM
                        talents t1
                        INNER JOIN locations l on l.id = t1.location_id
                    HAVING
                        distance < ?
                ) as tloc on tloc.id = t.id
            ";
            $paramCount++;
            $query->setParameter($paramCount, $casting->getLocation()->getLatitude(), Type::DECIMAL);
            $paramCount++;
            $query->setParameter($paramCount, $casting->getLocation()->getLongitude(), Type::DECIMAL);
            $paramCount++;
            $query->setParameter($paramCount, $casting->getLocation()->getLatitude(), Type::DECIMAL);
            $paramCount++;
            $query->setParameter($paramCount, $casting->getSearchRadius(), Type::INTEGER); //The radius in miles
        }

        // restrict casting type
        $sqlWhere = " where trt.casting_type_id = ? ";
        $paramCount++;
        $query->setParameter($paramCount, $casting->getType()->getId(), Type::INTEGER);

        // restrict project type
        $sqlWhere .= " and tpt.project_type_id = ?";
        $paramCount++;
        $query->setParameter($paramCount, $casting->getProject()->getType()->getId(), Type::INTEGER);

        // Payment Type Restriction
        if (!is_null($casting->getPaymentType())) {
            $sqlWhere .= " and (t.payment_type = ? or t.payment_type is null)";
            $paramCount++;
            $query->setParameter($paramCount, $casting->getPaymentType(), Type::STRING);
        }

        //restrict unionType
        $unionTypeIdList = $casting->getFilteredAttributeIds('unionTypes', 'union');
        if(count($unionTypeIdList['unionTypes']) > 0){
            $sqlWhere .= " and trav.casting_attribute_value_id in (?)";
            $paramCount++;
            $query->setParameter($paramCount, $unionTypeIdList['unionTypes']);
        }
        else $sqlWhere .= " and 0>0";

        if (!is_null($casting->getRoleAge())) {
            $roleAge = $casting->getRoleAge();
            if (array_key_exists('minValue', $roleAge) && is_int($roleAge['minValue'])
                && array_key_exists('maxValue', $roleAge) && is_int($roleAge['maxValue'])
            ) {
                $sqlWhere .= " and ((YEAR(CURDATE()) - YEAR(t.birthday)) between ? and ?) ";
                $paramCount++;
                $query->setParameter($paramCount, $roleAge['minValue'], Type::INTEGER);
                $paramCount++;
                $query->setParameter($paramCount, $roleAge['maxValue'], Type::INTEGER);
            }
        }

        if (!is_null($casting->getRoleGender())) {
            $sqlWhere .= " and (t.gender = ? or t.gender is null)";
            $paramCount++;
            $query->setParameter($paramCount, $casting->getRoleGender(), Type::STRING);
        }

        $sqlWhere .= " and rt.id is null ";

        $sql = $sqlSelect . $sqlWhere . " limit ? offset ?";
        $paramCount++;
        $query->setParameter($paramCount, $limit, Type::INTEGER);
        $paramCount++;
        $query->setParameter($paramCount, $offset, Type::INTEGER);

        $query->setSQL($sql);

        return $query->getResult();
    }

    public function getAllByLocation(Location $location) {
        $query = $this->em->createQuery();
        $dql = "select e from ". $this->className. " e 
            where IDENTITY(e.location) = :locationId
        ";
        $query->setParameter('locationId', $location->getId());
        $query->setDQL($dql);
        return $query->getResult();
    }
}