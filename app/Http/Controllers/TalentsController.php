<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Helpers\ApiResponse;
use App\Models\CastingAttributeValue;
use App\Models\Talent;
use App\Services\TalentService;
use App\Services\CastingAttributeValueService;
use Illuminate\Http\Request;

class TalentsController extends Controller
{
	/**
	 * @var TalentService
	 */
	protected $talentService;

	/**
	 * @var CastingAttributeValue
	 */
	protected $castingAttributeValueService;

	/**
	 * TalentController constructor.
	 * @param TalentService $talentService
	 * @param CastingAttributeValueService $castingAttributeValueService
	 */
	public function __construct(TalentService $talentService,
								CastingAttributeValueService $castingAttributeValueService)
	{
		parent::__construct();
		$this->talentService = $talentService;
		$this->castingAttributeValueService = $castingAttributeValueService;
		$this->middleware('auth')->except('store');
	}

	public function index(Request $request) {
		$talentList = $this->talentService->getAll($request->input());
		$apiRes = new ApiResponse('Talent', $talentList);
		return response()->json($apiRes);
	}

	public function show(Request $request, $id) {

		$talent = $this->talentService->getById($id);

		if(is_null($talent)){
			return response()->json(null, 404);
		}

		$apiRes = new ApiResponse('Talent', [$talent]);
		return response()->json($apiRes);
	}

	public function store(Request $request) {
		$talent = $this->talentService->create($request->input());

		$apiRes = new ApiResponse('Talent');
		if ($this->talentService->hasErrors()) {
			$apiRes->errors->merge($this->talentService->getErrors());
			return response()->json($apiRes, 422);
		}

		$apiRes->results[] = $talent;

		return response()->json($apiRes);
	}

	public function update(Request $request, $id) {
		$apiRes = new ApiResponse('Talent');

		$talentObj = $this->talentService->getById($id);
		if(is_null($talentObj)){
			return response()->json(null, 404);
		}

		if($request->has('unionTypes') && count($request->get('unionTypes')) > 0){
			$unionTypes = $this->castingAttributeValueService->getAllByIdList($request->input()['unionTypes']);
		}
		else{
			$apiRes->errors->add('unionTypes', 'Union type is required');
			return response()->json($apiRes, 422);
		}

		$talent = $this->talentService->update($talentObj, $request->input(), $unionTypes);

		if($this->talentService->hasErrors()) {
			$apiRes->errors = $this->talentService->getErrors();
			return response()->json($apiRes, 422);
		}

		$apiRes->results[] = $talent->toArray(Talent::ARRAY_FULL);

		return response()->json($apiRes);
	}

	public function destroy($id) {
		$talentObj = $this->talentService->getById($id);

		if (is_null($talentObj)) {
			return response()->json(null, 404);
		}

		$this->talentService->delete($talentObj);

		return response()->json(null);
	}
}
