<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JoinChannelController extends Controller
{
    public function __invoke(Request $request)
    {
        return response()->json([
            'public',
            'App.Models.User.'.Auth::id()
        ]);
    }
}
