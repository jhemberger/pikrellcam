#!/usr/bin/python3

# Listen for traffic on the PiKrellCam multicast group and print all lines.
# Run this in a terminal for debugging multicast traffic.

import socket
import struct

PKC_MULTICAST_GROUP_IP = '225.0.0.55'
PKC_MULTICAST_GROUP_PORT = 22555

sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM, socket.IPPROTO_UDP)
sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
sock.bind((PKC_MULTICAST_GROUP_IP, PKC_MULTICAST_GROUP_PORT))
mreq = struct.pack("4sl", socket.inet_aton(PKC_MULTICAST_GROUP_IP), socket.INADDR_ANY)

sock.setsockopt(socket.IPPROTO_IP, socket.IP_ADD_MEMBERSHIP, mreq)

while True:
	print(sock.recv(1024).decode())
