#!/bin/bash

# MIT License
#
# Copyright (c) 2023 Vitor Fonseca
#
# Permission is hereby granted, free of charge, to any person obtaining a copy
# of this software and associated documentation files (the "Software"), to deal
# in the Software without restriction, including without limitation the rights
# to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
# copies of the Software, and to permit persons to whom the Software is
# furnished to do so, subject to the following conditions:
#
# The above copyright notice and this permission notice shall be included in all
# copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
# AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
# SOFTWARE.

. "$(dirname "$0")/common.sh"

hiResImages=0

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
  hiResImages=0

  while :; do
    case "${1-}" in
    -h | --help) 
      usage 
      ;;
    -v | --verbose) 
      set -x 
      ;;
    --no-color) 
      NO_COLOR=1 
      ;;
    --hires-images) 
      hiResImages=1
      msg "Generating High Resolution Images"
      ;;
    -?*) 
      die "Unknown option: $1" 
      ;;
    *) 
      break 
      ;;
    esac
    shift
  done

  args=("$@")

  # check required params and arguments
  [[ ${#args[@]} -eq 0 ]] && die "Missing script arguments"

  return 0
}

parse_params "$@"

if [ ${#args[@]} -ne 1 ]; then
  echo "This script watches a folder for new images and extracts thumbnails from raw files"
  echo "Usage: $0 <folder>"
  exit 1
fi

BASE_FOLDER=$($ReadLink "${args[0]}")

if [ ! -d "${BASE_FOLDER}" ]; then
  echo "error accessing directory '$BASE_FOLDER'"
  exit 1
fi

INOTIFYWAIT=$(which inotifywait)
if [ $? -ne 0 ]; then
  echo "can't find 'inotifywait' in PATH"
  exit 1
fi
if [ recursive == 1 ]; then
  INOTIFYWAIT=$INOTIFYWAIT -r
fi

"${INOTIFYWAIT}" --monitor "${BASE_FOLDER}" --event "create" --event "moved_to" --exclude ".*\.xmp$" |
  while read -r path event srcFile; do
      msg "Event '${event}' for '${srcFile}'"
      SECONDS=0
      srcFullPath="${path}${srcFile}"
      dstFile=${srcFile%.*}
      dstFile=${dstFile##*/}
      dstFullPath="${path}thumbs/${dstFile}.jpg"

      msg "Extracting thumb images from raw ${srcFullPath}"
      exiv2 -pp $srcFullPath | while read -r preview_asc thumb_id format sizepx pixels sizeb bytes_asc; do
        thumb_id="$(cut -d':' -f1 <<< "$thumb_id")"
        thumb_w="$(cut -d'x' -f1 <<< "$sizepx")"
        thumb_h="$(cut -d'x' -f2 <<< "$sizepx")"
        dstTempFullPath=/tmp/$dstFile-preview$thumb_id.jpg
        if [ -f "$dstTempFullPath" ]; then
          echo "Deleting ${dstTempFullPath}" 
          rm $dstTempFullPath
        fi 
        echo "Extracting thumb image nº${thumb_id} size ${sizepx}"
        exiv2 -ep$thumb_id -l /tmp $srcFullPath
        mv $dstTempFullPath $dstFullPath 
      done
      echo "Extraction took $SECONDS seconds."

      if [ "$hiResImages" -eq 1 ]; then     
        msg "Generating high quality jpeg image"
        SECONDS=0
        dstFullPath="${path}fs/${dstFile}.jpg"
        if [ -f "$dstFullPath" ]; then
          rm $dstFullPath
        fi
        echo gm convert -quality 60 $srcFullPath $dstFullPath 
        gm convert -quality 60 $srcFullPath $dstFullPath
        echo "Conversion took $SECONDS seconds."
      fi
  done
