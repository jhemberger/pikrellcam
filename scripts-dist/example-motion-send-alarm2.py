#!/usr/bin/python3
# tab-width: 4

# This file should be edited, read the configurable variables below.
#
# This is an example on_motion_begin script that will send an alarm message
# to selected hosts if motion of a configurable magnitude and count
# is detected in a particular motion region.
# It reads /run/pikrellcam/motion-events to get motion events as they occur
# while a motion video is being recorded.
# 
# Set this script to be a PiKrellCam on_motion_begin script by copying to
# scripts/motion-send-alarm and editing on_motion_begin in pikrellcam.conf:
#   on_motion_begin $C/motion-send-alarm
# Also have pkc-alarm running on the to_hosts you configure here.
#
# With this motion detection settings can be low to detect small animals,
# but an alarm sent only if larger magnitude and count motion is detected
# in a region placed around some object of critical interest.
#
# This is a preliminary version of this script and can be improved.
# Check future PiKrellCam releases for any changes that might appear here.
#
import sys
import socket
import time

# Configurable variables
#
# Edit to_hosts to select who to send the alarm to.  On each of these hosts,
#   pkc-alarm should be running in order to receive the alarm.
# Edit motion_region to be the region you want monitored for detection.
#   This can be a region around a door, car, or any region of interest.
# Edit magnitude_limit and count_limit to be the motion vector parameters
#   you want exceeded for an alarm to be sent.  These should >= the values
#   the pikrellcam program has configured for motion detection.
# Edit holdoff to be the minimum seconds between sending alarm messages.
#   It should be greater than the holdoff in pkc-alarm.
#
to_hosts = "gkrellm4,rpi0,rpi1"
motion_region = '3'
magnitude_limit = 12
count_limit = 20
holdoff = 6

# UDP transmissions are unreliable.  So repeat sending the alarm.
# Receiving programs should check the message id and accept the alarm once
# per time interval (about 2 seconds)
#
repeat = 4
delay = .05

from_host = socket.gethostname()


# The PiKrellCam multicast group IP and port number is fixed:
PKC_MULTICAST_GROUP = '225.0.0.55'
PKC_MULTICAST_PORT  = 22555
send_socket = socket.socket(socket.AF_INET, socket.SOCK_DGRAM, socket.IPPROTO_UDP)
send_socket.setsockopt(socket.IPPROTO_IP, socket.IP_MULTICAST_TTL, 2)

filename = '/run/pikrellcam/motion-events'
file = open(filename,'r')

msg_id = 1

state = 'wait'
tsent = 0

while 1:
	where = file.tell()
	line = file.readline()
	if not line:
		time.sleep(0.1)
		file.seek(where)
		continue
	line = line.strip('\n')
#	print('==>' + line)
	if (line.find('<motion') == 0):
		state = 'motion'
	elif (line.find('</motion>') == 0):
		state = 'wait'
	elif (line.find('<end>') == 0):
		sys.exit(1)
	elif (state == 'motion'):
		if (line.startswith(motion_region)):
#			print(line)
			tnow = time.time()
			if (tnow >= tsent + holdoff):
				r, x, y, dx, dy, mag, count = line.split()
				if (int(mag) >= magnitude_limit and int(count) >= count_limit):
					for x in range(0, repeat):
						msg = "%s:%d %s message alarm" % (from_host, msg_id, to_hosts)
#						 print(msg)
						send_socket.sendto(msg.encode(), (PKC_MULTICAST_GROUP, PKC_MULTICAST_PORT))
						time.sleep(delay)
					tsent = tnow
					msg_id = msg_id + 1

