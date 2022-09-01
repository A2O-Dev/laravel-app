<?php

namespace App\Services;


use App\Models\Category;
use App\Models\Chat;
use App\Models\Invite;
use App\Models\Notification;
use App\Models\ProfileMatching;
use App\Models\ReqProfile;
use App\Models\SpecProfile;
use App\Models\Project;
use App\Models\User;
use App\Repositories\ProfileMatchingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Illuminate\Support\Facades\View;


class ProfileMatchingService extends BaseService
{
    /**
     * @var ProfileMatchingRepository
     */
    protected $baseRepository;

    /**
     * @var ChatService
     */
    protected $chatService;

    /**
     * @var NotificationService
     */
    protected $notificationService;

    /**
     * @var InviteService
     */
    protected $inviteService;


    /**
     * CastingTalentService constructor.
     * @param ProfileMatchingRepository $baseRepository
     * @param ChatService $chatService
     * @param NotificationService $notificationService
     * @param InviteService $inviteService
     */
    public function __construct(ProfileMatchingRepository $baseRepository,
                                ChatService $chatService,
                                NotificationService $notificationService,
                                InviteService $inviteService
    )
    {
        parent::__construct($baseRepository);
        $this->chatService = $chatService;
        $this->notificationService = $notificationService;
        $this->inviteService = $inviteService;
    }

    /**
     * @param $reqProfile
     * @param array $data
     * @param User|null $user
     * @param bool $onlyWithoutChat
     * @return ProfileMatching[]|ArrayCollection
     */
    public function getAllByReqProfile($reqProfile, $data = [], User $user = null, $onlyWithoutChat = false)
    {
        $status = null;
        if (array_key_exists('status', $data)) {
            $status = $data['status'];
        }

        $this->requestMapper->populate($data);

        return $this->baseRepository->getAllByReqProfile($reqProfile,
            $user,
            $this->requestMapper->getValue('limit'),
            $this->requestMapper->getValue('offset'),
            $status,
            $this->requestMapper->getValue('order'),
            $onlyWithoutChat);
    }

    /**
     * @param $reqProfile
     * @param array $data
     * @param User|null $specProfilesOwner
     * @param bool $onlyWithoutChat
     * @param User $favoriteAndBlacklistOwner
     * @param boolean $onlyWithOutBlacklistSpecProfiles
     * @return ProfileMatching[]|ArrayCollection
     */
    public function getAllByReqProfileWithFavorites($reqProfile, $data = [],
                                                    User $favoriteAndBlacklistOwner, $onlyWithOutBlacklistSpecProfiles = false,
                                                    User $specProfilesOwner = null, $onlyWithoutChat = false)
    {
        $status = null;
        if (array_key_exists('status', $data)) {
            $status = $data['status'];
        }

        $this->requestMapper->populate($data);

        return $this->baseRepository->getAllByReqProfileWithFavorites($reqProfile,
            $this->requestMapper->getValue('limit'),
            $this->requestMapper->getValue('offset'),
            $status,
            $this->requestMapper->getValue('order'),
            $favoriteAndBlacklistOwner,
            $onlyWithOutBlacklistSpecProfiles,
            $specProfilesOwner,
            $onlyWithoutChat);
    }

    /**
     * @param ReqProfile $reqProfile
     * @param array $data
     * @param boolean $withoutBlacklistSpecProfiles
     * @param User $user
     * @return int
     */
    public function countAllByReqProfile(ReqProfile $reqProfile, $data = [], $withoutBlacklistSpecProfiles = false, User $user = null) {
        $status = null;
        if (array_key_exists('status', $data)) {
            $status = $data['status'];
        }
        return $this->baseRepository->countAllByReqProfile($reqProfile, $status, $withoutBlacklistSpecProfiles, $user);
    }

    /**
     * @param User $owner
     * @param array $data
     * @return int
     */
    public function countAllByReqProfileOwner(User $owner, $data = [] ){
        $status = null;

        if(array_key_exists('status', $data)){
            $status = $data['status'];
        }

        return $this->baseRepository->countAllByReqProfileOwner($owner, $status);
    }

    /**
     * @param SpecProfile $specProfile
     * @param array $data
     * @return int
     */
    public function countAllBySpecProfile(SpecProfile $specProfile, $data = [] ){
        $status = null;

        if(array_key_exists('status', $data)){
            $status = $data['status'];
        }

        return $this->baseRepository->countAllBySpecProfile($specProfile, $status);
    }

    /**
     * @param SpecProfile $specProfile
     * @param array $data
     * @return ProfileMatching[]|ArrayCollection
     */
    public function getAllBySpecProfile(SpecProfile $specProfile, $data = []) {
        $status = null;
        $this->requestMapper->populate($data);

        if (array_key_exists('status', $data)) {
            $status = $data['status'];
        }

        return $this->baseRepository->getAllBySpecProfile($specProfile,
            $this->requestMapper->getValue('limit'),
            $this->requestMapper->getValue('offset'),
            $status,
            $this->requestMapper->getValue('order'));
    }

    /**
     * @param Project $project
     * @param Category|null $category
     * @param array $data
     * @return ProfileMatching[]|ArrayCollection
     */
    public function getAllByProject(Project $project, Category $category = null, $data = []) {
        $this->requestMapper->populate($data);

        $status = null;
        if (array_key_exists('status', $data)) {
            $status = $data['status'];
        }

        return $this->baseRepository->getAllByProject($project,
            $category,
            $this->requestMapper->getValue('limit'),
            $this->requestMapper->getValue('offset'),
            $status,
            $this->requestMapper->getValue('searchValue'),
            $this->requestMapper->getValue('order'));
    }

    /**
     * @param Project $project
     * @param Category|null $category
     * @param array $data
     * @return mixed
     */
    public function countAllByProject(Project $project, Category $category = null, $data = []) {
        $this->requestMapper->populate($data);

        $status = null;
        if (array_key_exists('status', $data)) {
            $status = $data['status'];
        }

        $searchValue = null;
        if (array_key_exists('searchValue', $data)) {
            $searchValue = $data['searchValue'];
        }

        return $this->baseRepository->countAllByProject($project, $category, $status, $searchValue);
    }

    /**
     * @param ProfileMatching $profileMatching
     * @param $data
     * @return ProfileMatching
     */
    public function changeStatus(ProfileMatching $profileMatching, $data) {
        if (!array_key_exists('status', $data)) {
            $this->errors->add('general', 'The status is required for this operation');
        }

        if ($data['status'] !== ProfileMatching::STATUS_PASSED_BY_REQ_PROFILE && is_null($profileMatching->getSpecProfile())) {
            $this->errors->add('general', 'Can not accept or pass an invite without having an Asset Profile for the invite.');
        }

        if (!$this->hasErrors()) {
            $notification = null;
            switch($data['status']) {
                case ProfileMatching::STATUS_SELECTED:
                    $profileMatching->select();
                    $title= $profileMatching->getSpecProfile()->getTitle()
                        ." Accepted Your Casting Invite";
                    $route = "spec-profile-matchings/".$profileMatching->getId();
                    $user = $profileMatching->getReqProfile()->getUser();
                    $description= "Hey ". $profileMatching->getReqProfile()->getUser()->getUserProfile()->getFirstName().", <br> <br>"
                        ."Great news! The Casting Invite you sent to ". $profileMatching->getSpecProfile()->getTitle()
                        ." for your ". $profileMatching->getReqProfile()->getProject()->getName()." project has been accepted."."<br> <br>"
                        ."Head on over to your Casting account to check it out and message them!";

                    $notification = new Notification($user, $title, $route,Notification::TYPE_GENERAL, $description);
                    break;
                case ProfileMatching::STATUS_PASSED_BY_REQ_PROFILE:
                    $profileMatching->passByReqProfile();
                    break;
                case ProfileMatching::STATUS_BOOKED:
                    $profileMatching->book();
                    $title = "Booked the Casting for : "
                        .$profileMatching->getReqProfile()->getProject()->getName();
                    $route = "req-profile-matchings/".$profileMatching->getId();
                    $user = $profileMatching->getSpecProfile()->getUser();
                    $description= "Hey ".$profileMatching->getSpecProfile()->getUser()->getUserProfile()->getFirstName().", <br><br>"
                    ."Congrats! You booked the Casting for ". $profileMatching->getReqProfile()->getName() ." in the "
                    .$profileMatching->getReqProfile()->getProject()->getName()
                    ." project."." <br> <br>"
                    ."Head on over to your Casting account to check it out!";
                    $notification = new Notification($user, $title, $route,Notification::TYPE_GENERAL,$description);
                    break;
                case ProfileMatching::STATUS_INVITED:
                    $profileMatching->invite();
                    $title = "New Casting Invite";

                    $description = "Hey ". $profileMatching->getSpecProfile()->getUser()->getUserProfile()->getFirstName() .", <br> <br>".
                        "Good news! ". $profileMatching->getReqProfile()->getUser()->getUserProfile()->getFirstName()
                        ." has invited you to look at the Casting - ".$profileMatching->getReqProfile()->getName() ." - for the " .$profileMatching->getReqProfile()->getProject()->getName()
                        ." project.". "<br><br>"
                        ."Review this Invite and either accept it or pass on it. " .$profileMatching->getReqProfile()->getUser()->getUserProfile()->getFirstName()
                        ." will be notified of your pass or acceptance."."<br> <br>"
                        ."If you accept, ". $profileMatching->getReqProfile()->getUser()->getUserProfile()->getFirstName()
                        ." will be able to start a Casting Chat with you to share more details. Remember that ". $profileMatching->getReqProfile()->getUser()->getUserProfile()->getFirstName()
                        ." can only start a Casting Chat with you once you accept the invite.". "<br> <br>".

                        "Head on over to your Casting account to check it out!";

                    $route = "req-profile-matchings/".$profileMatching->getId();
                    $user = $profileMatching->getSpecProfile()->getUser();
                    $notification = new Notification($user, $title, $route, Notification::TYPE_GENERAL, $description);
                    break;
                case ProfileMatching::STATUS_CALLBACK:
                    $profileMatching->callback();
                    break;
                case ProfileMatching::STATUS_PASSED_BY_SPEC_PROFILE:
                    $profileMatching->passBySpecProfile();
                    break;
                case ProfileMatching::STATUS_RESTORE:
                    $profileMatching->restoreStatus();
                    break;
                default:
                    $this->errors->add('general', 'The status requested is not valid');
            }
            if (!$this->hasErrors()) {
                $this->baseRepository->save($profileMatching);
                if (!is_null($notification)) {
                    $this->baseRepository->save($notification);
                    $notification->notify();
                }

            }
        }
        return $profileMatching;
    }

    /**
     * @param ReqProfile $reqProfile
     * @param SpecProfile $specProfile
     * @return ProfileMatching|null
     */
    public function getByReqProfileAndSpecProfile(ReqProfile $reqProfile, SpecProfile $specProfile){
        return $this->baseRepository->getByReqProfileAndSpecProfile($reqProfile, $specProfile);
    }

    /**
     * @param $status
     * @param array $data
     * @return ProfileMatching[]|ArrayCollection
     */
    public function getAllByStatus($status, $data = []) {
        $this->requestMapper->populate($data);
        return $this->baseRepository->getAllByStatus($status,
            $this->requestMapper->getValue('limit'),
            $this->requestMapper->getValue('offset')
        );
    }

    public function resendAllInvited() {
        $now = time();
        $limit = 100;
        $offset = 0;
        $profileMatchingList = $this->getAllByStatus(ProfileMatching::STATUS_INVITED, [
            'limit' => $limit,
            'offset' => $offset
        ]);
        while(count($profileMatchingList) > 0) {
            $notificationList = [];
            foreach ($profileMatchingList as $profileMatching) {
                $updatedTime = $profileMatching->getUpdatedAt()->getTimestamp();

                if ((($now - $updatedTime) / 60) >= config('services.reminders.inviteTalent.minTime')) {

                    if ($profileMatching->getReminderCount() < config('services.reminders.inviteTalent.maxReminders')) {
                        $title = "Casting Invite Reminder for ". $profileMatching->getReqProfile()->getProject()->getName();

                        $view = View::make('emails.invitation_resend', ['profileMatching' => $profileMatching, 'reminderCount' => $profileMatching->getReminderCount(),]);
                        $description = $view->render();
                        $route = "matched-castings/" . $profileMatching->getId();
                        $user = $profileMatching->getSpecProfile()->getUser();
                        $notification = new Notification($user, $title, $route, Notification::TYPE_GENERAL, $description);
                        $notification->notify();
                        $notificationList[] = $notification;
                        $profileMatching->addReminder();
                    }
                }
            }
            $this->baseRepository->saveList($notificationList);
            $this->baseRepository->saveList($profileMatchingList);

            $offset += $limit;
            $profileMatchingList = $this->getAllByStatus(ProfileMatching::STATUS_INVITED, [
                'limit' => $limit,
                'offset' => $offset
            ]);
        }
    }

    public function countAllSpecProfileByUser(User $user, $data = [] ){
        $status = null;

        if(array_key_exists('status', $data)){
            $status = $data['status'];
        }

        return $this->baseRepository->countAllSpecProfileByUser($user, $status);
    }

    public function changeAuditionTape(ProfileMatching $profileMatching, array $data){

        if(array_key_exists('auditionTapeLink', $data)) {
            $profileMatching->setAuditionTapeLink($data['auditionTapeLink']);
            $this->baseRepository->save($profileMatching);
        }

        return $profileMatching;

    }

    /**
     * @param User $user
     * @param ReqProfile $reqProfile
     * @param array $data
     * @return ProfileMatching[]
     */
    public function getAllPreviousByReqProfileUser(User $user, ReqProfile $reqProfile, $data = [])
    {
        $status = null;
        if (array_key_exists('status', $data)) {
            $status = $data['status'];
        }

        $this->requestMapper->populate($data);

        return $this->baseRepository->getAllPreviousByReqProfileUser($user, $reqProfile,
            $this->requestMapper->getValue('limit'),
            $this->requestMapper->getValue('offset'),
            $status,
            $this->requestMapper->getValue('order'));
    }

    /**
     * @param User $user
     * @param ReqProfile $reqProfile
     * @param array $data
     * @return int
     */
    public function countAllPreviousByReqProfileUser(User $user, ReqProfile $reqProfile, $data = [])
    {
        $status = null;
        if (array_key_exists('status', $data)) {
            $status = $data['status'];
        }

        $this->requestMapper->populate($data);

        return $this->baseRepository->countAllPreviousByReqProfileUser($user, $reqProfile,
            $this->requestMapper->getValue('limit'),
            $this->requestMapper->getValue('offset'),
            $status);
    }

    /**
     * @param Invite $invite
     * @return ProfileMatching|null
     */
    public function getByInvite(Invite $invite) {
        return $this->baseRepository->getByInvite($invite);
    }

    /**
     * @param ReqProfile $reqProfile
     * @param $inviteEmail
     * @param $inviteScope
     * @return ProfileMatching|null
     */
    public function getByReqProfileAndPendingInvite(ReqProfile $reqProfile, $inviteEmail, $inviteScope)
    {
        return $this->baseRepository->getByReqProfileAndPendingInvite($reqProfile, $inviteEmail, $inviteScope);
    }

    /**
     * @param SpecProfile $specProfile
     */
    public function completeAcceptedInvite(SpecProfile $specProfile)
    {
        $pendingProcessInviteList = $specProfile->getUser()->getPendingProcessInviteList();
        $user = $specProfile->getUser();
        foreach($pendingProcessInviteList as $pendingProcessInvite) {
            if (!is_null($pendingProcessInvite->getAcceptedAt())) {
                $profileMatching = $this->getByInvite($pendingProcessInvite);
                if (!is_null($profileMatching)) {
                    if ($this->updatePendingSpecProfileProfileMatching($profileMatching, $specProfile) === true) {
                        $this->changeStatus($profileMatching, ['status' => ProfileMatching::STATUS_SELECTED]);
                        $user->getPendingProcessInviteList()->removeElement($pendingProcessInvite);
                    }
                }
            }
        }
        if (!$this->hasErrors()) {
            $this->baseRepository->save($user);
        }
    }

    /**
     * This method update a Profile Matching without SpecProfile
     * When the user already have a specProfile
     * @param ProfileMatching $profileMatching
     * @param SpecProfile $specProfile
     * @return bool
     */
    public function updatePendingSpecProfileProfileMatching(ProfileMatching $profileMatching, SpecProfile $specProfile) {
        $updated = false;
        //TODO: validate no other profile matching with the same ReqProfile and SpecProfile in database.
        if (is_null($profileMatching->getSpecProfile())
            && $profileMatching->getReqProfile()->getCategory()->getId() == $specProfile->getCategory()->getId()) {
            $profileMatching->setSpecProfile($specProfile);
            $this->baseRepository->save($profileMatching);
            $updated = true;
        }
        return $updated;
    }

    /**
     * Create a new profile matching used in an external submission like the invite process
     * @param ReqProfile $reqProfile
     * @param SpecProfile $specProfile
     * @return ProfileMatching
     */
    public function createAndSetUnderReview(ReqProfile $reqProfile, SpecProfile $specProfile)
    {
        $profileMatching = new ProfileMatching($reqProfile, $specProfile);
        $profileMatching->setSelfSubmitted(true);
        $profileMatching->invite();
        $profileMatching->select();
        $this->baseRepository->save($profileMatching);

        return $profileMatching;
    }

    /**
     * @param ReqProfile $reqProfile
     * @param User $user
     * @return ProfileMatching|null
     */
    public function getByReqProfileAndUserPendingInvite(ReqProfile $reqProfile, User $user) {
        return $this->baseRepository->getByReqProfileAndUserPendingInvite($reqProfile, $user);
    }

    /**
     * @param array $data
     * @return ProfileMatching[]|ArrayCollection
     */
    public function getAllHasChat($data = []) {
        $this->requestMapper->populate($data);

        $status = null;
        if (array_key_exists('status', $data)) {
            $status = $data['status'];
        }

        return $this->baseRepository->getAllHasChat(
            $this->requestMapper->getValue('limit'),
            $this->requestMapper->getValue('offset'),
            $status,
            $this->requestMapper->getValue('searchValue'),
            $this->requestMapper->getValue('order'));
    }

    /**
     * @param User|null $user
     * @param Chat|null $chat
     * @param array $projectList
     * @param array $categoryList
     * @param array $reqProfileList
     * @param array $data
     * @return ProfileMatching[]|ArrayCollection
     * @internal param $reqProfile
     * @internal param bool $onlyWithoutChat
     */
    public function getAllByFilter(User $user = null, Chat $chat = null,$projectList=[], $categoryList=[], $reqProfileList=[], $data = [])
    {
        $status = null;
        if (array_key_exists('status', $data)) {
            $status = $data['status'];
        }

        $this->requestMapper->populate($data);

        return $this->baseRepository->getAllByFilter($user, $chat,
            $projectList,$categoryList,$reqProfileList,
            $this->requestMapper->getValue('limit'),
            $this->requestMapper->getValue('offset'),
            $status,
            $this->requestMapper->getValue('order'));
    }

    public function extractFromProfileMatchingResults($profileMatchingMixedResultsList)
    {
        $profileMatchingList = [];

        foreach ($profileMatchingMixedResultsList as $profileMatchingMixedResult) {
            $profileMatchingList[] = $profileMatchingMixedResult[0];
        }
        return $profileMatchingList;
    }

    /**
     * Add an isFavorite entry to the specProfile of a profileMatching
     * according a list of couples specProfile - isFavorite
     * @param $profileMatchingList
     * @param $specProfilesList
     * @return array
     */
    public function markSpecProfilesAsFavoritesFromSpecProfileResults($profileMatchingList, $specProfilesList)
    {
        /**
         * @var SpecProfile $specProfile[0]
         */
        foreach ($profileMatchingList as $pos => $profileMatching) {
            foreach ($specProfilesList as $specProfile) {
                if ($profileMatching['specProfile']['id'] === $specProfile[0]->getId()) {
                    $profileMatchingList[$pos]['specProfile']['isFavorite'] = boolval($specProfile['is_favorite']);
                }
            }
        }

        return $profileMatchingList;
    }

    /**
     * Add an isFavorite entry to the specProfile of a profileMatching
     * according a list of couples profileMatching - isFavorite
     * @param $profileMatchingList
     * @param $profileMatchingMixedResultList
     * @return array
     */
    public function markSpecProfilesAsFavoritesFromProfileMatchingResults($profileMatchingList, $profileMatchingMixedResultList)
    {

        foreach ($profileMatchingList as $pos => $profileMatching) {
            /**
             * @var ProfileMatching $profileMatchingMixedResult[0]
             */
            foreach ($profileMatchingMixedResultList as $profileMatchingMixedResult) {
                if ($profileMatching['specProfile']['id'] === $profileMatchingMixedResult[0]->getSpecProfile()->getId()) {
                    $profileMatchingList[$pos]['specProfile']['isFavorite'] = boolval($profileMatchingMixedResult['is_favorite']);
                }
            }
        }

        return $profileMatchingList;
    }

}