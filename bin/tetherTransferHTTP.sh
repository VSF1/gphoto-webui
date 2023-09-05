#!/bin/bash

. "$(dirname "$0")/common.sh"

store_photos_in=$(realpath "backup")       # select where create subfolders
delete_remote_files=0
listen_port=8001
sleep_time=-1
add_to_darktable=0

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
                sleep_time=$2
                shift; # skip value
                ;;
            -a | --add-darktable)
                add_to_darktable=1
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

cleanup() {
  "${DBUS_SEND}" --type=method_call --dest=org.darktable.service /darktable org.darktable.service.Remote.Lua string:"require('darktable').print('stopping to watch \`${store_photos_in}\'')"
}

parse_params "$@"

if [ ${#args[@]} -ne 1 ]; then
  echo "This script watches a tetherpi device for new images and imports them"
  echo "Usage: $0 <tetherpi|ipaddress>"
  exit 1
fi
#echo storing photo in $store_photos_in

DBUS_SEND=$(which dbus-send)
if [ $? -ne 0 ]; then
  echo "can't find 'dbus-send' in PATH"
  exit 1
fi

HAVE_LUA=$("${DBUS_SEND}" --print-reply --type=method_call --dest=org.darktable.service /darktable org.freedesktop.DBus.Properties.Get string:org.darktable.service.Remote string:LuaEnabled 2>/dev/null)
if [ $? -ne 0 ] && [ $add_to_darktable -ne 0 ]; then
  echo "darktable isn't running or DBUS isn't working properly"
  exit 1
fi

echo "${HAVE_LUA}" | grep "true$" >/dev/null
HAVE_LUA=$?

if [ ${HAVE_LUA} -eq 0 ]; then
  echo "Using Lua to load images, no error handling but uninterrupted workflow"
  "${DBUS_SEND}" --type=method_call --dest=org.darktable.service /darktable org.darktable.service.Remote.Lua string:"require('darktable').print('watching \`${store_photos_in}\'')"
  trap cleanup INT
  trap "echo; echo clean up done. bye" EXIT
else
  echo "darktable doesn't seem to support Lua, loading images directly. This results in better error handling but might interrupt the workflow"
fi

tetherpi_dev="${args[0]}:$listen_port"

echo "Checking $tetherpi_dev"
found_files_last_loop=0
while : 
do
    found_files=0
    lines=$(curl -s "http://$tetherpi_dev/service.php?action=getImages" | jq -r '.[] | "\(.name);\(.md5)"')
    while read object; do 
        # echo "$object"
        arrIN=(${object//;/ })
        fname=${arrIN[0]}
        remotemd5=${arrIN[1]}
        if [ -z "$fname" ]; then
            continue;
        fi
        found_files+=1;
        if test -f "$store_photos_in/$fname"; then
            localmd5=($(md5sum "$store_photos_in/$fname"))
            echo "MD5 verification - Remote: $remotemd5 Local: $localmd5"
            if [ "$localmd5" = "$remotemd5" ]; then
                echo "No changes found, skipping!"
            else
                echo "Files are different - $fname"
                if [ $delete_remote_files -eq 1 ]; then
                    echo "Deleting local version"
                    rm "$store_photos_in/$fname"
                fi
            fi
        else
            SECONDS=0 
            curl -s "http://$tetherpi_dev/service.php?action=getImage&file=$fname" -o "$store_photos_in/$fname"
            echo "Downloading $fname ($remotemd5) took $SECONDS seconds"
            if [ $add_to_darktable -eq 1 ] ; then
                if [ ${HAVE_LUA} -eq 0 ]; then
                    #echo "'${fname}' added"
                    "${DBUS_SEND}" --type=method_call --dest=org.darktable.service /darktable org.darktable.service.Remote.Lua string:"local dt = require('darktable') dt.database.import('${store_photos_in}/${fname}') dt.print('a new image was added')"
                else
                    ID=$("${DBUS_SEND}" --print-reply --type=method_call --dest=org.darktable.service /darktable org.darktable.service.Remote.Open string:"${store_photos_in}/${fname}" | tail --lines 1 | sed 's/.* //')
                    if [ "${ID}" -eq 0 ]; then
                        # TODO: maybe try to wait a few seconds and retry? Not sure if that is needed.
                        echo "'${fname}' couldn't be added"
                    else
                        echo "'${fname}' added with id ${ID}"
                    fi
                fi
            fi        
            localmd5=($(md5sum "$store_photos_in/$fname"))
            # echo "MD5 verification - Remote: $remotemd5 Local: $localmd5"
            if [ "$localmd5" = "$remotemd5" ]; then
                # echo "File was successfully transfered"
                if [ $delete_remote_files -eq 1 ]; then
                   # echo "Now deleting remote file"
                    curl -s "http://$tetherpi_dev/service.php?action=deleteFile&file=$fname" > /dev/null &
                fi
            else
                echo "!!!WARNING!!! Transfered file $fname looks different than remote"
                if [ $delete_remote_files -eq 1 ]; then
                    echo "Deleting local version"
                    rm "$store_photos_in/$fname"
                fi
            fi
        fi
    done <<< "$lines"   
    #echo "found files now $found_files , last loop $found_files_last_loop"
    if [ $found_files -eq 0 ] && [ $found_files_last_loop -eq 1 ]; then
        echo "No new files were found to transfer"
        found_files_last_loop=0
    else
        if [ $found_files -gt 0 ]; then
            found_files_last_loop=1
            echo "Transfer completed"
        fi
    fi
    
    if [ $sleep_time -eq -1 ]; then 
        break
    fi
    if [ $found_files_last_loop -eq 0 ]; then
        sleep $sleep_time
    fi
done