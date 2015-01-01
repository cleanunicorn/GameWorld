<?php

use Ehesp\SteamLogin\SteamLogin;

class HomeController extends BaseController {

    public function getHome()
    {
        $login = new SteamLogin();
        
        return View::make(
            'home'
            , array(
                'login_url' => $login->url(URL::to('login'))
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
        
        foreach($owned_games as $game)
        {
            //
            $game_node = Neo4j::makeNode();
            $game_node->setProperty('appId', $game->appId);
            $game_node->setProperty('name', $game->name);
            $game_node->setProperty('playtimeTwoWeeks', $game->playtimeTwoWeeks);
            $game_node->setProperty('playtimeForever', $game->playtimeForever);
            $game_node->setProperty('icon', $game->icon);
            $game_node->setProperty('logo', $game->logo);
            $game_node->setProperty('header', $game->header);
            $game_node->save();
        }
        
        print_r(Steam::user(Session::get('steam_id'))->GetPlayerSummaries()[0]);
        
        print_r($owned_games->toArray());
    }
    

}
