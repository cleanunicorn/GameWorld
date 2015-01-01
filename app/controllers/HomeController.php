<?php

use Ehesp\SteamLogin\SteamLogin;

class HomeController extends BaseController {

    public function getHome()
    {
        $login = new SteamLogin();
        
        return View::make(
            'home'
            , array(
                'login_url' => $login->url(URL::to('logged'))
            )
        );
    }
    
    public function getLogin()
    {
        $login = new SteamLogin();
        $user_id = $login->validate();
        
        if ($user_id)
        {
            Session::put('steam_id', $user_id);
            return Redirect::to('/logged');
        }
        else
        {
            return Redirect::to('/');
        }
    }
    
    public function getLogged()
    {
        $owned_games = Steam::player(Session::get('steam_id'))->GetOwnedGames(true, true);
        
        print_r($owned_games->toArray());
    }
    

}
