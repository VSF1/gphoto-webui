#!/usr/bin/env bash

sudo systemctl start rsync 
ipaddress=$(sudo nmap -sn 192.168.10.0/24 | grep -B 2 "5C:62:8B:D7:22:20" | head -n 1 | cut -d " " -f 5)

echo "Found $ipaddress"
localip=$(hostname -I | cut -d " " -f 1)

ssh vitor@$ipaddress 'cd /home/vitor/gphoto-webui-master/ && bin/pushFolderRsync.sh images -o '$localip'::images'