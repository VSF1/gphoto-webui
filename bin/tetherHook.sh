#!/bin/bash
# for every pic taken
if [[ $ARGUMENT =~ .+\.[jpg|JPG] ]];then
  #if [ *$ACTION* = *Saving* ]; then
  # do a echo of the file name
  echo "Download: $ARGUMENT"
  # shot the picture in the current geeqie open
  # cp $ARGUMENT latest.jpg
#  /usr/bin/geeqie --remote "$ARGUMENT" &
  md5sum "$ARGUMENT" > "$ARGUMENT.md5"
fi

if [[ $ARGUMENT =~ .+\.[arw|ARW|mrw|MRW] ]];then
  #if [ *$ACTION* = *Saving* ]; then
  # do a echo of the file name
  echo "Download: $ARGUMENT"
  # shot the picture in the current geeqie open
  # cp $ARGUMENT latest.arw
#  /usr/bin/geeqie --remote "$ARGUMENT" &
  md5sum "$ARGUMENT" > "$ARGUMENT.md5"
fi
 
