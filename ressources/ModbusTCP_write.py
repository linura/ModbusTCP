#!/usr/bin/python
# -*- coding: utf-8 -*-
 
# Client UDP conçu pour communiquer avec un serveur UDP
 
import socket
import time
import sys
import getopt
import os
import subprocess

buf=1024
#port=8000
#adresse=('192.168.0.158',port)
#adresse2=('',port)
 
##############################################################################
#if __name__ == '__main__':

try:
	opts, args = getopt.getopt(sys.argv[1:], "h:p:P", ["help","wsc=","wsr=","nom=","value="])
except getopt.GetoptError, err:
	print str(err)
	usage()
	sys.exit()

for o, a in opts:
	if o == "-h":
		host = str(a)
	elif o == "-p":
		port = int(a)
	elif o in ("-h", "--help"):
		usage()
		sys.exit()
	elif o == "--wsr":
		wsr = str(a)
	elif o == "--nom":
		message = str(a)
	elif o == "--value":
		value = str(a)
	else:
		usage()
		sys.exit()    

message+=","
message+=wsr
message+=","
message+=value
message=message.replace("+"," ")

pipe_name = '/tmp/ModbusTCP'+host+str(port)
pipeout = os.open(pipe_name, os.O_WRONLY)
os.write(pipeout,message)
os.close(pipeout)
