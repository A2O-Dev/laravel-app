<?php

namespace App\Repositories;

use App\Models\BlackListProfile;
use App\Models\Category;
use App\Models\Chat;
use App\Models\FavoriteProfile;
use App\Models\Invite;
use App\Models\ProfileMatching;
use App\Models\ProjectTeam\Member;
use App\Models\ReqProfile;
use App\Models\SpecProfile;
use App\Models\Project;
use App\Models\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

class ProfileMatchingRepository extends BaseRepository
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
        $this->className = ProfileMatching::class;
    }

    /**
     * @param $reqProfile
     * @param User|null $user
     * @param int $limit
     * @param int $offset
     * @param null $inStatus
     * @param array $order
     * @param bool $onlyWithoutChat
     * @return ProfileMatching[]|ArrayCollection
     */
    public function getAllByReqProfile($reqProfile, User $user = null, $limit = 10, $offset = 0, $inStatus = null,
                                     $order = [['column' => 'id', 'dir' => 'desc']],$onlyWithoutChat = false) {
        $query = $this->em->createQuery();

        if (is_array($reqProfile)) {
            $reqProfileIds = [];
            /**@var ReqProfile $reqProfileItem*/
            foreach ($reqProfile as $reqProfileItem){
                $reqProfileIds [] = $reqProfileItem->getId();
            }
            $reqProfileQuery = " IDENTITY(m.reqProfile) in (:reqProfileId) ";
            $query->setParameter('reqProfileId', $reqProfileIds);
        } else {
            $reqProfileQuery = " IDENTITY(m.reqProfile) = :reqProfileId ";
            $query->setParameter('reqProfileId', $reqProfile->getId());
        }

        $dql = "select m from ".$this->className." m
            left join m.specProfile t
            left join t.user u
            left join u.roles r
            where t.status = :specProfileStatus and ".$reqProfileQuery."
             ";

        if(!is_null($user)){
            $dql .= " and u.id = :userId";
            $query->setParameter('userId',$user->getId());
        }

        $query->setParameter('specProfileStatus', SpecProfile::STATUS_ACTIVE);

        if (!is_null($inStatus)) {
            $dql .= ' and m.status in (:inStatus)';
            $query->setParameter('inStatus', $inStatus);
        }

        if($onlyWithoutChat){
            $dql .= " and m not in (select pm from ".Chat::class." as c
            join c.profileMatchingList as pm
            )";
        }

        $columnsMap = [
            'm.auditionTapeLink' => ['auditionTapeLink'],
            'm.matchingPercentage' => ['matchingPercentage'],
            'm.id' => ['id'],
            'm.updatedAt' => ['updatedAt'],
            'u.lastActive' => ['lastActive'],
            "r.priority" => ['priority']
        ];

        $dql .= $this->dqlOrder($order, $columnsMap);

        $query->setDQL($dql);
        $query->setMaxResults($limit)->setFirstResult($offset);

        return $query->getResult();
    }

    /**
     * Get All profile matchings by reqProfile with a mark showing whether the specProfile
     * is/is not favorite of a passed favoriteAndBlacklistOwner
     * @param $reqProfile
     * @param User|null $favoriteAndBlacklistOwner
     * @param int $limit
     * @param int $offset
     * @param null $inStatus
     * @param array $order
     * @param bool $onlyWithoutChat
     * @param User|null $specProfilesOwner
     * @param User|null $favoriteAndBlacklistOwner
     * @param boolean $onlyWithoutBlacklistSpecProfiles
     * @return mixed
     */
    public function getAllByReqProfileWithFavorites($reqProfile, $limit = 10, $offset = 0, $inStatus = null,
                                                    $order = [['column' => 'id', 'dir' => 'desc']],
                                                    User $favoriteAndBlacklistOwner, $onlyWithoutBlacklistSpecProfiles = false,
                                                    User $specProfilesOwner = null, $onlyWithoutChat = false)
    {
        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata($this->className, "m");
        $rsm->addScalarResult('is_favorite', 'is_favorite');
        $paramCount = 0;

        $query = $this->em->createNativeQuery("", $rsm);

        $sqlSelect = "select DISTINCT m.*, CASE WHEN mfp.id IS NOT NULL THEN true WHEN mfp.id IS NULL THEN false END as is_favorite
            from profile_matchings m
            inner join spec_profiles t on m.spec_profile_id = t.id
            inner join users u on t.user_id = u.id
            inner join role_user ru on u.id = ru.user_id
            inner join roles r on ru.role_id = r.id 
            LEFT JOIN (SELECT sp2.id from spec_profiles sp2 inner JOIN favorite_profiles fp on fp.spec_profile_id = sp2.id where fp.user_id = ?) as mfp on t.id = mfp.id";

        $paramCount++;
        $query->setParameter($paramCount, $favoriteAndBlacklistOwner->getId());

        $paramCount++;
        $sqlWhere = " where t.status = ? ";
        $query->setParameter($paramCount, SpecProfile::STATUS_ACTIVE);

        if ($onlyWithoutBlacklistSpecProfiles) {
            $sqlWhere .= " and t.id not in (select blp.spec_profile_id from black_list_profiles blp where blp.user_id = ?) ";
            $paramCount++;
            $query->setParameter($paramCount, $favoriteAndBlacklistOwner->getId());
        }

        if (!is_null($specProfilesOwner)) {
            $paramCount++;
            $sqlWhere.= " and u.id = ? ";
            $query->setParameter($paramCount, $specProfilesOwner->getId());
        }

        $paramCount++;
        if (is_array($reqProfile)) {
            $reqProfileIds = [];
            /**@var ReqProfile $reqProfileItem*/
            foreach ($reqProfile as $reqProfileItem){
                $reqProfileIds [] = $reqProfileItem->getId();
            }
            $reqProfileQuery = " and m.req_profile_id in (?) ";
            $query->setParameter($paramCount, $reqProfileIds);
        } else {
            $reqProfileQuery = " and m.req_profile_id = ? ";
            $query->setParameter($paramCount, $reqProfile->getId());
        }
        $sqlWhere.= $reqProfileQuery;

        if (!is_null($inStatus)) {
            $paramCount++;
            $sqlWhere.= ' and m.status in (?)';
            $query->setParameter($paramCount, $inStatus);
        }

        if($onlyWithoutChat){
            $sqlWhere.= " and m.id not in (select pm.id from profile_matchings as pm
            inner join chats as c on pm.chat_id = c.id
            ) ";
        }

        //TODO Set dynamic order
        $sql = $sqlSelect . $sqlWhere . " order by is_favorite desc, m.audition_tape_link desc, m.matching_percentage desc, m.id desc, m.updated_at, u.last_active, r.priority desc";

        $sql.= " limit ? offset ? ";
        $paramCount++;
        $query->setParameter($paramCount, (int)$limit, Type::INTEGER);
        $paramCount++;
        $query->setParameter($paramCount, (int)$offset, Type::INTEGER);

        $query->setSQL($sql);
        return $query->getResult();
    }

    /**
     * @param ReqProfile $reqProfile
     * @param null $inStatus
     * @param bool $withoutBlacklistSpecProfiles
     * @param User $user
     * @return int
     */
    public function countAllByReqProfile(ReqProfile $reqProfile, $inStatus = null, $withoutBlacklistSpecProfiles = false, User $user = null) {
        $q = $this->em->createQuery();
        $dql = "select count(m.id) from ".$this->className." m
            join m.specProfile sp
            where IDENTITY(m.reqProfile) = :reqProfileId
            and sp.status = :specProfileStatus
        ";

        if ($withoutBlacklistSpecProfiles && !is_null($user)) {
            $dql.= " and sp not in(select sp2 from " . BlackListProfile::class . " fp join fp.specProfile as sp2 where fp.user=:userId)";
            $q->setParameter("userId", $user->getId());
        }

        $q->setParameter('reqProfileId', $reqProfile->getId());
        $q->setParameter('specProfileStatus', SpecProfile::STATUS_ACTIVE);

        if (!is_null($inStatus)) {
            $dql .= ' and m.status in (:inStatus)';
            $q->setParameter('inStatus', $inStatus);
        }

        $q->setDQL($dql);

        return $q->getSingleScalarResult();
    }

    /**
     * @param User $owner
     * @param string|null $status
     * @return int
     */
    public function countAllByReqProfileOwner(User $owner, $status = null){
        $params = [];
        $sql = "Select count(rct.id) as total
                from profile_matchings rct
                inner join req_profiles rc on rct.req_profile_id = rc.id
                inner join users cd on rc.user_id = cd.id
                WHERE cd.id = ? ";
        $params[] = $owner->getId();

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

    /**
     * @param SpecProfile $specProfile
     * @param null $inStatus
     * @return int
     */
    public function countAllBySpecProfile(SpecProfile $specProfile, $inStatus = null){
        $q = $this->em->createQuery();
        $dql = "select COUNT(m) from ".$this->className." m
            join m.reqProfile rp
            where IDENTITY(m.specProfile) = :specProfileId 
                and rp.status = :reqProfileStatus
            ";
        $q->setParameter('specProfileId', $specProfile->getId());
        $q->setParameter('reqProfileStatus', ReqProfile::STATUS_ACTIVE);

        if (!is_null($inStatus)) {
            $dql .= ' and m.status in (:inStatus)';
            $q->setParameter('inStatus', $inStatus);
        }

        $q->setDQL($dql);

        return $q->getSingleScalarResult();
    }

    /**
     * @param SpecProfile $specProfile
     * @param int $limit
     * @param int $offset
     * @param null $inStatus
     * @param array $order
     * @return ArrayCollection|ProfileMatching[]
     */
    public function getAllBySpecProfile(SpecProfile $specProfile, $limit = 10, $offset = 0, $inStatus = null,
                                   $order = [[
                                        'column' => 'id',
                                        'dir' => 'desc']])
    {
        $q = $this->em->createQuery();
        $dql = "select m from ".$this->className." m
            join m.reqProfile rp
            join m.specProfile t
            join t.user u
            join u.roles r
            where IDENTITY(m.specProfile) = :specProfileId
            and rp.status = :reqProfileStatus
        ";
        $q->setParameter('specProfileId', $specProfile->getId());
        $q->setParameter('reqProfileStatus', ReqProfile::STATUS_ACTIVE);
        if (!is_null($inStatus)) {
            $dql .= ' and m.status in (:inStatus)';
            $q->setParameter('inStatus', $inStatus);
        }

        $columnsMap = [
            'm.matchingPercentage' => ['matchingPercentage'],
            'm.id' => ['id'],
            'm.updatedAt' => ['updatedAt'],
            'u.lastActive' => ['lastActive'],
            "r.key" => ['plus']
        ];
        $dql .= $this->dqlOrder($order, $columnsMap);

        $q->setDQL($dql);
        $q->setMaxResults($limit);
        $q->setFirstResult($offset);

        return $q->getResult();
    }

    /**
     * @param Project $project
     * @param Category|null $category
     * @param int $limit
     * @param int $offset
     * @param null $status
     * @param null $searchValue
     * @param array $order
     * @return ProfileMatching[]|ArrayCollection
     */
    public function getAllByProject(Project $project, Category $category = null, $limit = 10, $offset = 0, $status = null,
                                    $searchValue = null,
                                    $order = [[
                                            'column' => 'id',
                                            'dir' => 'desc']])
    {
        $q = $this->em->createQuery();
        $dql = "select m from ".$this->className." m
            join m.reqProfile rp
            join m.specProfile sp
            where IDENTITY(rp.project) = :projectId
            and rp.status = :reqProfileStatus
            and sp.status = :specProfileStatus
        ";

        $q->setParameter('projectId', $project->getId());
        $q->setParameter('reqProfileStatus', ReqProfile::STATUS_ACTIVE);
        $q->setParameter('specProfileStatus', SpecProfile::STATUS_ACTIVE);

        if (!is_null($status)) {
            $dql .= ' and m.status in (:status)';
            $q->setParameter('status', $status);
        }

        if (!is_null($category)) {
            $dql .= ' and IDENTITY(rp.category) in (select c.id from '.Category::class.' c where c.id = :categoryId or c.parent = :categoryId)';
            $q->setParameter('categoryId', $category->getId());
        }

        if (!is_null($searchValue)) {
            $dql .= " and (
                 sp.title like :searchValue
                 or rp.name like :searchValue
                 )";
            $q->setParameter('searchValue', '%'.$searchValue.'%');
        }

        $dql .= $this->dqlOrder($order);

        $q->setDQL($dql);
        $q->setMaxResults($limit);
        $q->setFirstResult($offset);

        return $q->getResult();
    }

    /**
     * @param Project $project
     * @param null $status
     * @param null $searchValue
     * @return mixed
     */
    public function countAllByProject(Project $project, $status = null, $searchValue = null){
        $q = $this->em->createQuery();
        $dql = "select COUNT(m) from ".$this->className." m
            join m.specProfile sp
            join sp.category c
            join m.reqProfile rp
            join rp.project p
            where IDENTITY(rp.project) = :projectId
            and sp.status = :specProfileStatus
            and rp.status = :reqProfileStatus
        ";
        $q->setParameter('projectId', $project->getId());
        $q->setParameter('reqProfileStatus', ReqProfile::STATUS_ACTIVE);
        $q->setParameter('specProfileStatus', SpecProfile::STATUS_ACTIVE);

        if (!is_null($status)) {
            $dql .= ' and m.status in (:status)';
            $q->setParameter('status', $status);
        }

        if (!is_null($searchValue)) {
            $dql .= " and (
                 c.name like :searchValue
                 )";
            $q->setParameter('searchValue', '%'.$searchValue.'%');
        }

        $q->setDQL($dql);

        return $q->getSingleScalarResult();
    }

    /**
     * @param ReqProfile $reqProfile
     * @param SpecProfile $specProfile
     * @return ProfileMatching|null
     */
    public function getByReqProfileAndSpecProfile(ReqProfile $reqProfile, SpecProfile $specProfile){

        $q = $this->em->createQuery();
        $dql = "select m from ".$this->className." m
            where IDENTITY(m.reqProfile) = :reqProfileId and 
                  IDENTITY(m.specProfile) = :specProfileId";
        $q->setParameter('reqProfileId', $reqProfile->getId());
        $q->setParameter('specProfileId', $specProfile->getId());

        $q->setDQL($dql);
        $q->setMaxResults(1);

        return $q->getOneOrNullResult();
    }

    /**
     * @param $status
     * @param int $limit
     * @param int $offset
     * @return ArrayCollection|ProfileMatching[]
     */
    public function getAllByStatus($status, $limit = 10, $offset = 0) {
        $q = $this->em->createQuery();
        $dql = "select rt from ".$this->className." rt
            join rt.reqProfile rp
            join rt.specProfile sp
            where rt.status = :status 
            and rp.status = :reqProfileStatus
            and sp.status = :specProfileStatus
            ";
        $q->setParameter('status', $status);
        $q->setParameter('reqProfileStatus', ReqProfile::STATUS_ACTIVE);
        $q->setParameter('specProfileStatus', SpecProfile::STATUS_ACTIVE);
        $q->setMaxResults($limit);
        $q->setFirstResult($offset);

        $q->setDQL($dql);

        return $q->getResult();
    }


    public function countAllSpecProfileByUser(User $user, $inStatus = null){
        $q = $this->em->createQuery();
        $dql = "select COUNT(m) from ".$this->className." m
            join m.specProfile sp 
            where IDENTITY(sp.user) = :userId";
        $q->setParameter('userId', $user->getId());

        if (!is_null($inStatus)) {
            $dql .= ' and m.status in (:inStatus)';
            $q->setParameter('inStatus', $inStatus);
        }

        $q->setDQL($dql);

        return $q->getSingleScalarResult();
    }

    /**
     * @param User $user
     * @param ReqProfile $reqProfile
     * @param int $limit
     * @param int $offset
     * @param $profileMatchingStatus
     * @param array $order
     * @return ProfileMatching[]
     */
    public function getAllPreviousByReqProfileUser(User $user, ReqProfile $reqProfile, $limit = 10, $offset = 0, $profileMatchingStatus = null,
                                                        $order = [['column' => 'id', 'dir' => 'desc']]) {

        $q = $this->em->createQuery();
        $dql = "select pm from ".$this->className." pm
            join pm.specProfile as sp
            join pm.reqProfile  as rp
            join rp.project as p                            
            where IDENTITY(pm.reqProfile) <> :reqProfileId AND 
               pm.status in (:profileMatchingStatus)  AND
               sp.status = :specProfileStatus AND 
               (
                p.id in (SELECT pp.id
                      FROM ". Member::class." as mm INNER JOIN mm.projectTeam as te INNER JOIN te.project as pp
                      WHERE IDENTITY(mm.user) =:userId AND mm.status = :statusInvited) 
                OR IDENTITY(p.user)=:userId
               )
               and IDENTITY(sp.category) = :categoryId
        ";

        $q->setParameter('userId', $user->getId());
        $q->setParameter('statusInvited', Member::STATUS_CONFIRM);
        $q->setParameter('reqProfileId', $reqProfile->getId());
        $q->setParameter('profileMatchingStatus', $profileMatchingStatus);
        $q->setParameter('specProfileStatus', SpecProfile::STATUS_ACTIVE);
        $q->setParameter('categoryId', $reqProfile->getCategory()->getId());

        $columnsMap = [
            'sp.id' => ['id']
        ];

        $dql .= $this->dqlOrder($order, $columnsMap);

        $q->setDQL($dql);
        $q->setMaxResults($limit)->setFirstResult($offset);

        return $q->getResult();
    }

    /**
     * @param User $user
     * @param ReqProfile $reqProfile
     * @param int $limit
     * @param int $offset
     * @param $profileMatchingStatus
     * @return int
     */
    public function countAllPreviousByReqProfileUser(User $user, ReqProfile $reqProfile, $limit = 10, $offset = 0, $profileMatchingStatus = null) {
        $q = $this->em->createQuery();
        $dql = "select count(pm) from ".$this->className." pm
            join pm.specProfile as sp
            join pm.reqProfile  as rp
            join rp.project as p
            where IDENTITY(pm.reqProfile) <> :reqProfileId  AND
                        pm.status in (:profileMatchingStatus) AND
                        sp.status = :specProfileStatus AND 
                  (
                    p.id in (SELECT pp.id
                          FROM ". Member::class." as mm INNER JOIN mm.projectTeam as te INNER JOIN te.project as pp
                          WHERE IDENTITY(mm.user) =:userId AND mm.status = :statusInvited) 
                    OR IDENTITY(p.user)=:userId
                   )
                  and IDENTITY(sp.category) = :categoryId
        ";

        $q->setParameter('userId', $user->getId());
        $q->setParameter('statusInvited', Member::STATUS_CONFIRM);
        $q->setParameter('reqProfileId', $reqProfile->getId());
        $q->setParameter('profileMatchingStatus', $profileMatchingStatus);
        $q->setParameter('specProfileStatus', SpecProfile::STATUS_ACTIVE);
        $q->setParameter('categoryId', $reqProfile->getCategory()->getId());

        $q->setDQL($dql);

        return $q->getSingleScalarResult();
    }

    /**
     * @param Invite $invite
     * @return ProfileMatching|null
     */
    public function getByInvite(Invite $invite) {
        $q = $this->em->createQuery();
        $dql = "select m from ".$this->className." m
            where IDENTITY(m.invite) = :inviteId";
        $q->setParameter('inviteId', $invite->getId());

        $q->setDQL($dql);
        $q->setMaxResults(1);

        return $q->getOneOrNullResult();
    }

    /**
     * @param ReqProfile $reqProfile
     * @param $email
     * @param $inviteScope
     * @return ProfileMatching|null
     */
    public function getByReqProfileAndPendingInvite(ReqProfile $reqProfile, $email, $inviteScope)
    {
        $q = $this->em->createQuery();
        $dql = "select m from ". $this->className." m
            join m.invite as i
            where i.email = :email and i.inviteScope = :inviteScope
            and i.acceptedAt is null and i.rejectedAt is null
            and m.specProfile is null
            and IDENTITY(m.reqProfile) = :reqProfileId
            ";
        $q->setParameter('email', $email);
        $q->setParameter('inviteScope', $inviteScope);
        $q->setParameter('reqProfileId', $reqProfile->getId());

        $q->setDQL($dql);
        $q->setMaxResults(1);

        return $q->getOneOrNullResult();
    }

    /**
     * @param ReqProfile $reqProfile
     * @param User $user
     * @return ProfileMatching|null
     */
    public function getByReqProfileAndUserPendingInvite(ReqProfile $reqProfile, User $user) {
        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata($this->className, 't');
        $paramCount = 0;

        $query = $this->em->createNativeQuery("", $rsm);

        $sql = "select m.* from profile_matchings m 
            inner join pending_process_user_invites ui on ui.invite_id = m.invite_id
            where m.req_profile_id = ? and ui.user_id = ?
            limit 1
        ";

        $paramCount++;
        $query->setParameter($paramCount, $reqProfile->getId(), Type::INTEGER);

        $paramCount++;
        $query->setParameter($paramCount, $user->getId(), Type::INTEGER);

        $query->setSQL($sql);

        return $query->getOneOrNullResult();
    }

    /**
     * @param int $limit
     * @param int $offset
     * @param null $status
     * @param null $searchValue
     * @param array $order
     * @return ProfileMatching[]|ArrayCollection
     */
    public function getAllHasChat($limit = 10, $offset = 0, $status = null,
                                           $searchValue = null,
                                           $order = [[
                                               'column' => 'id',
                                               'dir' => 'desc']])
    {
        $q = $this->em->createQuery();
        $dql = "select m from ".$this->className." m
            join m.reqProfile rp
            join m.chat c
            left join c.projectList pc 
            where 0=0 and pc is null
        ";

        if (!is_null($status)) {
            $dql .= ' and m.status in (:status)';
            $q->setParameter('status', $status);
        }

        if (!is_null($searchValue)) {
            $dql .= " and (
                    rp.name like :searchValue
                 )";
            $q->setParameter('searchValue', '%'.$searchValue.'%');
        }

        $dql .= $this->dqlOrder($order);

        $q->setDQL($dql);
        $q->setMaxResults($limit);
        $q->setFirstResult($offset);

        return $q->getResult();
    }

    public function getAllByFilter(User $user = null, Chat $chat = null, $projectList = [], $categoryList = [], $reqProfileList = [],
                                   $limit = 10, $offset = 0, $inStatus = null,
                                   $order = [['column' => 'id', 'dir' => 'desc']]) {
        $query = $this->em->createQuery();

        $dql = "select m from ".$this->className." m
            left join m.reqProfile rp
            left join rp.category c
            left join m.specProfile sp
            left join sp.user u
            left join u.roles r
            where sp.status = :specProfileStatus ";

        $query->setParameter('specProfileStatus', SpecProfile::STATUS_ACTIVE);

        if(!is_null($user)){
            $dql .= " and IDENTITY(sp.user) = :userId";
            $query->setParameter('userId',$user->getId());
        }

        if($projectList && count($projectList)>0) {
            $projectIds = [];
            foreach ($projectList as $project) {
                $projectIds [] = $project->getId();
            }
            $dql .= " and IDENTITY(rp.project) in (:projectId) ";
            $query->setParameter('projectId', $projectIds);
        }

        if($categoryList && count($categoryList)>0) {
            $categoryIds = [];
            foreach ($categoryList as $category) {
                $categoryIds [] = $category->getId();
            }
            $dql .= " and ( IDENTITY(c.parent) in (:categoryId) or c.id in (:categoryId))";
            $query->setParameter('categoryId', $categoryIds);
        }

        if($reqProfileList && count($reqProfileList)>0) {
            $reqProfileIds = [];
            foreach ($reqProfileList as $reqProfile) {
                $reqProfileIds [] = $reqProfile->getId();
            }
            $dql .= " and IDENTITY(m.reqProfile) in (:reqProfileId) ";
            $query->setParameter('reqProfileId', $reqProfileIds);
        }

        if (!is_null($inStatus)) {
            $dql .= ' and m.status in (:inStatus) ';
            $query->setParameter('inStatus', $inStatus);
        }

        if (!is_null($chat)) {
            $dql .= " and m not in (
                    select pm from ".Chat::class." as ch
                    join ch.profileMatchingList as pm
                    where ch.id = :chatId
                )";
            $query->setParameter('chatId', $chat->getId());
        }

        $columnsMap = [
            'm.matchingPercentage' => ['matchingPercentage'],
            'm.id' => ['id'],
            'm.updatedAt' => ['updatedAt'],
            'u.lastActive' => ['lastActive'],
            "r.priority" => ['priority']
        ];

        $dql .= $this->dqlOrder($order, $columnsMap);

        $query->setDQL($dql);
        $query->setMaxResults($limit)->setFirstResult($offset);

        return $query->getResult();
    }

}