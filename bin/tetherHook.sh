#!/bin/bash
FILENAME=$(basename ${ARGUMENT})
FILENAME="./images/${FILENAME}"
# for every pic taken
if [[ $FILENAME =~ .+\.[jpg|JPG] ]];then
  #if [ *$ACTION* = *Saving* ]; then
  # do a echo of the file name
  echo "Download: $FILENAME"
  # shot the picture in the current geeqie open
  # cp $ARGUMENT latest.jpg
#  /usr/bin/geeqie --remote "$ARGUMENT" &
  mv "$ARGUMENT" "$FILENAME"
  md5sum "$FILENAME" > "$FILENAME.md5"
fi

if [[ $FILENAME =~ .+\.[arw|ARW|mrw|MRW] ]];then
  #if [ *$ACTION* = *Saving* ]; then
  # do a echo of the file name
  echo "Download: $FILENAME"
  # shot the picture in the current geeqie open
  # cp $ARGUMENT latest.arw
#  /usr/bin/geeqie --remote "$ARGUMENT" &
  mv "$ARGUMENT" "$FILENAME"
  md5sum "$FILENAME" > "$FILENAME.md5"
fi
 
