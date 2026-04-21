<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\Calendar\CalendarService;
use App\Services\CastingDirectorService;
use App\Services\ChatService;
use App\Services\CastingService;
use App\Services\RoleService;
use App\Services\SubscriberService;
use App\Services\SubscriptionService;
use App\Services\TalentService;
use App\Services\UserService;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/dashboard';

    /**
     * @var UserService
     */
    protected $userService;

    /**
     * @var RoleService
     */
    protected $roleService;

    /**
     * @var CastingDirectorService
     */
    protected $castingDirectorService;

    /**
     * @var TalentService;
     */
    protected $talentService;

    /**
     * @var SubscriptionService
     */
    protected $subscriptionService;

    /**
     * @var CastingService
     */
    protected $castingService;

    /**
     * @var ChatService
     */
    protected $chatService;

    /**
     * @var SubscriberService
     */
    protected $subscriberService;

    /**
     * @var CalendarService
     */
    protected  $calendarService;

    /**
     * AuthController constructor.
     * @param UserService $userService
     * @param RoleService $roleService
     * @param CastingDirectorService $castingDirectorService
     * @param TalentService $talentService
     * @param SubscriptionService $subscriptionService
     * @param CastingService $castingService
     * @param ChatService $chatService
     * @param SubscriberService $subscriberService
     * @param CalendarService $calendarService
     */
    public function __construct(UserService $userService,
                                RoleService $roleService,
                                CastingDirectorService $castingDirectorService,
                                TalentService $talentService,
                                SubscriptionService $subscriptionService,
                                CastingService $castingService,
                                ChatService $chatService,
                                SubscriberService $subscriberService,
                                CalendarService $calendarService)
    {
//        $this->middleware('guest')->except(
//            [
//                'logout',
//                'changePassword',
//                'toggleEmailNotificationsActive',
//                'updateUser',
//                'removeUser',
//                'changeAuth'
//                ]);
        $this->userService = $userService;
        $this->roleService = $roleService;
        $this->castingDirectorService = $castingDirectorService;
        $this->talentService = $talentService;
        $this->subscriptionService = $subscriptionService;
        $this->castingService = $castingService;
        $this->chatService = $chatService;
        $this->subscriberService = $subscriberService;
        $this->calendarService = $calendarService;
    }

    /**
     * Send the response after the user was authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    protected function sendLoginResponse(Request $request)
    {
        $request->session()->regenerate();

        $this->clearLoginAttempts($request);

        if ($request->expectsJson()) {

            /**
             * @var User $user;
             */
            $user = \Auth::user();

            if($user->subscribed('main') === false ){

                if(!$user->hasRoleByKey('super_admin') && !$user->hasRoleByKey(Role::KEY_ROL_BASIC)){
                    $this->userService->resetRoles($user);
                }
            }
            else {
                $this->subscriptionService->checkStatusStripeSubscription($user);
            }

            $userArray = \Auth::user()->toArray();
            $userArray['redirectTo'] = redirect()->intended($this->redirectPath())->getTargetUrl();

            $apiRes = new ApiResponse('User', [$userArray]);

            return response()->json($apiRes);
        } else {
            return redirect()->intended($this->redirectPath());
        }
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();
        if ($request->expectsJson()) {
            return response()->json();
        } else {
            return redirect('/');
        }
    }

    /**
     * Get the failed login response instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        $apiRes = new ApiResponse('Auth');
        $apiRes->errors->add($this->username(), trans('auth.failed'));

        return response()->json($apiRes, 422);
    }

    /**
     * Redirect the user after determining they are locked out.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendLockoutResponse(Request $request)
    {
        $seconds = $this->limiter()->availableIn(
            $this->throttleKey($request)
        );

        $message = Lang::get('auth.throttle', ['seconds' => $seconds]);

        $apiRes = new ApiResponse('Auth');
        $apiRes->errors->add($this->username(), $message);

        return response()->json($apiRes, 423);
    }

    public function changePassword(Request $request) {
        $user = \Auth::user();
        if(is_null($user)) {
            return response()->json(null, 401);
        }

        $user = $this->userService->changePassword($user, $request->input());
        $apiRes = new ApiResponse('User');

        if($this->userService->hasErrors()) {
            $apiRes->errors = $this->userService->getErrors();
            return response()->json($apiRes, 422);
        }

        $apiRes->results[] = $user;

        return response()->json($apiRes);
    }

    public function toggleEmailNotificationsActive(Request $request) {
        $user = \Auth::user();
        if (is_null($user)) {
            return response()->json(null, 401);
        }

        $this->userService->toggleEmailNotificationsActive($user, $request->input());
        return response()->json(null);
    }

    public function store(Request $request){
        $user = $this->userService->create($request->input());

        $apiRes = new ApiResponse('User');
        if ($this->userService->hasErrors()) {
            $apiRes->errors->merge($this->userService->getErrors());
            return response()->json($apiRes, 422);
        }
        $userArray = $user->toArray();
        $userArray['redirectTo'] = redirect()->intended($this->redirectPath())->getTargetUrl();

        $apiRes->results[] = $userArray;

        return response()->json($apiRes);
    }

    public function removeUser(Request $request) {
        /**
         * @var User $authUser
         */
        $authUser = \Auth::user();

        $apiRes = new ApiResponse('User');

        if (!$authUser->checkPassword($request->input('password'))) {
            $apiRes->errors->add('password', 'The password is incorrect');
            return response()->json($apiRes, 422);
        }

        $this->guard()->logout();
        $request->session()->invalidate();

        $this->userService->delete(
            $authUser,
            $this->castingDirectorService,
            $this->talentService,
            $this->subscriptionService,
            $this->castingService,
            $this->chatService,
            $this->subscriberService,
            $this->calendarService
            );

        return response()->json(null);
    }

    public function changeAuth(Request $request, $userId) {
        /**
         * @var User $authUser
         */
        $authUser = \Auth::user();
        if (!$authUser->hasRoleByKey('super_admin')) {
            return response()->json(null, 403);
        }

        $newAuthUser = $this->userService->getById($userId);
        if (is_null($newAuthUser)) {
            return response()->json(null, 404);
        }
        \Auth::login($newAuthUser);
        return response()->json(null);
    }
}
