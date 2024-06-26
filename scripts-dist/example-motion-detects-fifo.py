#!/usr/bin/python3
# tab-width: 4

# This is an example of an external script that will read all motion detects
# from PiKrellCam whether or not motion videos are enabled.
#
# Use it a starting point for a motion detect front end application that is
# independent of motion video enabled state.
#
# Run this script from a terminal on a Pi running PiKrellCam and watch for
# motion detects to be printed.
#
# This script can be terminated with a terminal ^C or by sending an "off"
# command to PiKrellCam:
#    echo "motion_detects_fifo_enable off" > /home/pi/pikrellcam/www/FIFO
#

import os
import sys
import signal
import select
import time


# Python 3
from io import StringIO as _StringIO
def StringIO(initial_value=b''):
	"""Take bytes as input but return a unicode IO object"""
	return _StringIO(initial_value.decode())

# Turn on pikrellcam motion detects writing to the motion_detects_FIFO
#
os.system('echo "motion_detects_fifo_enable on" > /home/pi/pikrellcam/www/FIFO')

# If ^C interrupted, send "off" to the command FIFO
# If external program sends "off", then this script will get an <off> tag
# read from the motion_detects_FIFO and exit.
#
def signal_handler(signum, frame):
	print('  Turning off motion_detects_fifo_enable')
	os.system('echo "motion_detects_fifo_enable off" > /home/pi/pikrellcam/www/FIFO')
	sys.exit(0)

signal.signal(signal.SIGINT, signal_handler)


motion_detects_FIFO = '/home/pi/pikrellcam/www/motion_detects_FIFO'
detects_fifo = os.open(motion_detects_FIFO, os.O_RDONLY | os.O_NONBLOCK)

bufferSize = 1024
state = 'wait'

while True:
	# Wait until there is data to read from the fifo. May be multiple lines.
	#
	select.select([detects_fifo],[],[detects_fifo])
	data = os.read(detects_fifo, bufferSize)

	# Split the data into lines and process motion type lines between
	# <motion>...</motion> tags
	# If a motion detect has external or audio, there may not be a frame vector.
	# A motion detect with actual motion always has an overall 'f' frame vector.
	# If there is an 'f' frame vector, there can also be one or more region
	#   vectors only, or a burst vector only, or both burst and one or more
	#   region vectors.  If you care only about overall frame motion, look
	#   for 'f' frame vectors.
	#
	lines = StringIO(data)
	for line in lines.readlines():
		line = line.strip('\n')
		print("FIFO read: " + line)

		# If some program sends a "motion_detects_fifo_enable off" to the
		# command FIFO, an <off> tag will be written to the motion_detects_FIFO.
		#
		if (line.find('<off>') == 0):
			print("    detected motion_detects_fifo_enable off - exiting.")
			sys.exit(1)
		elif (line.find('<motion') == 0):
			state = 'motion'
			# <motion t >  where t is system time with .1 second precision
			m, t, b = line.split()
			print("    motion detected - system time " + t + " => " + time.asctime( time.localtime( int(float(t)) ) ))
		elif (line.find('</motion>') == 0):
			print("")
			state = 'wait'
		elif (state == 'motion'):
			if (line[0] == "f"):
				r, x, y, dx, dy, mag, count = line.split()
				print("    motion vector - frame:" + " mag=" + mag + " count=" + count)
			elif (line[0].isdigit()):
				r, x, y, dx, dy, mag, count = line.split()
				print("    motion vector - region " + line[0] + ": mag=" + mag + " count=" + count)
			elif (line[0] == "b"):
				b, count = line.split()
				print("    burst detect - count=" + count)
			elif (line[0] == "e"):
				e, code = line.split()
				print("    external trigger - code: " + code)
			elif (line[0] == "a"):
				a, level = line.split()
				print("    audio trigger - level=" + level)

