#!/bin/bash

. "$(dirname "$0")/common.sh"

store_photos_in="./backup"       # select where create subfolders
delete_remote_files=0
listen_port=8001
sleep_time=-1

msg() {
  echo >&2 -e "${1-}"
}

die() {
  local msg=$1
  local code=${2-1} # default exit status 1
  msg "$msg"
  exit "$code"
}

parse_params() {
    # default values of variables set from params
    args=() 
    
    while :; do
        case "${1-}" in
            -h | --help) 
                usage ;;
            -v | --verbose) 
                set -x ;;
            -d | --delete_remote_files) 
                delete_remote_files=1
                echo "!!!WARNING!!! Delete remote files is enabled." 
                ;;
            -s | --sleep)
                sleep_time=$2;
                shift; # skip value
                ;;
            --no-color) 
                NO_COLOR=1 
                ;;
            -p | --port)
                listen_port=$2;
                shift; # skip value
                ;;
            -?*) 
                die "Unknown option: $1" 
                ;;
            *)
                args+=("$1") 
                break ;;
        esac
        shift
    done

    # args=("$@")

    # check required params and arguments
    [[ ${#args[@]} -eq 0 ]] && die "Missing script arguments"

    return 0
}

parse_params "$@"

if [ ${#args[@]} -ne 1 ]; then
  echo "This script watches a tetherpi device for new images and imports them"
  echo "Usage: $0 <tetherpi|ipaddress>"
  exit 1
fi

tetherpi_dev="${args[0]}:$listen_port"

echo "Checking $tetherpi_dev"

while : 
do
    curl -s "http://$tetherpi_dev/service.php?action=getImages" | jq -r '.[] | "\(.name);\(.md5)"' | while read object; do
        echo "$object"
        arrIN=(${object//;/ })
        fname=${arrIN[0]}
        remotemd5=${arrIN[1]}
        if test -f "$store_photos_in/$fname"; then
            localmd5=($(md5sum "$store_photos_in/$fname"))
            echo "MD5 verification - Remote: $remotemd5 Local: $localmd5"
            if [ "$localmd5" = "$remotemd5" ]; then
                echo "No changes found, skipping!"
            else
                echo "Files are different"
            fi
        else
            SECONDS=0 
            curl -s "http://$tetherpi_dev/service.php?action=getImage&file=$fname" -o "$store_photos_in/$fname"
            echo "Downloading $fname ($remotemd5) took $SECONDS seconds"
            localmd5=($(md5sum "$store_photos_in/$fname"))
            # echo "MD5 verification - Remote: $remotemd5 Local: $localmd5"
            if [ "$localmd5" = "$remotemd5" ]; then
                echo "File was successfully transfered"
                if [ $delete_remote_files -eq 1 ]; then
                    echo "Now deleting remote file"
                    curl -s "http://$tetherpi_dev/service.php?action=deleteFile&file=$fname" > /dev/null &
                fi
            else
                echo "!!!WARNING!!! Transfered file looks different than remote"
                # rm "$store_photos_in/$fname"
            fi
        fi
    done

    if [ $sleep_time -eq -1 ]; then 
        break
    fi
    sleep $sleep_time
done