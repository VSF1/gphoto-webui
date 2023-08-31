#!/bin/bash

store_photos_in="./images"       # select where create subfolders
#filenamepattern="%f.%C"
filenamepattern="%Y%m%d-%H%M%S-%04n.%C"
now=$(date +'%m-%d-%Y.%H.%M.%S')   # create now variable with date values

# killall process with gphoto to avoid error on device busy problem with claim device
# https://askubuntu.com/questions/993876/gphoto2-could-not-claim-the-usb-device
pkill -f gphoto2
pkill -f gphoto

gphoto2 --capture-tethered --keep --hook-script=./bin/tether_hook.sh --filename "./images/$filenamepattern"
