<?php

class RecommendController extends BaseController {

    private $client = null;

    public function __construct()
    {
        $this->client = new Everyman\Neo4j\Client();
    }

    public function getByFriends($SteamId)
    {
        /*
MATCH (n {steamId:'76561197991639729'})-[r:FRIEND]-(friend)
WITH DISTINCT friend
MATCH (friend)-[r:PLAYS]->(game)
WITH game

MATCH (n {steamId:'76561197991639729'})-[r:FRIEND]-(friend)-[p:PLAYS]-(game)
RETURN game, COUNT(friend)
ORDER BY COUNT(friend) DESC
LIMIT 100
        */



/*
MATCH (n {steamId:'76561197991639729'})-[r:FRIEND]-(friend)
WITH DISTINCT friend
MATCH (friend)-[r:PLAYS]->(game)
WITH game

MATCH (n {steamId:'76561197991639729'})
WHERE NOT n--(game)
WITH DISTINCT game

MATCH (n {steamId:'76561197991639729'})-[r:FRIEND]-(friend)-[p:PLAYS]-(game)
RETURN game, COUNT(friend)
ORDER BY COUNT(friend) DESC
LIMIT 100
*/

        $games_friends_play_string = "
            MATCH (n {steamId:'{$SteamId}'})-[r:FRIEND]-(friend)
            WITH DISTINCT friend
            MATCH (friend)-[r:PLAYS]->(game)
            WITH game

            MATCH (n {steamId:'{$SteamId}'})
            WHERE NOT n--(game)
            WITH DISTINCT game

            MATCH (n {steamId:'{$SteamId}'})-[r:FRIEND]-(friend)-[p:PLAYS]-(game)
            RETURN game, COUNT(friend)
            ORDER BY COUNT(friend) DESC
            LIMIT 100
        ";
        $games_friends_play_query = new Everyman\Neo4j\Cypher\Query($this->client, $games_friends_play_string);
        $games_friends_play_result = $games_friends_play_query->getResultSet();

        foreach($games_friends_play_result as $game)
        {
            echo "<a href='http://store.steampowered.com/app/{$game[0]->appId}/'>{$game[0]->name}</a> <br />\n";
            echo "<img src='{$game[0]->logo}' /> <br />\n";
        }


    }

    public function getByFriendsPlayTime($SteamId)
    {
/*
MATCH (n:Player {steamId:'76561197991639729'})-[r:FRIEND]-(friend:Player)
WITH DISTINCT friend
MATCH (friend:Player)-[r:PLAYS]->(game:Game)

MATCH (n:Player {steamId:'76561197991639729'})
WHERE NOT n--(game:Game)
WITH DISTINCT game

MATCH (n:Player {steamId:'76561197991639729'})-[r:FRIEND]-(friend:Player)-[play:PLAYS]-(game:Game)
WITH game,
	SUM(play.playtimeTwoWeeks) AS total_playtimeTwoWeeks
ORDER BY total_playtimeTwoWeeks DESC
RETURN game, total_playtimeTwoWeeks
LIMIT 1000*/

        $games_friends_play_string = "
            MATCH (n:Player {steamId:'{$SteamId}'})-[r:FRIEND]-(friend:Player)
            WITH DISTINCT friend
            MATCH (friend:Player)-[r:PLAYS]->(game:Game)

            MATCH (n:Player {steamId:'{$SteamId}'})
            WHERE NOT n--(game:Game)
            WITH DISTINCT game

            MATCH (n:Player {steamId:'{$SteamId}'})-[r:FRIEND]-(friend:Player)-[play:PLAYS]-(game:Game)
            WITH game,
                SUM(play.playtimeTwoWeeks) AS total_playtimeTwoWeeks
            ORDER BY total_playtimeTwoWeeks DESC
            RETURN game, total_playtimeTwoWeeks
            LIMIT 100
        ";
        $games_friends_play_query = new Everyman\Neo4j\Cypher\Query($this->client, $games_friends_play_string);
        $games_friends_play_result = $games_friends_play_query->getResultSet();

        foreach($games_friends_play_result as $game)
        {
            echo "<a href='http://store.steampowered.com/app/{$game[0]->appId}/'>{$game[0]->name}</a> <br />\n";
            echo "<img src='{$game[0]->logo}' /> <br />\n";
        }

    }

}
