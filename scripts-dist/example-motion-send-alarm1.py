#!/usr/bin/python3
# tab-width: 4

# Edit this script to set the "to_hosts" line below.
#
# This is an example on_motion_begin script that will send an alarm message
# to selected hosts if motion is detected.
#
# Set this script to be a PiKrellCam on_motion_begin script by copying to
# scripts/motion-send-alarm and editing on_motion_begin in pikrellcam.conf:
#   on_motion_begin $C/motion-send-alarm
# Also have pkc-alarm running on the to_hosts you configure here.
#
# With this script only one alarm is sent per motion detect video.
# See example-motion-send-alarm2 for a different approach.

import socket
import time

# Edit to_hosts to select hosts you want to send alarms to.
to_hosts = "gkrellm4,rpi0,rpi1"

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

msg_id = 1
for x in range(0, repeat):
    msg = "%s:%d %s message alarm" % (from_host, msg_id, to_hosts)
#    print(msg)
    send_socket.sendto(msg.encode(), (PKC_MULTICAST_GROUP, PKC_MULTICAST_PORT))
    time.sleep(delay)
