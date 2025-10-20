#!/bin/bash

echo -e "\e[0m"

if [[ $EUID -ne 0 ]]; then
   echo -e "\e[31mThis script must be run as root" 1>&2
   echo -e "\e[0m"
   exit 1
fi

if [ "$1" = 'force' ]; then
	echo  "FORCING STOP...";
	kill -9  $(ps aux | grep 'php' | awk '{print $2}') 2>/dev/null
	kill -9  $(ps aux | grep 'ffmpeg' | awk '{print $2}') 2>/dev/null
fi

systemctl stop aces aces-php-fpm aces-nginx 
sleep 2
systemctl restart aces-php-fpm
systemctl restart aces-nginx
sleep 2
systemctl restart aces

