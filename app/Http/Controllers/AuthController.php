<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Auth;

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
     * AuthController constructor.
     * @param UserService $userService
     */
    public function __construct(
        UserService $userService,
    ) {
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

            if ($user->subscribed('main') === false) {

                if (!$user->hasRoleByKey('super_admin') && !$user->hasRoleByKey(Role::KEY_ROL_BASIC)) {
                    $this->userService->resetRoles($user);
                }
            } else {
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

    public function toggleEmailNotificationsActive(Request $request)
    {
        $user = \Auth::user();
        if (is_null($user)) {
            return response()->json(null, 401);
        }

        $this->userService->toggleEmailNotificationsActive($user, $request->input());
        return response()->json(null);
    }

    /**
     * Change user password
     * @param ChangePasswordRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @OA\Post(
     *     path="/api/auth/change-password",
     *     tags={"auth"},
     *     security={{"sanctum": {}}},
     *     summary="Change authenticated user password",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={
     *                     "current_password",
     *                     "password",
     *                     "password_confirmation"
     *                 },
     *                 @OA\Property(
     *                     property="current_password",
     *                     type="string",
     *                     format="password",
     *                     example="oldpassword123"
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     type="string",
     *                     format="password",
     *                     example="newpassword123"
     *                 ),
     *                 @OA\Property(
     *                     property="password_confirmation",
     *                     type="string",
     *                     format="password",
     *                     example="newpassword123"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password changed successfully"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or incorrect current password"
     *     )
     * )
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(null, 401);
        }

        $data = $request->validated();

        $user = $this->userService->changePassword($user, $data);

        $apiRes = new ApiResponse('User');

        if ($this->userService->hasErrors()) {
            $apiRes->errors = $this->userService->getErrors();
            return response()->json($apiRes, 422);
        }

        $apiRes->results[] = $user;

        return response()->json($apiRes);
    }
    /**
     * Register a new user
     *
     * @param RegisterRequest $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Post(
     *     path="/api/auth/register",
     *     tags={"auth"},
     *     summary="Register a new user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={
     *                     "name",
     *                     "email",
     *                     "password",
     *                     "password_confirmation"
     *                 },
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     example="John Doe"
     *                 ),
     *                 @OA\Property(
     *                     property="email",
     *                     type="string",
     *                     format="email",
     *                     example="john@example.com"
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     type="string",
     *                     format="password",
     *                     example="password123"
     *                 ),
     *                 @OA\Property(
     *                     property="password_confirmation",
     *                     type="string",
     *                     format="password",
     *                     example="password123"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */

    public function store(RegisterRequest $request)
    {
        $user = $this->userService->register($request->validated());

        $apiRes = new ApiResponse('User');
        if ($this->userService->hasErrors()) {
            $apiRes->errors->merge($this->userService->getErrors());
            return response()->json($apiRes, 422);
        }
        $userArray = $user->toArray();

        $apiRes->results[] = $userArray;

        return response()->json($apiRes, 201);
    }

    public function changeAuth(Request $request, $userId)
    {
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

    /**
     * Login user
     * @param LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @OA\Post(
     *     path="/api/auth/login",
     *     tags={"auth"},
     *     summary="Login user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"email", "password"},
     *                 @OA\Property(property="email", type="string", format="email"),
     *                 @OA\Property(property="password", type="string", format="password")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User authenticated successfully"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials"
     *     )
     * )
     */
    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        $user = $this->userService->login($data);
        $apiRes = new ApiResponse('Auth');
        if ($this->userService->hasErrors()) {
            $apiRes->errors->merge($this->userService->getErrors());
            return response()->json($apiRes, 401);
        }

        $userArray = $user->toArray();
        $userArray['token'] = $user->createToken('auth_token')->plainTextToken;
        $apiRes->results[] = $userArray;

        return response()->json($apiRes);
    }

    /**
     * Logout user
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @OA\Post(
     *     path="/api/auth/logout",
     *     tags={"auth"},
     *     security={{"sanctum": {}}},
     *     summary="Logout user",
     *     @OA\Response(
     *         response=200,
     *         description="User logged out successfully"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function logoutUser(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Request password reset
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @OA\Post(
     *     path="/api/auth/forgot-password",
     *     tags={"auth"},
     *     summary="Request password reset",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"email"},
     *                 @OA\Property(property="email", type="string", format="email")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset link sent"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $data = $request->validated();

        $this->userService->requestPasswordReset($data);

        $apiRes = new ApiResponse('Auth');
        if ($this->userService->hasErrors()) {
            $apiRes->errors->merge($this->userService->getErrors());
            return response()->json($apiRes, 422);
        }

        return response()->json([
            'message' => 'Password reset link sent to your email'
        ]);
    }

    /**
     * Reset password
     * @param ResetPasswordRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @OA\Post(
     *     path="/api/auth/reset-password",
     *     tags={"auth"},
     *     summary="Reset user password",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"email", "token", "password", "password_confirmation"},
     *                 @OA\Property(property="email", type="string", format="email"),
     *                 @OA\Property(property="token", type="string"),
     *                 @OA\Property(property="password", type="string", format="password"),
     *                 @OA\Property(property="password_confirmation", type="string", format="password")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Invalid token or validation error"
     *     )
     * )
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        $this->userService->resetPassword($request->validated());

        $apiRes = new ApiResponse('Auth');
        if ($this->userService->hasErrors()) {
            $apiRes->errors->merge($this->userService->getErrors());
            return response()->json($apiRes, 422);
        }

        return response()->json(['message' => 'Password reset successfully']);
    }

    /**
     * Get current authenticated user
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @OA\Get(
     *     path="/api/auth/me",
     *     tags={"auth"},
     *     security={{"sanctum": {}}},
     *     summary="Get current user",
     *     @OA\Response(
     *         response=200,
     *         description="Current user data"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function me(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(null, 401);
        }

        $apiRes = new ApiResponse('User');
        $userArray = $user->toArray();
        $apiRes->results[] = $userArray;

        return response()->json($apiRes);
    }
}
