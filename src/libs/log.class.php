<?php
/**
 * Enregistrement d'evenement comme "Log"
 * @uses Database
 **/
class Log{

	/**
	 * Log d'une action faite par l'ACS/Device
	 * @param String              $serial                  Numero de serie
	 * @param String              $method                  Protocol TR069 appeler
	 * @param Mixed               $param                   Detail de l'action
	 **/
	public static function add($serial, $method, $param = null){
		if(is_array($param)){ $param = implode(', ', $param); } if($param == null){ $param = ''; }
		$sth = Database::prepare("INSERT INTO log (serial_number, ip_client, acs_call, acs_parameter, call_time) VALUES (:serial, :ip, :method, :args, NOW())");
		$sth->bindParam(':serial', $serial, PDO::PARAM_STR);
		$sth->bindParam(':ip', str_replace('81.18.191.163', '', $_SERVER['REMOTE_ADDR']), PDO::PARAM_STR);
		$sth->bindParam(':method', $method, PDO::PARAM_STR);
		$sth->bindParam(':args', $param, PDO::PARAM_STR);
		$sth->execute();
	}

	/**
	 * Log d'une erreur faite par l'ACS/Device
	 * @param String              $serial                  Numero de serie
	 * @param String              $method                  Protocol TR069 appeler
	 * @param Mixed               $param                   Detail de l'action
	 **/
	public static function error($serial, $method, $param = null){
		if(is_array($param)){ $param = implode(', ', $param); } if($param == null){ $param = ''; }
		$sth = Database::prepare("INSERT INTO log_error (serial_number, ip_client, acs_call, acs_parameter, call_time) VALUES (:serial, :ip, :method, :args, NOW())");
		$sth->bindParam(':serial', $serial, PDO::PARAM_STR);
		$sth->bindParam(':ip', $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
		$sth->bindParam(':method', $method, PDO::PARAM_STR);
		$sth->bindParam(':args', $param, PDO::PARAM_STR);
		$sth->execute();
	}

	/**
	 * Debug des enveloppes SOAP
	 * @param String              $input                   XML in
	 * @param String              $output                  XML out
	 **/
	public static function debug($input, $output){
		$sth = Database::prepare("INSERT INTO log_debug (ip, input, output) VALUES (:ip, :input, :output)");
		$sth->bindParam(':ip', $_SERVER["REMOTE_ADDR"], PDO::PARAM_STR);
		$sth->bindParam(':input',  $input,  PDO::PARAM_STR);
		$sth->bindParam(':output', $output, PDO::PARAM_STR);
		$sth->execute();
	}

	/**
	 * Suppression de log
	 **/
	public static function remove(){
		$n_suppr = 100; // Nombre de log minimal a garder

		Database::query("SET SESSION group_concat_max_len = 1000000"); // !important
		$tab = Database::query("SELECT serial_number, GROUP_CONCAT(id) log_id, COUNT(*) nb_enr FROM log GROUP BY serial_number HAVING nb_enr > ".$n_suppr)->fetchAll();
		foreach($tab as $line){
			$line['log_id'] = explode(',', $line['log_id']);
			usort($line['log_id'], function($a, $b){ return $b-$a; });
			if(isset($line['log_id'][$n_suppr]) && $line['log_id'][$n_suppr] > 0){
				Database::query("DELETE FROM log WHERE serial_number = '".$line['serial_number']."' AND id < '".$line['log_id'][$n_suppr]."' AND call_time < SUBDATE(NOW(), INTERVAL 1 MONTH)");
			}
		}
	}

	public static function check_error($serial, $younger_than = null){
		return Database
			::query("SELECT * FROM log_error WHERE serial_number = '".$serial."' ".
				($younger_than? 'AND call_time > SUBDATE(NOW(), INTERVAL '.$younger_than.')' : ''))
			->fetchAll(PDO::FETCH_ASSOC);
	}
}
