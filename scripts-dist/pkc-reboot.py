#!/usr/bin/python3
# tab-width: 4

# pkc-reboot multicasts a reboot command to selected hosts running
# PiKrellCam on a LAN
# Uses:
#   Reboot Pis running PiKrellCam from a desktop.
#   If the internet connection is lost to a Pi running PiKrellCam, it is
#   possible a multicast can still get through.  It has worked for me.

import sys
import socket
import time

# The PiKrellCam multicast group IP and port number is fixed:
PKC_MULTICAST_GROUP = '225.0.0.55'
PKC_MULTICAST_PORT  = 22555

# Open UDP multicast socket to write to.
send_socket = socket.socket(socket.AF_INET, socket.SOCK_DGRAM, socket.IPPROTO_UDP)
send_socket.setsockopt(socket.IPPROTO_IP, socket.IP_MULTICAST_TTL, 2)

# UDP transmissions are unreliable.  So repeat sending the reboot
# command multiple times to ensure all PiKrellCams will receive it.
# Set a fraction of a second delay between repeat sends.
# The sends will use a fixed message id number > 0 so that each receiving
# PiKrellCam will accept the first reboot command it receives per id
# number and then ignore any following repeated commands it receives for
# that id number.

repeat = 4
delay = .05

def usage():
	print("usage: pkc-reboot <host|host-list|all>")
	print("Multicast a reboot command to PiKrellCams on a LAN.")
	print("    host, host-list or all must be given.")
	print("    host-list is a comma separated list of hosts.")
	sys.exit()

argc = len(sys.argv)
if argc == 1:
	usage()
elif argc == 2:
	hosts = sys.argv[1]
else:
	print('Bad number of args')
	usage()

hostname = socket.gethostname()

msg_id = 1
for x in range(0, repeat):
	msg = "%s:%d %s command @reboot" % (hostname, msg_id, hosts)
#	print(msg)
	send_socket.sendto(msg.encode(), (PKC_MULTICAST_GROUP, PKC_MULTICAST_PORT))
	time.sleep(delay)


# PiKrellCam needs a confirming reboot command within 10 seconds.
# Change the id number so the second reboot command will be accepted.

time.sleep(1)
msg_id = 2
for x in range(0, repeat):
	msg = "%s:%d %s command @reboot" % (hostname, msg_id, hosts)
#	print(msg)
	send_socket.sendto(msg.encode(), (PKC_MULTICAST_GROUP, PKC_MULTICAST_PORT))
	time.sleep(delay)
