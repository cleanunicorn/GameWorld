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
            $player_create_string = "
                CREATE (n:Player {
                    steamId: '{$player->steamId}',
                    personaName: '". addslashes($player->personaName) ."',
                    lastLogoff: '{$player->lastLogoff}',
                    profileUrl: '{$player->profileUrl}',
                    avatarUrl: '{$player->avatarUrl}',
                    avatarMediumUrl: '{$player->avatarMediumUrl}',
                    avatarFullUrl: '{$player->avatarFullUrl}',
                    personaStateId: '{$player->personaStateId}',
                    realName: '{$player->realName}',
                    primaryClanId: '{$player->primaryClanId}',
                    timecreated: '{$player->timecreated}',
                    locCountryCode: '{$player->locCountryCode}',
                    locStateCode: '{$player->locStateCode}',
                    locCityId: '{$player->locCityId}'
                })
                RETURN n
            ";
            $player_create_query = new Everyman\Neo4j\Cypher\Query($client, $player_create_string);
            $player_create_result = $player_create_query->getResultSet();
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
                $game_create_string = "
                    CREATE (n:Game {
                        appId: '{$game->appId}',
                        name: '". addslashes($game->name) ."',
                        icon: '{$game->icon}',
                        logo: '{$game->logo}',
                        header: '{$game->header}'
                    })
                    RETURN n
                ";
                $game_create_query = new Everyman\Neo4j\Cypher\Query($client, $game_create_string);
                $game_create_result = $game_create_query->getResultSet();
            }

            $player_plays_game_string = "
                MATCH
                    (n:Player {steamId: '{$player->steamId}'}), (m:Game {appId:'{$game->appId}'})
                CREATE UNIQUE
                    (n)-[r:PLAYS]->(m)
                SET
                    r.appId='{$game->appId}',
                    r.name='". addslashes($game->name) ."',
                    r.icon='{$game->icon}',
                    r.logo='{$game->logo}',
                    r.header='{$game->header}'
                RETURN n,r,m
            ";
            $player_plays_game_query = new Everyman\Neo4j\Cypher\Query($client, $player_plays_game_string);
            $player_plays_game_result = $player_plays_game_query->getResultSet();

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

        $player_original = $player_original_match_result[0]['n'];

        foreach($friends as $friend)
        {
            // Check if the user is in there already
            $player_match_query_string = "MATCH (n { steamId: '{$friend->steamId}'}) RETURN n";
            $player_match_query = new Everyman\Neo4j\Cypher\Query($client, $player_match_query_string);
            $player_match_result = $player_match_query->getResultSet();
            if (count($player_match_result) == 0)
            {
                // Add the player to database
                $player_create_string = "
                    CREATE (n:Player {
                        steamId: '{$friend->steamId}',
                        personaName: '". addslashes($friend->personaName) ."',
                        lastLogoff: '{$friend->lastLogoff}',
                        profileUrl: '{$friend->profileUrl}',
                        avatarUrl: '{$friend->avatarUrl}',
                        avatarMediumUrl: '{$friend->avatarMediumUrl}',
                        avatarFullUrl: '{$friend->avatarFullUrl}',
                        personaStateId: '{$friend->personaStateId}',
                        realName: '{$friend->realName}',
                        primaryClanId: '{$friend->primaryClanId}',
                        timecreated: '{$friend->timecreated}',
                        locCountryCode: '{$friend->locCountryCode}',
                        locStateCode: '{$friend->locStateCode}',
                        locCityId: '{$friend->locCityId}'
                    })
                    RETURN n
                ";
                $player_create_query = new Everyman\Neo4j\Cypher\Query($client, $player_create_string);
                $player_create_result = $player_create_query->getResultSet();

                echo "Created steamId : {$friend->steamId} ({$friend->personaName}) <br />\n";
            }

            // Add friend relationship from original player to friend
            $player_friend_player_string = "
                MATCH
                    (n:Player {steamId: '{$player_original->steamId}'}), (m:Player {steamId:'{$friend->steamId}'})
                CREATE UNIQUE
                    (n)-[r:FRIEND]->(m)
                RETURN n,r,m
            ";
            $player_friend_player_query = new Everyman\Neo4j\Cypher\Query($client, $player_friend_player_string);
            $player_friend_player_result = $player_friend_player_query->getResultSet();

            // Add friend relationship from friend to original player
            $player_friend_player_string = "
                MATCH
                    (n:Player {steamId: '{$player_original->steamId}'}), (m:Player {steamId:'{$friend->steamId}'})
                CREATE UNIQUE
                    (m)-[r:FRIEND]->(n)
                RETURN n,r,m
            ";
            $player_friend_player_query = new Everyman\Neo4j\Cypher\Query($client, $player_friend_player_string);
            $player_friend_player_result = $player_friend_player_query->getResultSet();
        }
    }

    public function getGames($SteamId)
    {
        $owned_games = Steam::player($SteamId)->GetOwnedGames(true, true);

        // Open a new connection for Cypher queries. BAD CODE!
        $client = new Everyman\Neo4j\Client();

        // Check if the user is in there already
        $player = Steam::user($SteamId)->GetPlayerSummaries()[0];
        $player_match_query_string = "MATCH (n { steamId: '{$player->steamId}'}) RETURN n";
        $player_match_query = new Everyman\Neo4j\Cypher\Query($client, $player_match_query_string);
        $player_match_result = $player_match_query->getResultSet();
        if (count($player_match_result) == 0)
        {
            // Add the player to database
            $player_create_string = "
                CREATE (n:Player {
                    steamId: '{$player->steamId}',
                    personaName: '". addslashes($friend->personaName) ."',
                    lastLogoff: '{$player->lastLogoff}',
                    profileUrl: '{$player->profileUrl}',
                    avatarUrl: '{$player->avatarUrl}',
                    avatarMediumUrl: '{$player->avatarMediumUrl}',
                    avatarFullUrl: '{$player->avatarFullUrl}',
                    personaStateId: '{$player->personaStateId}',
                    realName: '{$player->realName}',
                    primaryClanId: '{$player->primaryClanId}',
                    timecreated: '{$player->timecreated}',
                    locCountryCode: '{$player->locCountryCode}',
                    locStateCode: '{$player->locStateCode}',
                    locCityId: '{$player->locCityId}'
                })
                RETURN n
            ";
            $player_create_query = new Everyman\Neo4j\Cypher\Query($client, $player_create_string);
            $player_create_result = $player_create_query->getResultSet();
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
                $game_create_string = "
                    CREATE (n:Game {
                        appId: '{$game->appId}',
                        name: '". addslashes($game->name) ."',
                        icon: '{$game->icon}',
                        logo: '{$game->logo}',
                        header: '{$game->header}'
                    })
                    RETURN n
                ";
                $game_create_query = new Everyman\Neo4j\Cypher\Query($client, $game_create_string);
                $game_create_result = $game_create_query->getResultSet();
            }

            $player_plays_game_string = "
                MATCH
                    (n:Player {steamId: '{$player->steamId}'}), (m:Game {appId:'{$game->appId}'})
                CREATE UNIQUE
                    (n)-[r:PLAYS]->(m)
                SET
                    r.appId='{$game->appId}',
                    r.name='". addslashes($game->name) ."',
                    r.icon='{$game->icon}',
                    r.logo='{$game->logo}',
                    r.header='{$game->header}'
                RETURN n,r,m
            ";
            $player_plays_game_query = new Everyman\Neo4j\Cypher\Query($client, $player_plays_game_string);
            $player_plays_game_result = $player_plays_game_query->getResultSet();

        }
    }


}
