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

if (php_sapi_name() != 'cli' || isset($_SERVER['REQUEST_METHOD']) || !isset($_SERVER['argc'])) {
	header("Status: 404 Not Found");
	header('HTTP/1.0 404 Not Found');
	$_SERVER['REDIRECT_STATUS'] = 404;
	echo "<h1>404 Not Found</h1>";
	echo "The page that you have requested could not be found.";
	exit();
}

require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";

if (isset($argv)) {
	foreach ($argv as $arg) {
		$argList = explode('=', $arg);
		if (isset($argList[0]) && isset($argList[1])) {
			$_GET[$argList[0]] = $argList[1];
		}
	}
}
$message = $_GET['values'];
log::add('ModbusTCP', 'debug', 'Evenement : ' . $message);

$values=$_GET['values'];

if($values<>""){//on a recu des valeurs

	$ModbusTCP_all = eqLogic::byTypeAndSearhConfiguration('ModbusTCP',$_GET['add'].'","port":"'.$_GET['port']);//Recherche des équipements avec la bonne adresse ip et le bon port
	if(count($ModbusTCP_all) == 0){
		log::add('ModbusTCP', 'info', 'impossible de trouver le slave ip:'.$_GET['add'].' port:'.$_GET['port']);
		return;
	}
	if ($values=="Pong"){
		log::add('ModbusTCP', 'debug', 'Trame Pong');
		foreach ($ModbusTCP_all as $ModbusTCP) {
			$ModbusTCP->setStatus('lastCommunication', date('Y-m-d H:i:s'));
//			$ModbusTCP->save();
		}
	}else{
		$values_arr=explode(';', $values);
		foreach ($values_arr as $values_inputs) {
			$values_inputs_arr=explode(',',$values_inputs);
			if (count($values_inputs_arr)!=3){//3 éléments dans le tableau : L'objet, Le nom et la valeur
				log::add('ModbusTCP', 'info', 'Trame invalide:' . $values_inputs);
				return;
			} else {
				$Valeur[$values_inputs_arr[0]][$values_inputs_arr[1]]=$values_inputs_arr[2];
			}
		}
		if(count($Valeur)!=0){
			foreach ($ModbusTCP_all as $ModbusTCP) {
				$ModbusTCP_nomobjet=$ModbusTCP->getConfiguration('nomobjet');
				foreach ($ModbusTCP->getCmd('info') as $cmd) {
					$configuration=$cmd->getConfiguration('location');
					if (isset($Valeur[$ModbusTCP_nomobjet][$configuration])){
						$old_value=$cmd->getValue();
						$new_value=$Valeur[$ModbusTCP_nomobjet][$configuration];
						unset($Valeur[$ModbusTCP_nomobjet][$configuration]);
                      	if ($new_value<>$old_value) {
							log::add('ModbusTCP', 'info','Mise à jour de L\'objet :'.$ModbusTCP_nomobjet.' Index :'.$configuration.' Old :'.$old_value.' New :'.$new_value);
							$cmd->event($new_value);
							$cmd->setValue($new_value);
							$cmd->save();
						}
					}
				}
			}
			foreach ($Valeur as $Test =>$result1){
				if(count($result1!=0)){//on n'a pas trouvé tous les objets
					foreach ($result1 as $key => $result){
						log::add('ModbusTCP', 'info','Impossible de trouver L\'objet :'.$Test.' index :'.$key);
					}
				}
			}
		} else{
			log::add('ModbusTCP', 'debug', 'Tableau vide');
		}
	}
}
