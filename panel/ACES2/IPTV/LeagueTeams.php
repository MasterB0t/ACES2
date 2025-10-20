<?php

namespace ACES2\IPTV;

class LeagueTeams
{
    public static function getTeams(string $league ) {

        switch(strtoupper($league)) {
            case 'MLB' :
                return array(
                    'ATHLETICS' => 'Athletics',
                    'WHITE SOX' => 'Chicago White Sox',
                    'CUBS' => 'Chicago Cubs',
                    'ANGELS' => 'Los Angeles Angels',
                    'DODGERS' => 'Los Angeles Dodgers',
                    'PIRATES' => 'Pittsburgh Pirates',
                    'DIAMONDBACKS' => 'Arizona Diamondbacks',
                    'BRAVES' => 'Atlanta Braves',
                    'ORIOLES' => 'Baltimore Orioles',
                    'RED SOX' => 'Boston Red Sox',
                    'REDS' => 'Cincinnati Reds',
                    'GUARDIANS' => 'Cleveland Guardians',
                    'ROCKIES' => 'Colorado Rockies',
                    'TIGERS' => 'Detroit Tigers',
                    'ASTROS' => 'Houston Astros',
                    'ROYALS' => 'Kansas City Royals',
                    'YANKEES' => 'New York Yankees',
                    'METS' => 'New York Mets',
                    'TWINS' => 'Minnesota Twins',
                    'BREWERS' => 'Milwaukee Brewers',
                    'MARLINS' => 'Miami Marlins',
                    'PHILLIES' => 'Philadelphia Phillies',
                    'PADRES' => 'San Diego Padres',
                    'GIANTS' => 'San Francisco Giants',
                    'MARINERS' => 'Seattle Mariners',
                    'CARDINALS' => 'St. Louis Cardinals',
                    'BAY RAYS' => 'Tampa Bay Rays',
                    'RANGERS' => 'Texas Rangers',
                    'BLUE JAYS' => 'Toronto Blue Jays',
                    'WASHINGTON NATIONALS' => 'Washington Nationals'
                );

            case 'NFL':
                return array(
                    'CARDINALS' => 'Arizona Cardinals',
                    'RAVENS' => 'Baltimore Ravens',
                    'FALCONS' => 'Atlanta Falcons',
                    'BILLS' => 'Atlanta Bills',
                    'PANTHERS' => 'Carolina Panthers',
                    'BENGALS' => 'Cincinnati Bengals',
                    'BEARS' => 'Chicago Bears',
                    'BROWNS' => 'Cleveland Browns',
                    'COWBOYS' => 'Dallas Cowboys',
                    'BRONCOS' => 'Denver Broncos',
                    'LIONS' => 'Detroit Lions',
                    'TEXANS' => 'Houston Texans',
                    'PACKERS' => 'Green Bay Packers',
                    'COLTS' => 'Indianapolis Colts',
                    'RAMS' => 'Los Angeles Rams',
                    'JAGUARS' => 'Jacksonville Jaguars',
                    'VIKINGS' => 'Minnesota Vikings',
                    'CHIEFS' => 'Kansas City Chiefs',
                    'SAINTS' => 'New Orleans Saints',
                    'RAIDERS' => 'Las Vegas Raiders',
                    'GIANTS' => 'New York Giants',
                    'CHARGERS' => 'Los Angeles Chargers',
                    'EAGLES' => 'Philadelphia Eagles',
                    'DOLPHINS' => 'Miami Dolphins',
                    '49ERS' => 'San Francisco 49ers',
                    'PATRIOTS' => 'New England Patriots',
                    'SEAHAWKS' => 'Seattle Seahawks',
                    'JETS' => 'New York Jets',
                    'BUCCANEERS' => 'Tampa Bay Buccaneers',
                    'STEELERS' => 'Pittsburgh Steelers',
                    'COMMANDERS' => 'Washington Commanders',
                    'TITANS' => 'Tennessee Titans',
                );

            default:
                throw new \Exeception("Unknown league '$league'");
        }

    }

}