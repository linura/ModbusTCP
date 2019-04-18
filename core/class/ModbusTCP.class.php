<?php
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class ModbusTCP extends eqLogic {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

/*	public static function cron() {
		if (!self::deamonRunning()) {
			self::runDeamon();
		}
	}
*/
	public static function deamon_start() {
		self::deamon_stop();
		$eqLogics = eqLogic::byType('ModbusTCP');
		foreach ($eqLogics as $ModbusTCP) {
			if ($ModbusTCP->getIsEnable()) {
				$ModbusTCP_ip = $ModbusTCP->getConfiguration('addr');
				$ModbusTCP_port = $ModbusTCP->getConfiguration('port');
				if (!isset($ModbusTCP_testIP[$ModbusTCP_ip.$ModbusTCP_port])){
					$ModbusTCP_testIP[$ModbusTCP_ip.$ModbusTCP_port]=1;
					$ModbusTCP_polling = config::byKey('polling', 'ModbusTCP');
					if ($ModbusTCP_polling==''){
						$ModbusTCP_polling='0';
					}
//					$ModbusTCP_polling = $ModbusTCP->getConfiguration('polling');
					$request='-h '.$ModbusTCP_ip.' -p '.$ModbusTCP_port.' --polling='.$ModbusTCP_polling.' --loglevel='.log::convertLogLevel(log::getLogLevel('ModbusTCP'));
					$ModbusTCP_path = realpath(dirname(__FILE__) . '/../../ressources');
					$cmd = 'nice -n 19 /usr/bin/python ' . $ModbusTCP_path . '/ModbusTCP_master.py ' . $request;
					log::add('ModbusTCP', 'debug', 'Lancement démon ModbusTCP : ' . $cmd);
					$result = exec('nohup ' . $cmd . ' >> ' . log::getPathToLog('ModbusTCP') . ' 2>&1 &');
					if (strpos(strtolower($result), 'error') !== false || strpos(strtolower($result), 'traceback') !== false) {
						log::add('ModbusTCP', 'error', $result);
						return false;
					}
					sleep(2);
					if (!self::deamonRunning()) {
						sleep(10);
						if (!self::deamonRunning()) {
							log::add('ModbusTCP', 'error', 'Impossible de lancer le démon ModbusTCP', 'unableStartDeamon');
							return false;
						}
					}
					message::removeAll('ModbusTCP', 'unableStartDeamon');
					log::add('ModbusTCP', 'info', 'Démon ModbusTCP lancé');
				}
			}
		}
	}

	public static function deamon_info() {
		$return = array();
		$return['log'] = 'ModbusTCP';
		$return['state'] = 'nok';
		$result = exec("ps -eo pid,command | grep 'ModbusTCP_master.py' | grep -v grep | awk '{print $1}'");
		if ($result != 0) {
			$return['state'] = 'ok';
		}
		$return['launchable'] = 'ok';
		return $return;
	}

	public static function deamonRunning() {
		$result = exec("ps -eo pid,command | grep 'ModbusTCP_master.py' | grep -v grep | awk '{print $1}'");
		if ($result == 0) {
			return false;
		}
		return true;
	}

	public static function deamon_stop() {
		if (!self::deamonRunning()) {
			return true;
		}
		$pid = exec("ps -eo pid,command | grep 'ModbusTCP_master.py' | grep -v grep | awk '{print $1}'");
		$pid2 = $pid;
		while ($pid){
			log::add('ModbusTCP', 'debug', 'Démon en cours :'.$pid);
			exec('kill ' . $pid);
			$pid = exec("ps -eo pid,command | grep 'ModbusTCP_master.py' | grep -v grep | awk '{print $1}'");
			$retry = 0;
			while ($pid==$pid2) {
				$pid = exec("ps -eo pid,command | grep 'ModbusTCP_master.py' | grep -v grep | awk '{print $1}'");
				$retry++;
				if ($retry > 10) {
					$pid = false;
				} else {
					sleep(1);
				}
			}
			$pid2=$pid;
		}
		return self::deamonRunning();
	}

	/*     * *********************Methode d'instance************************* */

	public function postInsert() {
	}
}

class ModbusTCPCmd extends cmd {
	/*     * *************************Attributs****************************** */


	/*     * ***********************Methode static*************************** */


	/*     * *********************Methode d'instance************************* */

	public function preSave() {
		if ($this->getConfiguration('request') == '') {
			//throw new Exception(__('La requete ne peut etre vide',__FILE__));
		}
	}

    public function execute($_options = null) {
    	$ModbusTCP = $this->getEqLogic();
        $ModbusTCP_ip = $ModbusTCP->getConfiguration('addr');
		$ModbusTCP_port = $ModbusTCP->getConfiguration('port');
		$ModbusTCP_port = $ModbusTCP->getConfiguration('port');
		$ModbusTCP_location = $this->getConfiguration('location');
		$ModbusTCP_path = realpath(dirname(__FILE__) . '/../../ressources');
		$response = true;
		$ModbusTCP_nomobjet=$ModbusTCP->getConfiguration('nomobjet');
		log::add('ModbusTCP', 'debug',$ModbusTCP->getName());
		if ($this->type == 'action') {
			$value="";
			try {
				$type_input='--wsr=';
				switch ($this->subType) {
					case 'message':
						$value = urlencode(str_replace('#message#', $_options['message'], $this->getConfiguration('request')));
						break;
					case 'slider':
						$value = str_replace('#slider#', $_options['slider'], $this->getConfiguration('request'));
						break;
					default:
						$value=$this->getConfiguration('request');
					break;
                }
				$Trame='/usr/bin/python ' . $ModbusTCP_path . '/ModbusTCP_write.py -h '.$ModbusTCP_ip.' -p '.$ModbusTCP_port.' --nom='.$ModbusTCP_nomobjet.' '. $type_input . ''.$ModbusTCP_location.' --value='.$value.' 2>&1';
				log::add('ModbusTCP', 'debug', 'Debut de l\'action '.$Trame);
				$result = shell_exec($Trame);
				return true;
			} catch (Exception $e)  {
				// 404
				log::add('ModbusTCP', 'error', 'valeur '.$this->getConfiguration('id').': ' . $e->getMessage());
				return false;
			}
		}else{
			return $this->getValue();
		}
	}
    /*     * **********************Getteur Setteur*************************** */
}
?>