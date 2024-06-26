#!/usr/bin/python3
# tab-width: 4

# This is an example on_motion_begin script that reads the file
# /run/pikrellcam/motion-events and prints motion event types as they occur.
# Use it as a start for a custom script.
# Copy this script to say ../scripts/motion_events
# and then in ~/.pikrellcam/pikrellcam.conf, set on_motion_begin to:
#
#	on_motion_begin $C/motion_events $e
#
# This script as is just prints output which can be seen only if pikrellcam
# is run from a terminal.

import sys
import time

filename = '/run/pikrellcam/motion-events'
file = open(filename,'r')

state = 'wait'
event = 1

# sys.argv[1] is the $e value: motion, external or audio
print("on_motion_begin triggered by: " + sys.argv[1])

while 1:
	where = file.tell()
	line = file.readline()
	if not line:
		time.sleep(0.1)
		file.seek(where)
		continue
	line = line.strip('\n')
	if (line.find('<motion') == 0):
		print("  motion event number: " + str(event))
		event = event + 1
		state = 'motion'
	elif (line.find('</motion>') == 0):
		state = 'wait'
	elif (line.find('<end>') == 0):
		sys.exit(1)
	elif (state == 'motion'):
		if (line[0].isdigit()):
			print("    motion direction - region: " + line[0])
		elif (line[0] == "b"):
			b, count = line.split() 
			print("    burst detect - count: " + count)
		elif (line[0] == "e"):
			e, code = line.split() 
			print("    external trigger - code: " + code)
		elif (line[0] == "a"):
			a, level = line.split() 
			print("    audio trigger - level: " + level)
