#!/usr/bin/env bash
#
#########################################################################################
#########################################################################################
#
# SCRIPT:       syncFolder.bash
# AUTHOR:       Diego Aurino da Silva
# DATE:         February 16, 2018
# REV:          1.0
# LICENSE:      MIT (https://github.com/diegoaurino/bashScripts/blob/master/LICENSE)
#
# PLATFORM:     WSL or GNU/Linux
#
# PURPOSE:      small script to sync left-to-right changes from two folders
#               under WSL or GNU/Linux (requires inotify-tools)
#
#########################################################################################
#########################################################################################

##################
# GENERAL SETTINGS
##################

bold=$(tput bold)
normal=$(tput sgr0)

src=""
dst="$2"

##################
# OPTIONS SECTION
##################

if [ $# -eq 0 ]
then
    printf "\n%s\t\t%s\n\n" "Use ${bold}-h${normal} for help."
     exit 1
else
    while getopts ":h" option
    do
        case ${option} in
            h )
                printf "\n%s\t\t%s\n\n" "Usage:   ./syncFolder.bash ${bold}/origin/folder${normal} -o ${bold}/destination/folder${normal}"            
                exit 0
            ;;
            \? )
                printf "\n%s\n\n" "${bold}Invalid Option for${normal} $(basename $0)" 1>&2
                exit 1
            ;;
        esac    
    done

    shift $((OPTIND -1))
    src=$1
    shift
    while getopts ":o:" option
    do
        case ${option} in  
            o )
                dst=$OPTARG
                printf "\n%s\n\n" "The following folders will be left-right synced:"
                printf "\tOrigin:\t\t\t%s\n" "${bold}$src${normal}"
                printf "\tDestination:\t\t%s\n\n" "${bold}$dst${normal}"
            ;;
            \? )
                printf "\n%s\n\n" "${bold}Invalid Option for${normal} $(basename $0): -$OPTARG." 1>&2
                exit 1
            ;;
            : )
                printf "\n%s\n\n" "${bold}The option${normal} -$OPTARG requires a directory as argument." 1>&2
                exit 1
            ;;
            * )
                printf "\n%s\n\n" "${bold}Unkown option for${normal} $(basename $0): -$OPTARG." 1>&2
                exit 1
            ;;
        esac    
    done
shift $((OPTIND -1))
fi

#sudo sshfs -o allow_other,default_permissions vitor@tetherpi:~/gphoto-webui-master/images/ $src

##################
# SYNC SECTION
##################
echo "Watching $src and $dst"    
# while inotifywait -r -e modify,create,delete $src
while : 
do  
    SECONDS=0
    rsync -aWq $src $dst --no-compress --progress --remove-source-files --filter='P .git' --filter='- *.md5' --filter='- thumbs' --filter='~*'
    elapsed=$SECONDS
    if [ "$elapsed" -ge 4 ]; then 
        echo "transfer took $elapsed seconds"
    fi
    sleep 2
done
