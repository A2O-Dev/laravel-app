<?php

namespace App\Http\Controllers\ViewControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TalentsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
//		$user = \Auth::user();
//		if (!$user->hasRoleByKey('super_admin')) {
//			return redirect('dashboard');
//		}
		return view('admin.talents');
    }

	/**
	 * Displays talent's profile
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function profile(Request $request, $id = null) {
		$talent = null;
		if (\Auth::check()) {
			if (is_null($id)) {
				$talent = $this->talentService->getByUser(\Auth::user());
			} else {
				$talent = $this->talentService->getById($id);
			}
			if (is_null($talent)) {
				return redirect('dashboard');
			}
			return view('talent.profile', ['talent' => $talent]);
		} else {
			if (is_null($id)) {
				return redirect('/');
			}
			$talent = $this->talentService->getById($id);
			if (is_null($talent)) {
				return redirect('/');
			}
			return view('talent.public-profile', ['talent' => $talent]);
		}
	}

}
