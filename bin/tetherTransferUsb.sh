#!/bin/bash
camera_port="$1"
output_folder="$2"       
filenamepattern="$3"   # "%Y%m%d-%H%M%S-%04n.%C"
now=$(date +'%m-%d-%Y.%H.%M.%S')   # create now variable with date values

# killall process with gphoto to avoid error on device busy problem with claim device
# https://askubuntu.com/questions/993876/gphoto2-could-not-claim-the-usb-device
#pkill -f gphoto2
#pkill -f gphoto

cur_dir=$(pwd)
cd $output_folder
gphoto2 --capture-tethered --keep --port $camera_port --hook-script=$cur_dir/bin/tetherHook.sh --filename "$filenamepattern"
cd $cur_dir