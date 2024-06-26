#!/usr/bin/python3
# tab-width: 4

# pkc-motion multicasts a motion_enable command to all PiKrellCams on a LAN
# to turn on or off motion detection.

import sys
import socket
import time

# The PiKrellCam multicast group IP and port number is fixed:
PKC_MULTICAST_GROUP = '225.0.0.55'
PKC_MULTICAST_PORT  = 22555

# Open UDP multicast socket to write to.
send_socket = socket.socket(socket.AF_INET, socket.SOCK_DGRAM, socket.IPPROTO_UDP)
send_socket.setsockopt(socket.IPPROTO_IP, socket.IP_MULTICAST_TTL, 2)

# UDP transmissions are unreliable.  So repeat sending the motion_enable
# command multiple times to ensure all PiKrellCams will receive it.
# Set a fraction of a second delay between repeat sends.
# The send will use a fixed message id number > 0 so that each receiving
# PiKrellCam will execute the first motion_enable it receives and then
# ignore any following repeated commands it receives.

repeat = 4
delay = .05

def usage():
	print("usage: pkc-motion {host|host-list|all} <on|off>")
	print("    Multicast a motion_enable command to PiKrellCams on a LAN.")
	print("    If host, host-list or all is not specified, all is assumed.")
	print("    host-list is a comma separated list of hosts.")
	print("    on or off must be given.")
	sys.exit()

argc = len(sys.argv)
if argc == 1:
	usage()
if argc == 2:
	hosts = "all"
	onoff = sys.argv[1]
if argc == 3:
	hosts = sys.argv[1]
	onoff = sys.argv[2]
if onoff == "on":
	msg_id = 1
elif onoff == "off":
	msg_id = 2
else:
	print('Bad arg: ' + onoff)
	usage()

hostname = socket.gethostname()

for x in range(1, repeat + 1):
	msg = "%s:%d %s command @motion_enable %s" % (hostname, msg_id, hosts, onoff)
#	print(msg)
	send_socket.sendto(msg.encode(), (PKC_MULTICAST_GROUP, PKC_MULTICAST_PORT))
	time.sleep(delay)

