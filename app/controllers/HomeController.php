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
            $game_match_query_string = "MATCH (n { appId: {$game->appId}}) RETURN n";
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

    public function getFriends($SteamId)
    {
        $friends = Steam::user($SteamId)->GetFriendList('friend');

        // Open a new connection for Cypher queries. BAD CODE!
        $client = new Everyman\Neo4j\Client();

        $player_original_match_query_string = "MATCH (n { steamId: '{$SteamId}'}) RETURN n";
        $player_original_match_query = new Everyman\Neo4j\Cypher\Query($client, $player_original_match_query_string);
        $player_original_match_result = $player_original_match_query->getResultSet();

        if (count($player_original_match_result) == 0)
        {
            dd('Dude is not here');
        }

        $player_original = Neo4j::getNode($player_original_match_result[0]['n']->getId());

        foreach($friends as $friend)
        {
            // Check if the user is in there already
            $player_match_query_string = "MATCH (n { steamId: '{$friend->steamId}'}) RETURN n";
            $player_match_query = new Everyman\Neo4j\Cypher\Query($client, $player_match_query_string);
            $player_match_result = $player_match_query->getResultSet();
            if (count($player_match_result) == 0)
            {
                // Add the player to database
                $player_node = Neo4j::makeNode();
                $player_node->setProperty('steamId', $friend->steamId);
                $player_node->setProperty('personaName', $friend->personaName);
                $player_node->setProperty('lastLogoff', $friend->lastLogoff);
                $player_node->setProperty('profileUrl', $friend->profileUrl);
                $player_node->setProperty('avatarUrl', $friend->avatarUrl);
                $player_node->setProperty('avatarMediumUrl', $friend->avatarMediumUrl);
                $player_node->setProperty('avatarFullUrl', $friend->avatarFullUrl);
                $player_node->setProperty('personaStateId', $friend->personaStateId);
                $player_node->setProperty('realName', $friend->realName);
                $player_node->setProperty('primaryClanId', $friend->primaryClanId);
                $player_node->setProperty('timecreated', $friend->timecreated);
                $player_node->setProperty('locCountryCode', $friend->locCountryCode);
                $player_node->setProperty('locStateCode', $friend->locStateCode);
                $player_node->setProperty('locCityId', $friend->locCityId);
                $player_node->save();

                $player_node_id = $player_node->getId();

                echo "Created steamId : {$friend->steamId} ({$friend->personaName}) <br />\n";
            }
            else
            {
                $player_node_id = $player_match_result[0]['n']->getId();
                $player_node = Neo4j::getNode($player_node_id);

                echo "Already there steamId : {$friend->steamId} ({$friend->personaName}) <br />\n";
            }

            // Check if these guys are saved as friends
            $friends_query_string = "match (n {steamId:'{$player_original->steamId}'})-[r:FRIEND_WITH]-(m {steamId: '{$player_node->steamId}'}) return n,m";
            $friends_query = new Everyman\Neo4j\Cypher\Query($client, $friends_query_string);
            $friends_result = $friends_query->getResultSet();
            if (count($friends_result) == 0)
            {
                // Make them friends
                $player_original->relateTo(
                    $player_node
                    , 'FRIEND_WITH'
                )->save();

                //
                $player_node->relateTo(
                    $player_original
                    , 'FRIEND_WITH'
                )->save();

                echo "Created friendship {$player_original->personaName} <-> {$player_node->personaName} <br />\n";
            }
            else
            {
                echo "Already friends {$player_original->personaName} <-> {$player_node->personaName} <br />\n";
            }
        }
    }


}
