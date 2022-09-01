<?php
/**
 * Created by PhpStorm.
 * User: Ariel
 * Date: 5/27/2017
 * Time: 2:41 PM
 */

namespace App\Services;

use App\Models\Organization;
use App\Models\CastingType;
use App\Models\Site;
use App\Repositories\CastingTypeRepository;
use Illuminate\Support\Facades\Validator;

class CastingTypeService extends BaseService
{
    /**
     * @var CastingTypeRepository
     */
    protected $baseRepository;

    /**
     * CastingTypeService constructor.
     * @param CastingTypeRepository $baseRepository
     */
    public function __construct(CastingTypeRepository $baseRepository)
    {
        parent::__construct($baseRepository);
    }

    /**
     * If organization = null, the user will be created as global user
     * @param array $data
     * @return CastingType|null
     */
    public function create(array $data) {
        /**
         * @var Validator $validator
         */
        $validationRules = [
            'name' => 'required',
            'code' => 'required|alpha_dash'
        ];

        /**
         * @var \Illuminate\Contracts\Validation\Validator $validator
         */
        $validator = Validator::make($data, $validationRules);

        if ($validator->fails()) {
            $this->errors->merge($validator->errors());
        }

        $castingType = null;
        if (!$this->hasErrors()) {

            /**
             * Validate project_code unique
             */
            $foundCastingType = $this->baseRepository->getByCode($data['code']);
            if(is_null($foundCastingType)){
                $castingType = new CastingType($data['name'], $data['code']);
                if (array_key_exists('description', $data)) {
                    $castingType->setDescription($data['description']);
                }

                $this->baseRepository->save($castingType);
            }else{
                $this->errors->add('code','The code is already in use');
            }

        }

        return $castingType;
    }

    public function update(CastingType $castingType, array $data) {
        $validatorRules = [
            'name' => 'filled',
            'code' => 'filled|alpha_dash'
        ];
        $messages = [];

        if (array_key_exists('name', $data)) {
            $castingType->setName($data['name']);
        }

        if (array_key_exists('code', $data) ) {
            $foundCastingType = $this->baseRepository->getByCode($data['code']);
            if(is_null($foundCastingType)){
                $castingType->setCode($data['code']);
            }else{
                if($castingType->getId() == $foundCastingType->getId()){
                    $castingType->setCode($data['code']);
                }else{
                    $this->errors->add('code','The code is already in use');
                }
            }
        }

        if (array_key_exists('description', $data)) {
            $castingType->setDescription($data['description']);
        }

        /**
         * @var \Illuminate\Contracts\Validation\Validator $validator
         */
        $validator = Validator::make($data, $validatorRules,$messages);

        if($validator->fails()) {
            $this->errors->merge($validator->errors());
        }

        if(!$this->hasErrors()){
            $this->baseRepository->save($castingType);
        }

        return $castingType;
    }

    public function delete(CastingType $castingType) {
        $this->baseRepository->delete($castingType);
    }
}