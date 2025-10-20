<?php

namespace ACES2\IPTV\SMARKETS;

use ACES2\Curl;
use ACES2\IPTV\LeagueTeams;

class Event {

    public $id = 0;

    public $name = "";
    public $slug = "";
    public $full_slug = "";
    public $type = "";
    public $start_datetime = '';
    public $end_datetime = '';
    public $team1 = '';
    public $team2 = '';

    public function __construct(array $event, string $league ) {
        $this->name = $event['name'];
        $this->slug = $event['slug'];
        $this->full_slug = $event['full_slug'];
        $this->type = $event['type'];
        $this->start_datetime = $event['start_datetime'];
        $this->id = (int)$event['id'];


        $Teams = LeagueTeams::getTeams(strtoupper($league));

        $slug = strtoupper(str_replace("-", " ", $this->slug));
        foreach($Teams as $team => $team_name ) {
            if(strpos($slug, $team) !== false) {
                if(empty($this->team1))
                    $this->team1 = $team;
                else {
                    $this->team2 = $team;
                    break;
                }
            }
        }

    }

    static public function getEventState(int $event_id ) {
        $curl = new Curl("https://api.smarkets.com/v3/events/{$event_id}/states/");
        $response = json_decode($curl->getBody());
        return  $response->event_states[0]->state;
    }


}