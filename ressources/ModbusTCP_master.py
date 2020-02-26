#!/usr/bin/env python
# -*- coding: utf-8 -*-

# ModbusTCP_thread
# exit with ctrl+c

import socket
import time
import logging
import sys
import getopt
import os
import subprocess
import threading

log_level = "error"
polling = 0

try:
	opts, args = getopt.getopt(sys.argv[1:], "h:p:P", ["help","polling=","loglevel="])
except getopt.GetoptError, err:
	print str(err)
	sys.exit()

for o, a in opts:
	if o == "-h":
		host = str(a)
	elif o == "-p":
		port = int(a)
	elif o == "--polling":
		polling = int(a)
	elif o == "--loglevel":
		log_level = str(a)
	elif o in ("-h", "--help"):
		usage()
		sys.exit()

jeeModbusTCP = os.path.abspath(os.path.join(os.path.dirname(__file__), '../core/php/jeeModbusTCP.php')) 
tempscourant=time.time()

class ThreadReception(threading.Thread):
	tempsreception=time.time()
	"""objet thread gérant la réception des messages"""
	def __init__(self, conn):
		threading.Thread.__init__(self)
		self.connexion = conn           # réf. du socket de connexion
	def run(self):
		while 1:
			message_recu = self.connexion.recv(1024)
			tempsreception=time.time()
			logging.debug("Daemon : Reception de : "+message_recu)
			subprocess.Popen(['/usr/bin/php',jeeModbusTCP,'values='+message_recu,'add='+host,'port='+str(port)])

class ThreadEmission(threading.Thread):
	"""objet thread gérant l'émission des messages"""

	def __init__(self, conn,ip,port):
		threading.Thread.__init__(self)
		self.connexion = conn           # réf. du socket de connexion
		self.ip=ip
		self.pipe_name = '/tmp/ModbusTCP'+ip+str(port)
		self.port=port

	def run(self):
		time.sleep(10)#on attends 10s
		self.connexion.sendto("Ping",(self.ip,self.port))
		logging.debug("Daemon : Envoi de : Ping")
		if not os.path.exists(self.pipe_name):
			os.mkfifo(self.pipe_name)  
		self.pipein = open(self.pipe_name, 'r')
		while 1:
			message_emis=self.pipein.read()
			if message_emis!='':
				self.connexion.sendto(message_emis,(self.ip,self.port))
				logging.debug("Daemon : Envoi de : "+message_emis)
			if polling>0:
				if time.time()>(ThreadReception.tempsreception+polling*60):
					self.connexion.sendto("Ping",(self.ip,self.port))
					logging.debug("Daemon : Envoi de : Ping")
					ThreadReception.tempsreception=time.time()
			time.sleep(0.01)#on attends 10ms

def convert_log_level(level = 'error'):
	LEVELS = {'debug': logging.DEBUG,
          'info': logging.INFO,
          'notice': logging.WARNING,
          'warning': logging.WARNING,
          'error': logging.ERROR,
          'critical': logging.CRITICAL,
          'none': logging.NOTSET}
	return LEVELS.get(level, logging.NOTSET)

def set_log_level(level = 'error'):
	FORMAT = '[%(asctime)-15s][%(levelname)s] : %(message)s'
	logging.basicConfig(level=convert_log_level(level),format=FORMAT, datefmt="%Y-%m-%d %H:%M:%S")

# Programme principal - Établissement de la connexion :
set_log_level(log_level)
connexion = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)

try:
	connexion.bind(('', port))
except socket.error, msg:
	logging.error("Daemon : La connexion a échoué.")
	logging.error("Daemon : %s"%msg) #permet l'affichage de l'erreur retournee par la connexion
	sys.exit()

logging.debug("Daemon : Polling : "+str(polling))
logging.debug("Daemon : Temps courant : "+str(tempscourant))

logging.debug("Daemon : Connexion établie avec le serveur.")

# Dialogue avec le serveur : on lance deux threads pour gérer
# indépendamment l'émission et la réception des messages :
th_E = ThreadEmission(connexion,host,port)
th_R = ThreadReception(connexion)
th_E.start()
th_R.start()
