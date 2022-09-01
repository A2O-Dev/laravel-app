<?php
/**
 * Created by PhpStorm.
 * User: Ariel
 * Date: 11/28/2017
 * Time: 11:45 AM
 */

namespace App\Services;


use App\Helpers\ModelHelper;
use App\Models\CastingDirector;
use App\Models\Chat;
use App\Models\Notification;
use App\Models\CastingTalent;
use App\Models\Casting;
use App\Models\Talent;
use App\Repositories\CastingTalentRepository;
use App\Repositories\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class CastingTalentService extends BaseService
{
    /**
     * @var CastingTalentRepository
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
     * CastingTalentService constructor.
     * @param CastingTalentRepository $baseRepository
     * @param ChatService $chatService
     * @param NotificationService $notificationService
     */
    public function __construct(CastingTalentRepository $baseRepository,
                                ChatService $chatService,
                                NotificationService $notificationService
    )
    {
        parent::__construct($baseRepository);
        $this->chatService = $chatService;
        $this->notificationService = $notificationService;
    }

    /**
     * @param Casting $casting
     * @param array $data
     * @return CastingTalent[]|ArrayCollection
     */
    public function getAllByCasting(Casting $casting, $data = [])
    {
        $status = null;
        if (array_key_exists('status', $data)) {
            $status = $data['status'];
        }
        $this->requestMapper->populate($data);

        return $this->baseRepository->getAllByCasting($casting,
            $this->requestMapper->getValue('limit'),
            $this->requestMapper->getValue('offset'),
            $status,
            $this->requestMapper->getValue('order'));
    }

    public function countAllByCasting(Casting $casting, $data = []) {
        $status = null;
        if (array_key_exists('status', $data)) {
            $status = $data['status'];
        }
        return $this->baseRepository->countAllByCasting($casting, $status);
    }

    public function countAllByCastingDirector(CastingDirector $castingDirector, $data = [] ){
        $status = null;

        if(array_key_exists('status', $data)){
            $status = $data['status'];
        }

        return $this->baseRepository->countAllByCastingDirector($castingDirector, $status);
    }

    public function countAllByTalent(Talent $talent, $data = [] ){
        $status = null;

        if(array_key_exists('status', $data)){
            $status = $data['status'];
        }

        return $this->baseRepository->countAllByTalent($talent, $status);
    }

    public function getAllByTalent(Talent $talent, $data = []) {
        $status = null;
        $this->requestMapper->populate($data);

        if (array_key_exists('status', $data)) {
            $status = $data['status'];
        }

        $castingTalentList = $this->baseRepository->getAllByTalent($talent,
            $this->requestMapper->getValue('limit'),
            $this->requestMapper->getValue('offset'),
            $status,
            $this->requestMapper->getValue('order'));

        return $castingTalentList;
    }

    public function deleteAllPendingByCasting(Casting $casting) {
        $this->baseRepository->deleteAllPendingByCasting($casting);
    }

    public function deleteAllPendingByTalent(Talent $talent) {
        $this->baseRepository->deleteAllPendingByTalent($talent);
    }

    public function findMatches(Casting $casting, $talentList) {
        $castingTalentList = [];
        foreach($talentList as $talent) {
            $castingTalent = CastingTalent::matchTalent($casting, $talent);
            if (!is_null($castingTalent)) {
                $this->baseRepository->save($castingTalent);
                if ($castingTalent->getStatus() == CastingTalent::STATUS_MATCHED) {
                    $castingTalentList[] = $castingTalent;
                }
            }
        }

        return $castingTalentList;
    }

    public function changeStatus(CastingTalent $castingTalent, $data) {
        if (array_key_exists('status', $data)) {
            $notification = null;
            switch($data['status']) {
                case CastingTalent::STATUS_SELECTED:
                    $castingTalent->select();
                    $title = "Talent ".$castingTalent->getTalent()->getFirstName()
                        ." ".$castingTalent->getTalent()->getLastName()
                        ." has accepted invitation to Casting: "
                        . $castingTalent->getCasting()->getProject()->getName() . " - "
                        .$castingTalent->getCasting()->getName();
                    $route = "castings/".$castingTalent->getCasting()->getId();
                    $user = $castingTalent->getCasting()->getCastingDirector()->getUser();
                    $notification = new Notification($user, $title, $route);
                    break;
                case CastingTalent::STATUS_PASSED_BY_DIRECTOR:
                    $castingTalent->pass_by_director();
                    break;
                case CastingTalent::STATUS_BOOKED:
                    $castingTalent->book();
                    $title = "You have been booked to Casting: "
                        . $castingTalent->getCasting()->getProject()->getName() . " - "
                        . $castingTalent->getCasting()->getName();
                    $route = "talent-castings/".$castingTalent->getId();
                    $user = $castingTalent->getTalent()->getUser();
                    $notification = new Notification($user, $title, $route);
                    break;
                case CastingTalent::STATUS_INVITED:
                    $castingTalent->invite();
                    $title = "ASSET NAME)" ."Accepted Your Casting Invite:"
                        . $castingTalent->getCasting()->getProject()->getName() . " - "
                        . $castingTalent->getCasting()->getName();

                    $description =  $castingTalent->getCasting()->getCastingDirector()->getFirstName().' '.
                        $castingTalent->getCasting()->getCastingDirector()->getLastName().
                        " has invited you to the Casting: ".$castingTalent->getCasting()->getProject()->getName().
                        ' - '.$castingTalent->getCasting()->getName().' '.
                        "<br><br>".
                        'Review this Casting Invite and either accept it or pass on it. '.
                        $castingTalent->getCasting()->getCastingDirector()->getFirstName().
                        ' will be notified of your pass or acceptance. If you accept, '.$castingTalent->getCasting()->getCastingDirector()->getFirstName()
                        . ' will be able to start a Casting Chat with you to share more details.<br><br> 
                        Remember that '.$castingTalent->getCasting()->getCastingDirector()->getFirstName().' can only start a Casting Chat with you once you accept the invite.'.
                        "";
                    $route = "talent-castings/".$castingTalent->getId();
                    $user = $castingTalent->getTalent()->getUser();
                    $notification = new Notification($user, $title, $route, Notification::TYPE_GENERAL, $description);
                    break;
                case CastingTalent::STATUS_CALLBACK:
                    $castingTalent->callback();
                    break;
                case CastingTalent::STATUS_PASSED_BY_TALENT:
                    $castingTalent->pass_by_talent();
                    break;
                case CastingTalent::STATUS_RESTORE:
                    $castingTalent->statusRestore();
                    break;
                default:
                    $this->errors->add('general', 'The status requested is not valid');
            }
            if (!$this->hasErrors()) {
                $this->baseRepository->save($castingTalent);
                if (!is_null($notification)) {
                    $this->baseRepository->save($notification);
                    $notification->notify();
                }

                //Archive Chat
                if( $castingTalent->getStatus() === CastingTalent::STATUS_PASSED_BY_TALENT ||
                    $castingTalent->getStatus() === CastingTalent::STATUS_PASSED_BY_DIRECTOR){

                    $chat = $this->chatService->getByUsers(
                        $castingTalent->getCasting(),
                        $castingTalent->getTalent()->getUser(),
                        $castingTalent->getCasting()->getCastingDirector()->getUser()
                    );

                    if(!is_null($chat)){
                        $this->chatService->archive($chat, [ 'archiveType' => Chat::ARCHIVE_TYPE_CASTING_TALENT ]);
                    }
                }

                //Restore Chat
                if( $castingTalent->getStatus() !== CastingTalent::STATUS_PASSED_BY_TALENT &&
                    $castingTalent->getStatus() !== CastingTalent::STATUS_PASSED_BY_DIRECTOR){

                    $chat = $this->chatService->getByUsers(
                        $castingTalent->getCasting(),
                        $castingTalent->getTalent()->getUser(),
                        $castingTalent->getCasting()->getCastingDirector()->getUser()
                    );

                    if(!is_null($chat)){
                        $this->chatService->restore($chat);
                    }
                }

            }
        } else {
            $this->errors->add('general', 'The status is required for this operation');
        }
        return $castingTalent;
    }

    public function createChat(CastingTalent $castingTalent ){

        $casting = $castingTalent->getCasting();

        $chat = null;
        if(in_array($castingTalent->getStatus(), [
            CastingTalent::STATUS_SELECTED,
            CastingTalent::STATUS_BOOKED,
            CastingTalent::STATUS_CALLBACK]))
        {
            $userTalent = $castingTalent->getTalent()->getUser();
            $userDirector = $castingTalent->getCasting()->getCastingDirector()->getUser();
            $data['title'] = $castingTalent->getCasting()->getProject()->getName().' - ' . $castingTalent->getCasting()->getName();

            $chat = $this->chatService->create($data['title'],[$userTalent,$userDirector],$casting->getProject(),[],[]);

        }else{
            $this->errors->add('Casting',"You can't start a chat from status: ". $castingTalent->getStatus());
        }
        
        return $chat;

    }

    public function getByCastingAndTalent(Casting $casting, Talent $talent){
        return $this->baseRepository->getByCastingAndTalent($casting, $talent);
    }

    /**
     * @param $status
     * @param array $data
     * @return CastingTalent[]
     */
    public function getAllByStatus($status, $data = []) {
        $this->requestMapper->populate($data);
        return $this->baseRepository->getAllByStatus($status,
            $this->requestMapper->getValue('limit'),
            $this->requestMapper->getValue('offset')
        );
    }

    public function resendAllInvited() {
        if (filter_var(config('queue.crons.debug'), FILTER_VALIDATE_BOOLEAN) === true) {
            Log::info('queue.crons.debug entra');
            /**
             * @var UserRepository $userRepository
             */
            $userRepository = app(UserRepository::class);
            $user = $userRepository->getByEmail(config('queue.crons.debugEmail'));
            if (is_null($user)) {
                Log::info('User not Found');
                echo "The user could not be find\n";
            } else {
                Log::info('User Found');
                $this->notificationService->sendTest($user);
            }
            exit;
        }

        $now = time();
        $limit = 50;
        $offset = 0;
        $castingTalentList = $this->getAllByStatus(CastingTalent::STATUS_INVITED, [
            'limit' => $limit,
            'offset' => $offset
        ]);
        while(count($castingTalentList) > 0) {
            $notificationList = [];
            foreach ($castingTalentList as $castingTalent) {
                $updatedTime = $castingTalent->getUpdatedAt()->getTimestamp();

                if ((($now - $updatedTime) / 60) >= config('services.reminders.inviteTalent.minTime')) {

                    if ($castingTalent->getReminderCount() < config('services.reminders.inviteTalent.maxReminders')) {
                        $title = "You have a pending invitation to " . $castingTalent->getCasting()->getFullName();

                        $view = View::make('emails.invitation_resend', ['castingTalent' => $castingTalent, 'reminderCount' => $castingTalent->getReminderCount(),]);
                        $description = $view->render();
                        $route = "talent-castings/" . $castingTalent->getId();
                        $user = $castingTalent->getTalent()->getUser();
                        $notification = new Notification($user, $title, $route, Notification::TYPE_GENERAL, $description);
                        $notification->notify();
                        $notificationList[] = $notification;
                        $castingTalent->addReminder();
                    } else {
                        $castingTalent->pass_by_director();
                    }
                }
            }
            $this->baseRepository->saveList($notificationList);
            $this->baseRepository->saveList($castingTalentList);

            $offset += $limit;
            $castingTalentList = $this->getAllByStatus(CastingTalent::STATUS_INVITED, [
                'limit' => $limit,
                'offset' => $offset
            ]);
        }
    }
}