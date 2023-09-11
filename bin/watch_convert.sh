#!/bin/bash

# MIT License
#
# Copyright (c) 2016-2017 Tobias Ellinghaus
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
  recursive=0

  while :; do
    case "${1-}" in
    -h | --help) usage ;;
    -v | --verbose) set -x ;;
    --no-color) NO_COLOR=1 ;;
    -r | --recursice) recursive=1 ;; # example flag
    -?*) die "Unknown option: $1" ;;
    *) break ;;
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
  echo "This script watches a folder for new images and imports them into a running instance of darktable"
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

"${INOTIFYWAIT}" --monitor "${BASE_FOLDER}" --event "create, move_to" --exclude ".*\.xmp$" |
  while read -r path event file; do
      echo "${event} '${file}' added, generating full size and thumbs"
      outputfile=$(basename $file)
      $(gm convert -quality 60 images/$file images/fs/$outputfile.jpg)
      $(gm convert -quality 60 images/fs/$file images/thumbs/$outputfile.jpg)
  done
