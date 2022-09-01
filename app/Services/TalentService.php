<?php
/**
 * Created by PhpStorm.
 * User: Ariel
 * Date: 5/27/2017
 * Time: 2:41 PM
 */

namespace App\Services;

use App\Helpers\StorageFile;
use App\Models\Category;
use App\Models\Location;
use App\Models\Casting;
use App\Models\SpecSheet;
use App\Models\Talent;
use App\Models\User;
use App\Repositories\TalentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Illuminate\Support\Facades\Validator;

class TalentService extends BaseService
{
    /**
     * @var TalentRepository
     */
    protected $baseRepository;

    /**
     * @var UserService
     */
    protected $userService;

    /**
     * @var CategoryService
     */
    protected $categoryService;

    /**
     * @var CastingTypeService
     */
    protected $castingTypeService;

    /**
     * @var AttributeService
     */
    protected $attributeService;

    /**
     * @var LocationService
     */
    protected $locationService;

    /**
     * @var CastingTalentService
     */
    protected $castingTalentService;

    /**
     * @var ProjectTypeService
     */
    protected $projectTypeService;

    /**
     * TalentService constructor.
     * @param TalentRepository $baseRepository
     * @param UserService $userService
     * @param CategoryService $categoryService
     * @param CastingTypeService $castingTypeService
     * @param AttributeService $attributeService
     * @param LocationService $locationService
     * @param CastingTalentService $castingTalentService
     * @param ProjectTypeService $projectTypeService
     */
    public function __construct(TalentRepository $baseRepository, UserService $userService,
                                CategoryService $categoryService, CastingTypeService $castingTypeService,
                                AttributeService $attributeService, LocationService $locationService,
                                CastingTalentService $castingTalentService, ProjectTypeService $projectTypeService
    )
    {
        parent::__construct($baseRepository);
        $this->userService = $userService;
        $this->categoryService = $categoryService;
        $this->castingTypeService = $castingTypeService;
        $this->attributeService = $attributeService;
        $this->locationService = $locationService;
        $this->castingTalentService = $castingTalentService;
        $this->projectTypeService = $projectTypeService;
    }

    /**
     * @param array $data
     * @return Talent|null
     */
    public function create(array $data) {
        /**
         * @var Validator $validator
         */
        $validationRules = [
            'firstName' => 'required|string',
            'lastName' => 'required|string',
            'location' => 'filled',
        ];

        /**
         * @var \Illuminate\Contracts\Validation\Validator $validator
         */
        $validator = Validator::make($data, $validationRules);

        if ($validator->fails()) {
            $this->errors->merge($validator->errors());
        }

        $talent = null;
        if (!$this->hasErrors()) {
            $category = $this->categoryService->getByCode(Category::CODE_PERSON);
            if (is_null($category)) {
                $this->errors->add('general', 'The talent can not be create due to a misconfiguration in categories');
            }

            if (!$this->hasErrors()) {

                $talent = new Talent($category, $data['firstName'], $data['lastName']);

                if (array_key_exists('description', $data)) {
                    $talent->setDescription($data['description']);
                }

                if (array_key_exists('location', $data)) {
                    $location = $this->locationService->create($data['location']);
                    if(!is_null($location)){
                        $talent->setLocation($location);
                        $talent->setLocationName($location->getName());
                    }
                }

                if (array_key_exists('typeIdList', $data)) {
                    $typeList = $this->castingTypeService->getAllByIdList($data['typeIdList']);
                    foreach($typeList as $type) {
                        $talent->getCastingTypes()->add($type);
                    }
                }

                if(array_key_exists('projectTypeIdList', $data)){
                    $projectTypeList = $this->projectTypeService->getAllByIdList($data['projectTypeIdList']);
                    foreach($projectTypeList as $projectType) {
                        $talent->getProjectTypes()->add($projectType);
                    }
                }

                if(array_key_exists('unionType', $data)){
                    $talent->setUnionType($data['unionType']);
                }

                if(array_key_exists('paymentType', $data)){
                    $talent->setPaymentType($data['paymentType']);
                }

                if(array_key_exists('gender', $data)) {
                    $talent->setGender($data['gender']);
                }

                $data['name'] = $data['firstName']. ' ' . $data['lastName'];
                $data['roleKey'] = 'talent';
                $user = $this->userService->create($data);
                $this->errors->merge($this->userService->getErrors());

                if (!$this->hasErrors()) {
                    $talent->setUser($user);
                    $this->baseRepository->save($talent);
                }
            }
        }

        return $talent;
    }

    public function update(Talent $talent, array $data, array $castingAttributeValues = null) {
        $validatorRules = [
            'firstName' => 'filled',
            'lastName' => 'filled',
            'location' => 'filled',
            'represented' => 'required|boolean',
            'agency' => 'required_if:represented,true',
            'birthday' => 'required'
        ];

        $validationMessages = [
            'agency.required_if' => 'The Agency field is required'
        ];

        /**
         * @var \Illuminate\Contracts\Validation\Validator $validator
         */
        $validator = Validator::make($data, $validatorRules, $validationMessages);

        if($validator->fails()) {
            $this->errors->merge($validator->errors());
        }

        if(!$this->hasErrors()) {
            if (array_key_exists('firstName', $data)) {
                $talent->setFirstName($data['firstName']);
            }

            if (array_key_exists('lastName', $data)) {
                $talent->setLastName($data['lastName']);
            }

            if (array_key_exists('description', $data)) {
                $talent->setDescription($data['description']);
            }

            $talent->getCastingAttributeValues()->clear();
            $talent->setCastingAttributeValues($castingAttributeValues);

            if(array_key_exists('paymentType', $data)){
                $talent->setPaymentType($data['paymentType']);
            }

            if(array_key_exists('birthday', $data)) {
                $talent->setBirthday(new \DateTime($data['birthday']));
            }

            if(array_key_exists('gender', $data)) {
                $talent->setGender($data['gender']);
            }

            if (array_key_exists('location', $data)) {
                if (is_null($talent->getLocation())) {
                    $location = $this->locationService->create($data['location']);
                    if(!is_null($location)){
                        $talent->setLocation($location);
                        $talent->setLocationName($location->getName());
                    }
                } else {
                    $location = $this->locationService->update($talent->getLocation(), $data['location']);
                    if(!is_null($location)){
                        $talent->setLocationName($location->getName());
                    }
                }
            }

            if (array_key_exists('typeIdList', $data)) {
                $typeList = $this->castingTypeService->getAllByIdList($data['typeIdList']);
                $talent->getCastingTypes()->clear();
                foreach($typeList as $type) {
                    $talent->getCastingTypes()->add($type);
                }
            }

            if (array_key_exists('projectTypeIdList', $data)) {
                $projectTypeList = $this->projectTypeService->getAllByIdList($data['projectTypeIdList']);
                $talent->getProjectTypes()->clear();
                foreach($projectTypeList as $projectType) {
                    $talent->getProjectTypes()->add($projectType);
                }
            }

            if (array_key_exists('profile', $data)) {
                if (array_key_exists('attributeList', $data['profile'])) {
                    $this->attributeService->saveAll($talent->getProfile(), $data['profile']);
                }
            }

            if(array_key_exists('videoUrl', $data)){

                if (empty($data['videoUrl'])){
                    $talent->setVideoUrl();
                }else{
                    if (filter_var($data['videoUrl'], FILTER_VALIDATE_URL)) {
                        $talent->setVideoUrl($data['videoUrl']);
                    } else {
                        $this->errors->add('videoUrl','The video url format is invalid.');
                    }
                }
            }

            if (array_key_exists('imageList', $data)) {
                $imageList = [];
                foreach($data['imageList'] as $imageData) {
                    $newImage = StorageFile::createFromArray($imageData);
                    if (!is_null($newImage)) {
                        $newImage->move('uploads/users/user-'.$talent->getUser()->getId().'/'.$newImage->getFileName());
                        $imageList[] = $newImage;
                    } else {
                        foreach($talent->getImages() as $image) {
                            if(!is_null($imageData) && array_key_exists('fileName', $imageData)
                                && $image->getFileName() == $imageData['fileName']) {
                                $imageList[] = $image;
                                break;
                            }
                        }
                    }
                }
                $talent->setImages($imageList);
            }

            if (array_key_exists('user', $data) && array_key_exists('profileImage', $data['user'])) {
                if (!is_null($data['user']['profileImage'])) {
                    if (array_key_exists('fileName', $data['user']['profileImage'])) {
                        foreach($talent->getImages() as $image) {
                            if ($image->getFileName() == $data['user']['profileImage']['fileName']) {
                                $this->userService->updateProfileImage($talent->getUser(), $image);
                                break;
                            }
                        }
                    }
                }
            }

            if($data['represented']){
                $talent->setRepresented($data['represented']);
                $talent->setAgency($data['agency']);
            }else{
                $talent->setRepresented(false);
                $talent->setAgency(null);
            }

            $this->userService->update($talent->getUser(), $data);
            $this->errors->merge($this->userService->getErrors());
            if (!$this->hasErrors()) {
                $this->baseRepository->save($talent);
                /**
                 * Clear all the matched and checked castingTalents by talent
                 */
                $this->castingTalentService->deleteAllPendingByTalent($talent);
            }
        }
        return $talent;
    }

    public function delete(Talent $talent) {
        $this->baseRepository->delete($talent);
    }

    /**
     * @param User $user
     * @return Talent
     */
    public function getByUser(User $user) {
        return $this->baseRepository->getByUser($user);
    }

    public function getAllAvailableByCasting(Casting $casting, $data = []) {
        $limit = 50;
        if (array_key_exists('limit', $data)) {
            $limit = $data['limit'];
        }
        return $this->baseRepository->getAllAvailableByCasting($casting, $limit);
    }

    public function updatePlusByUser(User $user) {
        $talent = $this->getByUser($user);
        if (!is_null($talent)) {
            $talent->setIsPlus($user->hasRoleByKey('talent_plus'));
            $this->baseRepository->save($talent);
        }
    }

    public function getAllByLocation(Location $location) {
        return $this->baseRepository->getAllByLocation($location);
    }
}