<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ChangePasswordController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Change the password.
     *
     * @param  Request  $request
     * @return Response
     */
    public function change(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => '',
            'new_password' => 'confirmed',
        ]);

        $validator->after(function ($validator) use ($request) {
            if (! Hash::check($request->password, $request->user()->password)) {
                $validator->errors()->add('password', 'Incorrect password.');
            }
        });

        if ($validator->fails()) {
            return view('auth.passwords.change', $request->all())->withErrors($validator);
        }

        $request->user()->fill([
            'password' => Hash::make($request->new_password)
        ])->save();

        return response('Password changed.', 200);
    }


    public function view(Request $request)
    {
        return view('auth.passwords.change');
    }
}
