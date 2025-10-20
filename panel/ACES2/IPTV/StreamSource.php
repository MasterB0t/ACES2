<?php

namespace ACES2\IPTV;

class StreamSource {

    static public function getSourceStats(string $source_url ) {

        $cmd = " timeout 15 ffprobe -v quiet -print_format json ";
        $cmd .=  " -show_entries stream=width,height,codec_name,r_frame_rate,bit_rate,duration -show_entries stream=r_frame_rate ";
        $cmd .= " '$source_url' ";

        $output = shell_exec($cmd);

        $json =  json_decode($output, true);
        if( $output == null || json_last_error() !== JSON_ERROR_NONE) {
            logD($output);
            return null;
        }

        if(!is_array($json['streams'])) {
            logD("Down???");
            logD(print_r($json, true));
            return null;
        }


        $video = [];
        $audio = [];

        foreach( $json['streams'] as $stream ) {
            if( isset($stream['width']) ) {

                //IF IT DON'T GET FRAMES THIS COULD CAUSE EXCEPTION. LET CATCH IT;
                try {
                    $frames = explode("/", $stream['r_frame_rate']);
                    $fps = $frames[0] / $frames[1];
                } catch( \DivisionByZeroError $e) {
                    $fps = 0;
                }

                $video = array(
                    'resolution' => $stream['width'].'x'.$stream['height'],
                    'codec' => strtoupper($stream['codec_name']),
                    'fps' => $fps
                );

            } else {
                $audio = array(
                    'codec' => strtoupper($stream['codec_name']),
                    'bitrate' => $stream['bit_rate']
                );
            }
        }

        return array (
            'video' => $video,
            'audio' => $audio
        );

    }

}