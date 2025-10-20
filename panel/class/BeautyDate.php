<?php

class DateBeautyPrint {

    static public function simplePrint(int $from_timestamp, int $to_timestamp = null):string {

        if(is_null($to_timestamp))
            $to_timestamp = time();

        $FromDate = new DateTime(date('Y-m-d H:i:s', $from_timestamp));
        $d = $FromDate->diff(new DateTime( date('Y-m-d H:i:s' , $to_timestamp) ));

        return match (true) {
            $d->y > 0 => $d->y > 1 ? "$d->y years" : "$d->y year",
            $d->m > 0 => $d->m > 1 ? "$d->m months" : "$d->m month",
            $d->d > 0 => $d->d > 1 ? "$d->d days" : "$d->d day",
            $d->h > 0 => $d->h > 1 ? "$d->h hours" : "$d->h hour",
            $d->i > 0 => $d->i > 1 ? "$d->i minutes" : "$d->i minute",
            $d->s > 0 => $d->s > 1 ? "$d->s seconds" : "$d->s second",
            default => '',
        };

    }



    static public function shortPrint(int $from_timestamp, int $to_timestamp = null ):string {

        if(is_null($to_timestamp))
            $to_timestamp = time();

        $FromDate = new DateTime(date('Y-m-d H:i:s', $from_timestamp));
        $d = $FromDate->diff(new DateTime( date('Y-m-d H:i:s' , $to_timestamp) ));

        return match (true) {
            $d->y > 0 =>  "$d->y years" ,
            $d->m > 0 => "$d->m months" ,
            $d->d > 0 =>  "$d->d days" ,
            $d->h > 0 =>  "$d->h hours" ,
            $d->i > 0 => "$d->i mins" ,
            $d->s > 0 => "$d->s secs" ,
            default => '',
        };

    }


}