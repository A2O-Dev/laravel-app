<?php
/**
 * Created by PhpStorm.
 * User: Ariel
 * Date: 5/26/2017
 * Time: 3:30 PM
 */

namespace App\Repositories;

use App\Models\CallSheet;
use App\Models\Project;
use App\Models\ProjectTeam\Member;
use App\Models\ProjectTeam\Role;
use App\Models\ReqProfile;
use App\Models\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

class UserRepository extends BaseRepository
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
        $this->className = User::class;
    }

    /**
     * @param $email
     * @return User|null
     */
    public function getByEmail($email) {
        $q = $this->em->createQuery();
        $dql = "select u from ".$this->className." as u
            where u.email = :email
        ";
        $q->setParameter('email', $email);
        $q->setDQL($dql);
        $q->setMaxResults(1);
        return $q->getOneOrNullResult();
    }

    /**
     * @param $newEmail
     * @return User|null
     */
    public function getByNewEmail($newEmail) {
        $q = $this->em->createQuery();
        $dql = "select u from ".$this->className." as u
            where u.newEmail = :newEmail
        ";
        $q->setParameter('newEmail', $newEmail);
        $q->setDQL($dql);
        $q->setMaxResults(1);
        return $q->getOneOrNullResult();
    }

    public function getAllUser( $searchValue = null,
                            $limit = 10, $offset = 0,
                            $order = [['column' => 'id','dir' => 'asc']]
    ) {
        $query = $this->em->createQuery();

        $dql = "select u
                from ".$this->className." as u 
                where 1=1 ";

        if (!is_null($searchValue)) {
            $dql .= " and (u.name like :searchValue or u.email like :searchValue)";
            $query->setParameter('searchValue', '%'.$searchValue.'%');
        }

        $columnsMap = [
            'u.id' => ['id']
        ];
        $dql .= $this->dqlOrder($order, $columnsMap);
        $query->setDQL($dql);
        $query->setMaxResults($limit);
        $query->setFirstResult($offset);

        return $query->getResult();

    }

    public function countAll($searchValue = null) {
        $q = $this->em->createQuery();
        $dql = "select count(u.id) from ".$this->className." u
            where 1=1 
        ";

        if (!is_null($searchValue)) {
            $dql .= " and (u.name like :searchValue or u.email like :searchValue)";
            $q->setParameter('searchValue', '%'.$searchValue.'%');
        }

        $q->setDQL($dql);
        return $q->getSingleScalarResult();
    }

    /**
     * @param $days
     * @param int $limit
     * @param int $offset
     * @return ArrayCollection | User[]
     */
    public function getAllInactive($days, $limit = 10, $offset = 0)
    {
        $q = $this->em->createQuery();
        $dql = "select u from " . $this->className . " u
                join u.userProfile up
            where DATEDIFF(CURRENT_DATE(),u.lastActive) > :days";
        $q->setParameter('days', $days);
        $q->setMaxResults($limit);
        $q->setFirstResult($offset);

        $q->setDQL($dql);

        return $q->getResult();
    }

    /**
     * @param CallSheet $callSheet
     * @return ArrayCollection | User[]
     */
    public function getAllByCallSheet(CallSheet $callSheet)
    {

        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata($this->className, 'cs');

        $query = $this->em->createNativeQuery("", $rsm);

        $sql = "SELECT u.* FROM users AS u
                INNER JOIN ( SELECT DISTINCT u.id FROM users AS u
                           INNER JOIN spec_profiles AS sp ON sp.user_id = u.id
                           INNER JOIN profile_matchings AS pm ON pm.spec_profile_id = sp.id
                           INNER JOIN talent_schedules AS ts ON ts.profile_matching_id = pm.id
                           WHERE ts.call_sheet_id = :callSheetId AND ts.profile_matching_id IS NOT NULL) AS tsu ON tsu.id = u.id
                UNION

                SELECT u.* FROM users AS u
                INNER JOIN ( SELECT DISTINCT u.id FROM users AS u
                           INNER JOIN spec_profiles AS sp ON sp.user_id = u.id
                           INNER JOIN profile_matchings AS pm ON pm.spec_profile_id = sp.id
                           INNER JOIN crew_schedules AS cws ON cws.profile_matching_id = pm.id
                           WHERE cws.call_sheet_id = :callSheetId AND cws.profile_matching_id IS NOT NULL) AS tsu ON tsu.id = u.id
                UNION

                SELECT u.* FROM users AS u
                INNER JOIN ( SELECT DISTINCT u.id FROM users AS u
                           INNER JOIN spec_profiles AS sp ON sp.user_id = u.id
                           INNER JOIN profile_matchings AS pm ON pm.spec_profile_id = sp.id
                           INNER JOIN call_sheet_extra AS cse ON cse.profile_matching_id = pm.id
                           WHERE cse.call_sheet_id = :callSheetId AND cse.profile_matching_id IS NOT NULL) AS tsu ON tsu.id = u.id";

        $query->setParameter('callSheetId', $callSheet->getId());

        $query->setSQL($sql);

        return $query->getResult();
    }

    public function getAllUserByFilters(
        User $user = null,
        $projectList = [],
        $categoryList = [],
        $reqProfileList = [],
        $statusList = ['active'],
        $statusMatchingList = [],
        $teamOnly = false,
        $onlyProjectAdmin = false,
        $searchValue = null,
        $limit = 10, $offset = 0,
        $order = [['column' => 'id', 'dir' => 'asc']]
    )
    {

        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata($this->className, 'u');

        $query = $this->em->createNativeQuery("", $rsm);

        $searchValueQuery='';
        if (!is_null($searchValue)) {
            $searchValueQuery = " and (u.name like :searchValue or u.email like :searchValue)";
            $query->setParameter('searchValue', '%' . $searchValue . '%');
        }

        $onlyProjectAdminQuery2 = "";
        $projectQueryWhere="";
        if (count($projectList) > 0) {
            $projectIds = [];
            foreach ($projectList as $project) {
                $projectIds[] = $project->getId();
            }
            $projectQueryWhere = " and p.id in (:projectIds) ";

            //Projects where the actual user is part of
            $projectQueryWhereUserProjects = " AND p.id in(SELECT mp.id FROM projects mp
            INNER JOIN project_teams pt ON mp.id = pt.project_id
            INNER JOIN project_team_members ptm ON pt.id = ptm.project_team_id
            INNER JOIN users u3 ON ptm.user_id = u3.id 
            WHERE u3.id = :userId AND mp.status='active') ";

            if($onlyProjectAdmin && !$teamOnly){
                $onlyProjectAdminQuery2 = " 
               LEFT JOIN project_teams pt ON p.id = pt.project_id
               LEFT JOIN project_team_members ptm ON pt.id = ptm.project_team_id
               LEFT JOIN project_team_roles ptr ON ptm.project_team_role_id = ptr.id";
                $projectQueryWhere .= " AND (ptr.role_key = :teamMemberRoleKey) ";
                $query->setParameter('teamMemberRoleKey',Role::SUPER_ADMIN_KEY);
            }
            $query->setParameter('projectIds', $projectIds);
        }

        $reqProfileQuery = "";
        if(!$teamOnly){

            $categoryQueryWhere = "";
            if (count($categoryList) > 0) {
                $categoryIds = [];
                foreach ($categoryList as $category) {
                    $categoryIds[] = $category->getId();
                }
                $categoryQueryWhere = " and (c.id in (:categoryIds) or c.parent_id in (:categoryIds)) ";
                $query->setParameter('categoryIds', $categoryIds);
            }

            $reqProfileQueryWhere = "";
            if (count($reqProfileList) > 0) {

                $reqProfileIds = [];
                foreach ($reqProfileList as $reqProfile) {
                    $reqProfileIds[] = $reqProfile->getId();
                }

                $reqProfileQueryWhere = " and rp.id in (:reqProfileIds) ";
                $query->setParameter('reqProfileIds', $reqProfileIds);
            }
            $statusMatchingQueryWhere = "";
            if (count($statusMatchingList) > 0) {
                $statusMatchingQueryWhere .= " and pm.status in (:statusMatchingList) ";
                $query->setParameter('statusMatchingList', $statusMatchingList);
            }

            $statusQueryWhere = "";
            if(count($statusList) > 0) {
                $statusQueryWhere .= " and rp.status in (:statusList)";
                $query->setParameter('statusList', $statusList);
            }

            $reqProfileQuery = "UNION (SELECT u.*
                    FROM users u
                      JOIN spec_profiles sp ON u.id = sp.user_id
                      JOIN profile_matchings pm ON sp.id = pm.spec_profile_id
                      JOIN req_profiles rp ON pm.req_profile_id = rp.id
                      JOIN users u2 ON rp.created_by = u2.id
                      JOIN projects p ON rp.project_id = p.id"
                        .$onlyProjectAdminQuery2.
                    " JOIN categories c ON rp.category_id = c.id
                    WHERE u.id !=:userId and sp.status = 'active' " . $statusQueryWhere . $projectQueryWhere . $projectQueryWhereUserProjects . $categoryQueryWhere . $reqProfileQueryWhere . $statusMatchingQueryWhere . "
                    GROUP BY u.id)";
        }

        $sql = " select u.* from ((SELECT u2.*
             FROM users AS u
               JOIN projects p ON u.id = p.user_id
               LEFT JOIN project_teams pt ON p.id = pt.project_id
               LEFT JOIN project_team_members ptm ON pt.id = ptm.project_team_id
               LEFT JOIN project_team_roles ptr ON ptm.project_team_role_id = ptr.id
               LEFT JOIN users u2 ON ptm.user_id = u2.id
             WHERE u2.id !=:userId "
            .$projectQueryWhere.") ". $reqProfileQuery.") as u 
             order by u.name
             limit " .$offset.", ".$limit;

        $query->setParameter('userId', $user->getId());
        $query->setSQL($sql);

        return $query->getResult();
    }

}