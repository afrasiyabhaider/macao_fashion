<?php

namespace App\Http\Controllers\Auth;

use App\CashRegister;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

use App\Utils\BusinessUtil;
use App\Utils\CashRegisterUtil;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your  screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * All Utils instance.
     *
     */
    protected $businessUtil;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    // protected $redirectTo = 'portal/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(BusinessUtil $businessUtil)
    {
        $this->middleware('guest')->except('logout');
        $this->businessUtil = $businessUtil;
    }

    /**
     * Change authentication from email to username
     *
     * @return void
     */
    public function username()
    {
        return 'username';
    }

    public function logout()
    {
        request()->session()->flush();
        \Auth::logout();
        return redirect('/login');
    }

    /**
     * The user has been authenticated.
     * Check if the business is active or not.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        if (!$user->business->is_active) {
            \Auth::logout();
            return redirect('/login')
                ->with(
                    'status',
                    ['success' => 0, 'msg' => __('lang_v1.business_inactive')]
                );
        } elseif ($user->status != 'active') {
            \Auth::logout();
            return redirect('/login')
                ->with(
                    'status',
                    ['success' => 0, 'msg' => __('lang_v1.user_inactive')]
                );
        }
    }

    protected function redirectTo()
    {
        $user = Auth::user();
        $location_id = $user->business_location_id;
        // $location_id = request()->session()->get('user.business_location_id');
        $register = CashRegister::where('statusss','open')->where('location_id',$location_id)->latest()->first();

        // dd($user,$register);
        if (!empty($register)) {
            $open_date = Carbon::parse($register->created_at)->format('d-m-Y');
            $current = Carbon::now()->format('d-m-Y');
            // dd($register, $open_date, $current);
            if ($open_date != $current) {
                $cashRegisterUtil = new CashRegisterUtil();
                $total_sale = $cashRegisterUtil->getRegisterDetails($location_id)->total_sale;
                $register->closing_amount = $total_sale;
                $register->location_id = $location_id;
                $register->closed_at = Carbon::now()->format('Y-m-d H:i:s');
                $register->statusss = 'close';
                
                $register->save();
            }
        }
        if (!$user->can('dashboard.data') && $user->can('sell.create')) {
            return '/pos/create';
        }
        return '/pos/create';
        // return 'portal/';
    }
}
