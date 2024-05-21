#!/usr/bin/env bash


localip=$(hostname -I | cut -d " " -f 1)
localnet=$(hostname -I | cut -d "." -f 1,2,3)
sudo systemctl start rsync 
ipAddress=$(sudo nmap -sn $localnet.0/24 | grep -B 2 "5C:62:8B:D7:22:20" | head -n 1 | cut -d " " -f 5)
apAddress=$(sudo nmap -sn $localnet.0/24 | grep -B 2 "94:83:C4:31:47:E6" | head -n 1 | cut -d " " -f 5)

echo "IP ADDRESS: $localip"
echo "LOCAL NETWORK: $localnet.0/24"
if [ ! -z "$ipAddress" ]; then
    echo "TetherPi: $ipAddress"
    ssh vitor@$ipAddress 'cd /home/vitor/gphoto-webui-master/ && bin/pushFolderRsync.sh images -o '$localip'::images'
    exit 0
else 
    echo "TetherPi: Not Found"
fi

if [ ! -z "$apAddress" ]; then
    echo "TetherAP: $apAddress"
    #ssh root@$apAddress 'cd gphoto-webui/ && bin/pushFolderRsync.sh images -o '$localip'::images'
    ssh root@$apAddress 'cd gphoto-webui/ && bin/pushFolderRsync.sh /tmp/mountd/disk1_part1 -o '$localip'::images'
    #ssh root@$apAddress 'cd gphoto-webui/ && mkdir -p /tmp/gphoto2 && bin/pushFolderRsync.sh /tmp/gphoto2/ -o '$localip'::images'
    exit 0
else 
    apAddress="192.168.8.1"
    echo "TetherAP: Not Found, forcing $apAddress"
    #ssh root@$apAddress 'cd gphoto-webui/ && bin/pushFolderRsync.sh images -o '$localip'::images'
    ssh root@$apAddress 'cd gphoto-webui/ && bin/pushFolderRsync.sh /tmp/mountd/disk1_part1 -o '$localip'::images'
    #ssh root@$apAddress 'cd gphoto-webui/ && mkdir -p /tmp/gphoto2 && bin/pushFolderRsync.sh /tmp/gphoto2/ -o '$localip'::images'
fi
