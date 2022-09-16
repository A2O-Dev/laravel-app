<?php
/**
 * Created by PhpStorm.
 * User: Ariel
 * Date: 5/28/2017
 * Time: 11:22 AM
 */

namespace App\Services;

use App\Helpers\StorageFile;
use App\Models\Covenant;
use App\Models\Deed;
use App\Models\Easement;
use App\Models\Lien;
use App\Models\Mortgage;
use App\Models\Title;
use App\Models\TitleType;
use App\Models\User;
use App\Repositories\TitleRepository;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;


class TitleService extends BaseService
{
    /**
     * @var TitleRepository
     */
    protected $baseRepository;

    /**
     * @var LocationService
     */
    protected $locationService;

    /**
     * @var BoughtTitleService
     */
    protected $boughtTitleService;

    /**
     * @var ShoppingCartService $shoppingCartService
     */
    protected $shoppingCartService;

    /**
     * @var DeedTypeService
     */
    protected $deedTypeService;

    /**
     * @var LienTypeService
     */
    protected $lienTypeService;

    /**
     * @var EasementTypeService
     */
    protected $easementTypeService;

    /**
     * TitleService constructor.
     * @param TitleRepository $baseRepository
     * @param LocationService $locationService
     * @param BoughtTitleService $boughtTitleService
     * @param ShoppingCartService $shoppingCartService
     * @param DeedTypeService $deedTypeService
     * @param LienTypeService $lienTypeService
     * @param EasementTypeService $easementTypeService
     */
    public function __construct(TitleRepository $baseRepository, LocationService $locationService,
                                BoughtTitleService $boughtTitleService, ShoppingCartService $shoppingCartService,
                                DeedTypeService $deedTypeService, LienTypeService $lienTypeService,
                                EasementTypeService $easementTypeService)
    {
        parent::__construct($baseRepository);
        $this->locationService = $locationService;
        $this->boughtTitleService = $boughtTitleService;
        $this->shoppingCartService = $shoppingCartService;
        $this->deedTypeService = $deedTypeService;
        $this->lienTypeService = $lienTypeService;
        $this->easementTypeService = $easementTypeService;
    }

    public function getAllByAddress($data)
    {
        return null;
    }

    /**
     * @param User $owner
     * @param array $data
     * @return Title|null
     */
    public function create(User $owner, array $data)
    {
        $validationRules = [
            'acceptTermsSale' => 'required|accepted',
            'location' => 'required',
            'dateSearch' => 'required|date',
            'dateEffective' => 'required|date',
            'searchType' => ['required',
                Rule::in(['commercial', 'residential'])
            ],
            'searchTypeDetail' => [
                'required',
                Rule::in(['update', 'current_owner', 'limited', 'full', 'full_year'])
            ],
            'searchTypeDetailValue' => 'required_if:searchTypeDetail,full_year',
            'parcel' => 'alpha_num'
        ];

        $attributeNames = array(
            'apartment' => 'Condo/Unit/Apartment',
        );

        $validator = Validator::make($data, $validationRules);
        $validator->setAttributeNames($attributeNames);

        if ($validator->fails()) {
            $this->errors->merge($validator->errors());
        }

        $title = null;
        if (!$this->hasErrors()) {

            $location = $this->locationService->create($data['location']);
            if ($this->locationService->hasErrors()) {
                $this->errors->merge($this->locationService->getErrors());
            } else {
                $title = new Title($owner, $location, new \DateTime($data['dateSearch']));
                $title->setDateEffective(new \DateTime($data['dateEffective']));
                $title->setApartment($data['apartment']);
                $title->setSearchType($data['searchType']);
                $title->setSearchTypeDetail($data['searchTypeDetail']);
                if (array_key_exists('searchTypeDetailValue', $data)) {
                    $title->setSearchTypeDetailValue($data['searchTypeDetailValue']);
                }

                if (array_key_exists('parcel', $data)) {
                    $title->setParcel($data['parcel']);
                }

                $this->baseRepository->save($title);
            }
        }

        return $title;
    }

    public function update(Title $title, array $data)
    {
        switch ($data['lastTitleStep']) {
            case 'basicInfo':
                return $this->updateBasicInfo($title, $data);
            case 'legalDescription':
                return $this->updateLegalDescription($title, $data);
            case 'chainTitle':
                return $this->updateChainTitle($title, $data);
            case 'mortgages':
                return $this->updateMortgages($title, $data);
            case 'liens':
                return $this->updateLiens($title, $data);
            case 'easements':
                return $this->updateEasements($title, $data);
            case 'covenants':
                return $this->updateCovenants($title, $data);
            case 'notes':
                return $this->updateNotes($title, $data);
            case 'finalize':
                return $this->updateFinalize($title, $data);
        }
    }

    public function updateBasicInfo(Title $title, $data)
    {
        $validationRules = [
            'acceptTermsSale' => 'required|accepted',
            'location' => 'required',
            'dateSearch' => 'required|date',
            'dateEffective' => 'required|date',
            'searchType' => ['required',
                Rule::in(['commercial', 'residential'])
            ],
            'searchTypeDetail' => [
                'required',
                Rule::in(['update', 'current_owner', 'limited', 'full', 'full_year'])
            ],
            'searchTypeDetailValue' => 'required_if:searchTypeDetail,full_year'
        ];

        $attributeNames = array(
            'apartment' => 'Condo/Unit/Apartment',
        );

        $validator = Validator::make($data, $validationRules);
        $validator->setAttributeNames($attributeNames);

        if ($validator->fails()) {
            $this->errors->merge($validator->errors());
        }

        if (!$this->hasErrors()) {
            if ($title->getLocationName() != $data['location']['name']) {
                $this->locationService->update($title->getLocation(), $data['location']);
            }
            if ($this->locationService->hasErrors()) {
                $this->errors->merge($this->locationService->getErrors());
            } else {
                $title->setDateSearch(new \DateTime($data['dateSearch']));
                $title->setDateEffective(new \DateTime($data['dateEffective']));

                $title->setApartment($data['apartment']);
                $title->setSearchType($data['searchType']);
                $title->setSearchTypeDetail($data['searchTypeDetail']);
                if (array_key_exists('searchTypeDetailValue', $data)) {
                    $title->setSearchTypeDetailValue($data['searchTypeDetailValue']);
                }

                if ($title->getStatus() == Title::STATUS_DRAFT) {
                    $title->setLastTitleStep($data['lastTitleStep']);
                }

                $this->baseRepository->save($title);
            }
        }

        return $title;
    }

    public function updateLegalDescription(Title $title, $data)
    {
        $validationRules = [
            'type' => 'required',
            'condoName' => 'required'
        ];
        $attributeNames = array(
            'condoName' => 'Subdivision/Condo Name',
        );

        if ($title->getLocation()->getState() == 'GA') {
            $additionalValidations = [
                'district' => 'required|numeric|integer|min:0',
                'landLot' => 'required|numeric|integer|min:0',
            ];
        } else {
            $additionalValidations = [
                'township' => 'required',
                'range' => 'required'
            ];
        }
        $validationRules = array_merge($validationRules, $additionalValidations);

        $validator = Validator::make($data, $validationRules);
        $validator->setAttributeNames($attributeNames);

        if ($validator->fails()) {
            $this->errors->merge($validator->errors());
        }

        if (!$this->hasErrors()) {
            $title->setSection($data['section']);
            $title->setType($data['type']);
            $title->setCondoName($data['condoName']);

            if (array_key_exists('district', $data)) {
                $title->setDistrict($data['district']);
            }

            if (array_key_exists('gmd', $data)) {
                $title->setGmd($data['gmd']);
            }

            if (array_key_exists('landLot', $data)) {
                $title->setLandLot($data['landLot']);
            }

            if (array_key_exists('township', $data)) {
                $title->setTownship($data['township']);
            }

            if (array_key_exists('range', $data)) {
                $title->setRange($data['range']);
            }

            if (array_key_exists('zones', $data)) {
                $title->setZones($data['zones']);
            }

            if (array_key_exists('lot', $data)) {
                $title->setLot($data['lot']);
            }

            if (array_key_exists('block', $data)) {
                $title->setBlock($data['block']);
            }

            if (array_key_exists('phase', $data)) {
                $title->setPhase($data['phase']);
            }

            if (array_key_exists('pod', $data)) {
                $title->setPod($data['pod']);
            }

            if (array_key_exists('unit', $data)) {
                $title->setUnit($data['unit']);
            }

            if (array_key_exists('subdivisionSection', $data)) {
                $title->setSubdivisionSection($data['subdivisionSection']);
            }

            if (array_key_exists('interestCommon', $data)) {
                $title->setInterestCommon($data['interestCommon']);
            }

            if (array_key_exists('parking', $data)) {
                $title->setParking($data['parking']);
            }

            if (array_key_exists('garage', $data)) {
                $title->setGarage($data['garage']);
            }

            if (array_key_exists('storage', $data)) {
                $title->setStorage($data['storage']);
            }

            if (array_key_exists('wine', $data)) {
                $title->setWine($data['wine']);
            }

            if (array_key_exists('platList', $data)) {
                $title->setPlatList($data['platList']);
            }

            if (array_key_exists('floorPlanList', $data)) {
                $title->setFloorPlanList($data['floorPlanList']);
            }

            if (array_key_exists('longLegal', $data)) {
                $title->setLongLegal($data['longLegal']);
            }

            if ($title->getStatus() == Title::STATUS_DRAFT) {
                $title->setLastTitleStep($data['lastTitleStep']);
            }

            $this->baseRepository->save($title);
        }

        return $title;
    }

    public function updateChainTitle(Title $title, $data)
    {
        $validationRules = [
            'deedList' => 'required',
            'chainTitleType' => ['required',
                Rule::in(['current_owner', 'full'])
            ],
            'deedList.*.deedType' => 'required',
            'deedList.*.deedDate' => 'required|date',
            'deedList.*.recDate' => 'required|date',
            'deedList.*.deedBook' => 'required',
            'deedList.*.deedPage' => 'required',
            'deedList.*.grantor' => 'required',
            'deedList.*.grantee' => 'required',
        ];

        $attributeNames = array(
            'deedList.*.deedType' => 'Deed Type',
            'deedList.*.deedDate' => 'Deed Date',
            'deedList.*.recDate' => 'Rec Date',
            'deedList.*.deedBook' => 'Deed Book',
            'deedList.*.deedPage' => 'Deed Page',
            'deedList.*.grantor' => 'Grantor',
            'deedList.*.grantee' => 'Grantee',
        );

        $validator = Validator::make($data, $validationRules);
        $validator->setAttributeNames($attributeNames);

        if ($validator->fails()) {
            $this->errors->merge($validator->errors());
        }

        if (!$this->hasErrors()) {
            $title->setChainTitleType($data['chainTitleType']);
            $title->getDeedList()->clear();
            foreach ($data['deedList'] as $key => $deedData) {
                $deedType = $this->deedTypeService->getById($deedData['deedType']['id']);
                if (!is_null($deedType)) {
                    $deed = new Deed($title, $deedType);
                    $deed->setDeedDate(new \DateTime($deedData['deedDate']));
                    $deed->setRecDate(new \DateTime($deedData['recDate']));
                    $deed->setDeedBook($deedData['deedBook']);
                    $deed->setDeedPage($deedData['deedPage']);
                    $deed->setGrantor($deedData['grantor']);
                    $deed->setGrantee($deedData['grantee']);

                    $title->getDeedList()->add($deed);
                }
            }

            if ($title->getStatus() == Title::STATUS_DRAFT) {
                $title->setLastTitleStep($data['lastTitleStep']);
            }

            $this->baseRepository->save($title);
        }

        return $title;
    }

    public function updateMortgages(Title $title, $data)
    {
        $validationRules = [
            'mortgageList.*.deedType' => 'required',
            'mortgageList.*.deedDate' => 'required|date',
            'mortgageList.*.recDate' => 'required|date',
            'mortgageList.*.deedBook' => 'required',
            'mortgageList.*.deedPage' => 'required',
            'mortgageList.*.grantor' => 'required',
            'mortgageList.*.grantee' => 'required',
            'mortgageList.*.amountBorrowed' => 'required',
        ];

        $attributeNames = array(
            'mortgageList.*.deedType' => 'Deed Type',
            'mortgageList.*.deedDate' => 'Deed Date',
            'mortgageList.*.recDate' => 'Rec Date',
            'mortgageList.*.deedBook' => 'Deed Book',
            'mortgageList.*.deedPage' => 'Deed Page',
            'mortgageList.*.grantor' => 'Grantor',
            'mortgageList.*.grantee' => 'Grantee',
            'mortgageList.*.amountBorrowed' => 'Amount Borrowed',
        );

        $validator = Validator::make($data, $validationRules);
        $validator->setAttributeNames($attributeNames);

        if ($validator->fails()) {
            $this->errors->merge($validator->errors());
        }

        if (!$this->hasErrors()) {
            $title->getMortgageList()->clear();
            foreach ($data['mortgageList'] as $key => $mortgageData) {
                $deedType = $this->deedTypeService->getById($mortgageData['deedType']['id']);
                if (!is_null($deedType)) {
                    $mortgage = new Mortgage($title, $deedType);
                    $mortgage->setDeedDate(new \DateTime($mortgageData['deedDate']));
                    $mortgage->setRecDate(new \DateTime($mortgageData['recDate']));
                    $mortgage->setDeedBook($mortgageData['deedBook']);
                    $mortgage->setDeedPage($mortgageData['deedPage']);
                    $mortgage->setGrantor($mortgageData['grantor']);
                    $mortgage->setGrantee($mortgageData['grantee']);
                    $mortgage->setAmountBorrowed($mortgageData['amountBorrowed']);

                    $mortgage->setTransferList($mortgageData['transferList']);
                    $mortgage->setReRecordedList($mortgageData['reRecordedList']);
                    $mortgage->setSubordinatedList($mortgageData['subordinatedList']);
                    $mortgage->setUccList($mortgageData['uccList']);

                    $title->getMortgageList()->add($mortgage);
                }
            }

            if ($title->getStatus() == Title::STATUS_DRAFT) {
                $title->setLastTitleStep($data['lastTitleStep']);
            }

            $this->baseRepository->save($title);
        }

        return $title;
    }

    public function updateLiens(Title $title, $data)
    {
        $validationRules = [
            'lienList.*.lienType' => 'required',
            'lienList.*.lienor' => 'required',
            'lienList.*.debtor' => 'required',
            'lienList.*.book' => 'required',
            'lienList.*.page' => 'required',
            'lienList.*.amount' => 'required',
        ];

        $attributeNames = array(
            'lienList.*.lienType' => 'Lien Type',
            'lienList.*.lienor' => 'Lienor',
            'lienList.*.debtor' => 'Debtor',
            'lienList.*.book' => 'Lien Book',
            'lienList.*.page' => 'Lien Page',
            'lienList.*.amount' => 'Amount',
        );

        $validator = Validator::make($data, $validationRules);
        $validator->setAttributeNames($attributeNames);

        if ($validator->fails()) {
            $this->errors->merge($validator->errors());
        }

        if (!$this->hasErrors()) {
            $title->getLienList()->clear();
            foreach ($data['lienList'] as $key => $lienData) {
                $lienType = $this->lienTypeService->getById($lienData['lienType']['id']);
                if (!is_null($lienType)) {
                    $lien = new Lien($title, $lienType);
                    $lien->setLienor($lienData['lienor']);
                    $lien->setDebtor($lienData['debtor']);
                    $lien->setBook($lienData['book']);
                    $lien->setPage($lienData['page']);
                    $lien->setAmount($lienData['amount']);

                    $lien->setDeedList($lienData['deedList']);
                    $lien->setTransferList($lienData['transferList']);

                    $title->getLienList()->add($lien);
                }
            }

            if ($title->getStatus() == Title::STATUS_DRAFT) {
                $title->setLastTitleStep($data['lastTitleStep']);
            }

            $this->baseRepository->save($title);
        }

        return $title;
    }

    public function updateEasements(Title $title, $data)
    {
        $validationRules = [
            'easementList.*.easementType' => 'required',
            'easementList.*.deedDate' => 'required|date',
            'easementList.*.recDate' => 'required|date',
            'easementList.*.deedBook' => 'required',
            'easementList.*.deedPage' => 'required',
            'easementList.*.grantor' => 'required',
            'easementList.*.grantee' => 'required',
        ];

        $attributeNames = array(
            'easementList.*.easementType' => 'Easement Type',
            'easementList.*.deedDate' => 'Deed Date',
            'easementList.*.recDate' => 'Rec Date',
            'easementList.*.deedBook' => 'Deed Book',
            'easementList.*.deedPage' => 'Deed Page',
            'easementList.*.grantor' => 'Grantor',
            'easementList.*.grantee' => 'Grantee',
        );

        $validator = Validator::make($data, $validationRules);
        $validator->setAttributeNames($attributeNames);

        if ($validator->fails()) {
            $this->errors->merge($validator->errors());
        }

        if (!$this->hasErrors()) {
            $title->getEasementList()->clear();
            foreach ($data['easementList'] as $key => $easementData) {
                $easementType = $this->easementTypeService->getById($easementData['easementType']['id']);
                if (!is_null($easementType)) {
                    $easement = new Easement($title, $easementType);
                    $easement->setDeedDate(new \DateTime($easementData['deedDate']));
                    $easement->setRecDate(new \DateTime($easementData['recDate']));
                    $easement->setDeedBook($easementData['deedBook']);
                    $easement->setDeedPage($easementData['deedPage']);
                    $easement->setGrantor($easementData['grantor']);
                    $easement->setGrantee($easementData['grantee']);

                    $title->getEasementList()->add($easement);
                }
            }

            if ($title->getStatus() == Title::STATUS_DRAFT) {
                $title->setLastTitleStep($data['lastTitleStep']);
            }

            $this->baseRepository->save($title);
        }

        return $title;
    }

    public function updateCovenants(Title $title, $data)
    {
        if (array_key_exists('covenant', $data)) {
            $covenant = $title->getCovenant();
            if (is_null($data['covenant'])) {
                $title->setCovenant(null);
                if (!is_null($covenant)) {
                    $this->baseRepository->delete($covenant);
                }
            } else {
                if (is_null($covenant)) {
                    $covenant = new Covenant($title);
                }
                $covenantData = $data['covenant'];
                if (array_key_exists('type', $covenantData)) {
                    $covenant->setType($covenantData['type']);
                }

                if (array_key_exists('deedBook', $covenantData)) {
                    $covenant->setDeedBook($covenantData['deedBook']);
                }

                if (array_key_exists('deedPage', $covenantData)) {
                    $covenant->setDeedPage($covenantData['deedPage']);
                }

                if (array_key_exists('dateRecorded', $covenantData)) {
                    $covenant->setDateRecorded(new \DateTime($covenantData['dateRecorded']));
                }

                if (array_key_exists('amendedList', $covenantData)) {
                    $covenant->setAmendedList($covenantData['amendedList']);
                }

                if (array_key_exists('reRecordedList', $covenantData)) {
                    $covenant->setReRecordedList($covenantData['reRecordedList']);
                }

                if (array_key_exists('supplementedList', $covenantData)) {
                    $covenant->setSupplementedList($covenantData['supplementedList']);
                }
                $title->setCovenant($covenant);
            }
        }

        if ($title->getStatus() == Title::STATUS_DRAFT) {
            $title->setLastTitleStep($data['lastTitleStep']);
        }

        $this->baseRepository->save($title);

        return $title;
    }

    public function updateNotes(Title $title, $data)
    {
        if (array_key_exists('note', $data)) {
            $title->setNote($data['note']);
        }

        if ($title->getStatus() == Title::STATUS_DRAFT) {
            $title->setLastTitleStep($data['lastTitleStep']);
        }

        $this->baseRepository->save($title);

        return $title;
    }

    public function updateFinalize(Title $title, $data)
    {
        $validationRules = [
            'documentList' => 'required',
            'price' => 'required|integer|min:0',
            'certifiedByUser' => 'required|accepted',
        ];

        $attributeNames = array(
            'documentList' => 'Document',
            'price' => 'Price',
            'certifyInfo' => 'Certify Info',
        );

        $customMessages = [
            'documentList.required' => 'You need to upload a Document'
        ];

        $validator = Validator::make($data, $validationRules, $customMessages);
        $validator->setAttributeNames($attributeNames);

        if ($validator->fails()) {
            $this->errors->merge($validator->errors());
        }

        if (!$this->hasErrors()) {
            $documentList = [];
            foreach ($data['documentList'] as $documentData) {
                $newDocument = StorageFile::createFromArray($documentData);
                if (!is_null($newDocument)) {
                    $newDocument->move('uploads/titles/title-' . $title->getId() . '/' . $newDocument->getFileName());
                    $documentList[] = $newDocument;
                } else {
                    foreach ($title->getDocuments() as $document) {
                        if (!is_null($documentData) && array_key_exists('fileName', $documentData)
                            && $document->getFileName() == $documentData['fileName']
                        ) {
                            $documentList[] = $document;
                            break;
                        }
                    }
                }
            }
            $title->setDocuments($documentList);
            $title->setPrice($data['price']);

            if ($title->getStatus() == Title::STATUS_DRAFT) {
                $title->setLastTitleStep($data['lastTitleStep']);
            }

            $title->setCertifiedByUser(true);

            $title->publish();

            $this->baseRepository->save($title);
        }

        return $title;
    }

    public function delete(Title $title)
    {
        $boughtTitleList = $this->boughtTitleService->getAllByTitle($title);

        //Setting up the boughtTitles associated to the title before eliminating it
        foreach ($boughtTitleList as $boughtTitle) {
            if($boughtTitle->getSale()->getStatus() == 'succeeded'){
                //Setting the title_id field of the boughtTitle to null
                $boughtTitle->clearTitle();
                $this->baseRepository->save($boughtTitle);
            }
            else if($boughtTitle->getSale()->getStatus() == 'pending'){
                //Getting the boughtTitle out of the associated shoppingCart
                $this->shoppingCartService->removeItemFromCart($boughtTitle->getSale(), $boughtTitle);
            }
        }
        $this->baseRepository->delete($title);
    }

    public function getAllByOwner(User $owner, $data = []){

        $this->requestMapper->populate($data);
        $titleList = $this->baseRepository->getAllByOwner($owner,
            $this->requestMapper->getValue('limit'),
            $this->requestMapper->getValue('offset'),
            $this->requestMapper->getValue('order'));

        return $titleList;
    }

    public function totalCountByOwner(User $owner){
        return $this->baseRepository->totalCountByOwner($owner);
    }

    public function totalCountByStatus(User $owner, $data = []){

        $status = null;
        if (array_key_exists('status', $data)) {
            $status = $data['status'];
        }

        return $this->baseRepository->totalCountByStatus($owner, $status);
    }


}