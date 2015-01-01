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
        
        // Open a new connection for Cypher queries. BAD CODE!
        $client = new Everyman\Neo4j\Client();
        
        // Check if the user is in there already
        $player = Steam::user(Session::get('steam_id'))->GetPlayerSummaries()[0]; 
        $player_match_query_string = "MATCH (n { steamId: '{$player->steamId}'}) RETURN n";
        $player_match_query = new Everyman\Neo4j\Cypher\Query($client, $player_match_query_string);
        $player_match_result = $player_match_query->getResultSet();
        if (count($player_match_result) == 0)
        {
            // Add the player to database
            $player_node = Neo4j::makeNode();
            $player_node->setProperty('steamId', $player->steamId);
            $player_node->setProperty('personaName', $player->personaName);
            $player_node->setProperty('lastLogoff', $player->lastLogoff);
            $player_node->setProperty('profileUrl', $player->profileUrl);
            $player_node->setProperty('avatarUrl', $player->avatarUrl);
            $player_node->setProperty('avatarMediumUrl', $player->avatarMediumUrl);
            $player_node->setProperty('avatarFullUrl', $player->avatarFullUrl);
            $player_node->setProperty('personaStateId', $player->personaStateId);
            $player_node->setProperty('realName', $player->realName);
            $player_node->setProperty('primaryClanId', $player->primaryClanId);
            $player_node->setProperty('timecreated', $player->timecreated);
            $player_node->setProperty('locCountryCode', $player->locCountryCode);
            $player_node->setProperty('locStateCode', $player->locStateCode);
            $player_node->setProperty('locCityId', $player->locCityId);
            $player_node->save();
            
            $player_node_id = $player_node->getId();
        }
        else
        {
            $player_node_id = $player_match_result[0]['n']->getId();
            $player_node = Neo4j::getNode($player_node_id);
        }   
        
        $player_plays = $player_node->getRelationships(array('PLAYS'));
        foreach($player_plays as $player_play)
        {
            $player_play->delete();
        }
        
        foreach($owned_games as $game)
        {
            // Check if the game is already saved
            $game_match_query_string = "MATCH (n { appId: '{$game->appId}'}) RETURN n";
            $game_match_query = new Everyman\Neo4j\Cypher\Query($client, $game_match_query_string);
            $game_match_result = $game_match_query->getResultSet();
            if (count($game_match_result) == 0)
            {
                // Add games to database
                $game_node = Neo4j::makeNode();
                $game_node->setProperty('appId', $game->appId);
                $game_node->setProperty('name', $game->name);
                $game_node->setProperty('icon', $game->icon);
                $game_node->setProperty('logo', $game->logo);
                $game_node->setProperty('header', $game->header);
                $game_node->save();
                
                $game_node_id = $game_node->getId();
            }
            else
            {
                $game_node_id = $game_match_result[0]['n']->getId();
                $game_node = Neo4j::getNode($game_node_id);
            }
            
            // Create relationship between the player and the game
            $player_node->relateTo(
                    $game_node
                    , 'PLAYS'
                )
                ->setProperty(
                    'playtimeTwoWeeks'
                    , $game->playtimeTwoWeeks
                )
                ->setProperty(
                    'playtimeForever'
                    , $game->playtimeForever
                )
                ->save();
        }
                
        print_r(Steam::user(Session::get('steam_id'))->GetPlayerSummaries()[0]);
        
        print_r($owned_games->toArray());
    }
    

}
