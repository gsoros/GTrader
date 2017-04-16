<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use GTrader\Bot;

class BotController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }


    public function list(Request $request)
    {
        return response(Bot::getListOfUser(Auth::id()), 200);
    }


    public function new(Request $request)
    {
        $user_id = Auth::id();
        $name = 'New Bot';
        $i = 2;
        while (true) {
            if (!Bot::where('user_id', $user_id)
                        ->where('name', $name)
                        ->count()) {
                break;
            }
            $name = 'New Bot #'.$i;
            $i++;
        }

        $bot = new Bot;
        $bot->user_id = $user_id;
        $bot->name = $name;
        $bot->status = 'disabled';
        $bot->save();

        return response($bot->toHTML(), 200);
    }


    public function idCheck(Request $request)
    {
        if (!($bot = Bot::find($request->id))) {
            error_log('Failed to load bot ID '.$request->id);
            return response('Failed to load bot.', 404);
        }
        if ($bot->user_id !== Auth::id()) {
            error_log('That bot belongs to someone else: ID '.$request->id);
            return response('Failed to load bot.', 403);
        }
        return $bot;
    }


    public function form(Request $request)
    {
        if (!($bot = $this->idCheck($request)) instanceof Bot) {
            return $bot;
        }

        return response($bot->toHTML(), 200);
    }


    public function delete(Request $request)
    {
        if (!($bot = $this->idCheck($request)) instanceof Bot) {
            return $bot;
        }
        $bot->delete();
        return response(Bot::getListOfUser(Auth::id()), 200);
    }


    public function save(Request $request)
    {
        if (!($bot = $this->idCheck($request)) instanceof Bot) {
            return $bot;
        }
        $bot->handleSaveRequest($request);
        $bot->save();
        return response(Bot::getListOfUser(Auth::id()), 200);
    }
}
