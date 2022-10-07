<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller {

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request) {
        $apiRes = new ApiResponse('User');

        $newUser = new User($request->all());
        $newUser->save();

        $apiRes->filterCount = 1;
        $apiRes->totalCount = 1;
        $apiRes->results = $newUser;

        return response()->json($apiRes);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function update(Request $request, User $user) {
        $apiRes = new ApiResponse('User');

        $user->update($request->all());

        $apiRes->filterCount = 1;
        $apiRes->totalCount = 1;
        $apiRes->results = $user;

        return response()->json($apiRes);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function destroy(User $user) {
        $apiRes = new ApiResponse('User');

        $apiRes->results = $user->delete();

        return response()->json($apiRes);
    }
}
