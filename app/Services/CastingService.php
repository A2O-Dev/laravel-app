<?php
/**
 * Created by PhpStorm.
 * User: Ariel
 * Date: 5/27/2017
 * Time: 2:41 PM
 */

namespace App\Services;


use App\Helpers\StorageFile;
use App\Models\CastingDirector;
use App\Models\Chat;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Role;
use App\Models\Casting;
use App\Models\CastingTalent;
use App\Models\CastingType;
use App\Models\CastingAttributeValue;
use App\Models\User;
use App\Repositories\CastingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CastingService extends BaseService
{
    /**
     * @var CastingRepository
     */
    protected $baseRepository;

    /**
     * @var CastingDirectorService
     */
    protected $castingDirectorService;

    /**
     * @var ProjectService
     */
    protected $projectService;

    /**
     * @var CategoryService
     */
    protected $categoryService;

    /**
     * @var TalentService
     */
    protected $talentService;

    /**
     * @var CastingTalentService
     */
    protected $castingTalentService;

    protected $attributeRequirementService;

    /**
     * @var LocationService
     */
    protected $locationService;

    /**
     * @var NotificationService
     */
    protected $notificationService;

    /**
     * @var ChatService
     */
    protected $chatService;

    /**
     * CastingService constructor.
     * @param CastingRepository $baseRepository
     * @param CategoryService $categoryService
     * @param TalentService $talentService
     * @param CastingTalentService $castingTalentService
     * @param AttributeRequirementService $attributeRequirementService
     * @param LocationService $locationService
     * @param NotificationService $notificationService
     * @param CastingDirectorService $castingDirectorService
     */
    public function __construct(CastingRepository $baseRepository,
                                CategoryService $categoryService,
                                TalentService $talentService,
                                CastingTalentService $castingTalentService,
                                AttributeRequirementService $attributeRequirementService,
                                LocationService $locationService,
                                NotificationService $notificationService,
                                CastingDirectorService $castingDirectorService,
                                ChatService $chatService
    )
    {
        parent::__construct($baseRepository);
        $this->categoryService = $categoryService;
        $this->talentService = $talentService;
        $this->castingTalentService = $castingTalentService;
        $this->attributeRequirementService = $attributeRequirementService;
        $this->locationService = $locationService;
        $this->notificationService = $notificationService;
        $this->castingDirectorService = $castingDirectorService;
        $this->chatService = $chatService;
    }

    public function getAllByCastingDirector($data = [], CastingDirector $castingDirector )
    {
        $castingList = [];
        $this->requestMapper->populate($data);
        $status = null;

        if (array_key_exists('status', $data)) {
            $status = $data['status'];
        }

        if(!is_null($status)){
            $castingList = $this->baseRepository->getAllByCastingDirector($castingDirector,
                $status,
                $this->requestMapper->getValue('limit'),
                $this->requestMapper->getValue('offset'),
                $this->requestMapper->getValue('order'));
        }else{
            $castingList = parent::getAll($data);
        }

        return $castingList;
    }

    /**
     * @param Project $project
     * @param CastingDirector $castingDirector
     * @param CastingType $castingType
     * @param array $castingAttributeValues
     * @param array $data
     * @return Casting|null
     */
    public function create(Project $project, CastingDirector $castingDirector, CastingType $castingType, array $castingAttributeValues,  array $data) {
        /**
         * @var Validator $validator
         */
        $validationRules = [
            'name' => 'required',
            'startDate' => 'nullable|date',
            'endDate' => 'nullable|date',
            'requiredTalentsCount' => 'nullable|integer',
            'searchRadius' => 'nullable|integer'
        ];

        /**
         * @var \Illuminate\Contracts\Validation\Validator $validator
         */
        $validator = Validator::make($data, $validationRules);

        if ($validator->fails()) {
            $this->errors->merge($validator->errors());
        }

        $personCategory = $this->categoryService->getByCode('person');
        if (is_null($personCategory)) {
            $this->errors->add('general', 'The category could not be found, please contact support');
        }

        $casting = null;
        if (!$this->hasErrors()) {
            $casting = new Casting($personCategory, $castingDirector, $project, $castingType, $castingAttributeValues, $data['name']);
            $this->baseRepository->save($casting);

            if (array_key_exists('requirementSheet', $data)
                && array_key_exists('attrRequirementList', $data['requirementSheet'])) {
                $attributeData = ['attributeList' => $data['requirementSheet']['attrRequirementList']];
                $this->attributeRequirementService->saveAll($casting->getRequirementSheet(), $attributeData);
            }

            if(array_key_exists('image', $data)) {
                if (is_null($casting->getImage()) || $data['image']['fileName'] !== $casting->getImage()->getFileName()) {
                    $newImage = StorageFile::createFromArray($data['image']);
                    if (!is_null($newImage)) {
                        $newImage->move('uploads/castings/casting-'.$casting->getId().'/'.$newImage->getFileName());
                        $casting->setImage($newImage);
                    }
                }
            }

            if(array_key_exists('description',$data)){
                $casting->setDescription($data['description']);
            }

            if(array_key_exists('paymentType',$data)){
                $casting->setPaymentType($data['paymentType']);
            }

            if(array_key_exists('location',$data)){
                $location = $this->locationService->create($data['location']);
                if (!is_null($location)) {
                    $casting->setLocation($location);
                    $casting->setLocationName($location->getName());
                }
            }

            if(array_key_exists('startDate',$data)){
                if (empty($data['startDate'])) {
                    $casting->setStartDate(null);
                } else {
                    $casting->setStartDate(new \DateTime($data['startDate']));
                }
            }

            if(array_key_exists('endDate',$data)){
                if (empty($data['endDate'])) {
                    $casting->setEndDate(null);
                } else {
                    $casting->setEndDate(new \DateTime($data['endDate']));
                }
            }

            if (array_key_exists('requiredTalentsCount', $data)) {
                $casting->setRequiredTalentsCount($data['requiredTalentsCount']);
            }

            if (array_key_exists('searchRadius', $data)) {
                $casting->setSearchRadius($data['searchRadius']);
            }

            if (array_key_exists('roleGender', $data)) {
                if (empty($data['roleGender'])) {
                    $casting->setRoleGender(null);
                } else {
                    $casting->setRoleGender($data['roleGender']);
                }
            }

            if (array_key_exists('roleAge', $data)) {
                if (empty($data['roleAge'])) {
                    $casting->setRoleAge(null);
                } else {
                    $casting->setRoleAge($data['roleAge']);
                }
            }

            $this->baseRepository->save($casting);
            if ($casting->getStatus() == Casting::STATUS_ACTIVE) {
                $this->castingTalentService->deleteAllPendingByCasting($casting);
                $talentList = $this->talentService->getAllAvailableByCasting($casting, ['limit' => 100]);
                $this->castingTalentService->findMatches($casting, $talentList);
            }
        }

        return $casting;
    }

    public function update(Casting $casting, array $data, CastingType $castingType = null, Project $project = null, array $castingAttributeValues) {
        $validatorRules = [
            'startDate' => 'nullable|date',
            'endDate' => 'nullable|date',
            'requiredTalentsCount' => 'nullable|integer',
            'searchRadius' => 'nullable|integer'
        ];

        if (array_key_exists('name', $data)) {
            $validatorRules['name'] = 'required';
            $messages['name.required'] = 'The name field is required.';
            $casting->setName($data['name']);
        }

        if (array_key_exists('status', $data)) {
            $validatorRules['status'] = 'required';
            $messages['status.required'] = 'The status field is required.';
            $casting->setStatus($data['status']);
        }

        if (!is_null($castingType)) {
            $casting->setType($castingType);
        }

        if (!is_null($project)) {
            $casting->setProject($project);
        }

        /**
         * @var \Illuminate\Contracts\Validation\Validator $validator
         */
        $validator = Validator::make($data, $validatorRules);

        if($validator->fails()) {
            $this->errors->merge($validator->errors());
        }

        if(!$this->hasErrors()) {
            if (array_key_exists('requirementSheet', $data)
                && array_key_exists('attrRequirementList', $data['requirementSheet'])) {
                $attributeData = ['attributeList' => $data['requirementSheet']['attrRequirementList']];
                $this->attributeRequirementService->saveAll($casting->getRequirementSheet(), $attributeData);
            }

            if(array_key_exists('image', $data)) {
                if (is_null($casting->getImage()) || $data['image']['fileName'] !== $casting->getImage()->getFileName()) {
                    $newImage = StorageFile::createFromArray($data['image']);
                    if (!is_null($newImage)) {
                        $newImage->move('uploads/castings/casting-'.$casting->getId().'/'.$newImage->getFileName());
                        $casting->setImage($newImage);
                    }
                }
            }

            if(array_key_exists('description',$data)) {
                $casting->setDescription($data['description']);
            }

            if(count($castingAttributeValues) > 0 ){
                $casting->getCastingAttributeValues()->clear();
                $casting->setCastingAttributeValue($castingAttributeValues);
            }

            if(array_key_exists('paymentType',$data)){
                $casting->setPaymentType($data['paymentType']);
            }

            if (array_key_exists('location', $data)) {
                if (is_null($casting->getLocation())) {
                    $location = $this->locationService->create($data['location']);
                    if (!is_null($location)) {
                        $casting->setLocation($location);
                        $casting->setLocationName($location->getName());
                    }
                } else {
                    $location = $this->locationService->update($casting->getLocation(), $data['location']);
                    if (!is_null($location)) {
                        $casting->setLocationName($location->getName());
                    }
                }

            }

            if(array_key_exists('status',$data)) {

                if($data['status'] === Casting::STATUS_ARCHIVED){
                    $casting->setStatus(Casting::STATUS_ARCHIVED);

                    //Archive Chats
                    $chatList = $this->chatService->getAllByCasting($casting);
                    $this->chatService->archiveList($chatList, ['archiveType' => Chat::ARCHIVE_TYPE_CASTING ]);
                }

                if($data['status'] === Casting::STATUS_ACTIVE) {
                    $casting->setStatus(Casting::STATUS_ACTIVE);

                    $chatList = $this->chatService->getAllByCasting($casting);
                    $finalChatList = [];

                    //Restore Chats
                    foreach ($chatList as $chat) {
                        if ($chat->getArchiveType() == Chat::ARCHIVE_TYPE_CASTING ) {
                            $finalChatList[] = $chat;
                        }
                    }
                    $this->chatService->restoreList($finalChatList);
                }
            }

            if(array_key_exists('startDate',$data)) {
                if (empty($data['startDate'])) {
                    $casting->setStartDate(null);
                } else {
                    $casting->setStartDate(new \DateTime($data['startDate']));
                }
            }
            if(array_key_exists('endDate',$data)) {
                if (empty($data['endDate'])) {
                    $casting->setEndDate(null);
                } else {
                    $casting->setEndDate(new \DateTime($data['endDate']));
                }
            }

            if (array_key_exists('requiredTalentsCount', $data)) {
                $casting->setRequiredTalentsCount($data['requiredTalentsCount']);
            }

            if (array_key_exists('searchRadius', $data)) {
                $casting->setSearchRadius($data['searchRadius']);
            }

            if (array_key_exists('roleGender', $data)) {
                if (empty($data['roleGender'])) {
                    $casting->setRoleGender(null);
                } else {
                    $casting->setRoleGender($data['roleGender']);
                }
            }

            if (array_key_exists('roleAge', $data)) {
                if (empty($data['roleAge'])) {
                    $casting->setRoleAge(null);
                } else {
                    $casting->setRoleAge($data['roleAge']);
                }
            }

            $this->baseRepository->save($casting);
            if ($casting->getStatus() == Casting::STATUS_ACTIVE) {
                $this->castingTalentService->deleteAllPendingByCasting($casting);
                $talentList = $this->talentService->getAllAvailableByCasting($casting, ['limit' => 100]);
                $this->castingTalentService->findMatches($casting, $talentList);
            }
        }

        return $casting;
    }

    public function delete(Casting $casting) {
        $this->baseRepository->delete($casting);
    }

    public function getAllHaveChatsByUser(User $user) {
        return $this->baseRepository->getAllHaveChatsByUser($user);
    }

    public function getAllHaveChatsByUserWithTotals(User $user , $data = []) {
        $this->requestMapper->addMasterKey('messagesStatus', null);
        $this->requestMapper->populate($data);
        return $this->baseRepository->getAllHaveChatsByUserWithTotals($user,
            $this->requestMapper->getValue('messagesStatus'));
    }

    public function matchAllCastings() {
        $limit = 10;
        $offset = 0;
        $castingList = $this->baseRepository->getAllByStatus(Casting::STATUS_ACTIVE, $limit, $offset);
        while(count($castingList) > 0) {
            foreach($castingList as $casting) {
                $talentList = $this->talentService->getAllAvailableByCasting($casting, ['limit' => 20]);
                $this->castingTalentService->findMatches($casting, $talentList);
            }
            $offset += $limit;

            $castingList = $this->baseRepository->getAllByStatus(Casting::STATUS_ACTIVE, $limit, $offset);
        }
    }

    public function countByCastingDirector(CastingDirector $castingDirector){

        return $this->baseRepository->countByCastingDirector($castingDirector);
    }

    public function countByCastingDirectorAndStatus($data = [], CastingDirector $castingDirector){

        $status = null;
        if (array_key_exists('status', $data)) {
            $status = $data['status'];
        }

        if(!is_null($status)){
            $totalCountCasting = $this->baseRepository->countByCastingDirectorAndStatus($castingDirector, $status);
        }else{
            $totalCountCasting = $this->countByCastingDirector($castingDirector);
        }

        return $totalCountCasting;
    }


}