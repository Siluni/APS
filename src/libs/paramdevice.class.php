<?php
/**
 * Parametre lie a un "Device"
 * @uses Param, Database, ParameterList
 **/
class ParamDevice extends Param{

	/** @var String               $serial_number           SQL ID (device) */
	public $serial_number;

	/** @var String               $acs_value               Valeur associee */
	public $acs_value;

	/** @var String               $device_value            Derniere valeur connu */
	public $device_value;

	/** @var DateTime             $date_written            Date d'ecriture sur le device */
	public $date_written;

	/** @var DateTime             $date_update             Date de derniere mise a jour ACS */
	public $date_update;


	//------------------------------
	// Constructeur
	//------------------------------
	public static function getByName($serial_number, $name){
		$sth = Database::prepare("
			SELECT p.*, d.*
			FROM param p
			JOIN param_device d ON d.param_id = p.param_id
			WHERE (d.date_written < d.date_update OR YEAR(d.date_written) = 0)
			  AND d.serial_number = :serial AND p.name = :name");
		$sth->bindParam(':serial', $serial_number, PDO::PARAM_STR);
		$sth->bindParam(':name',   $name,          PDO::PARAM_STR);
		$sth->execute();

		$ret = $sth->fetchAll(PDO::FETCH_CLASS, __CLASS__);
		if(count($ret) > 0){ return $ret[0]; }
		throw new Exception('UNKNOW NAME');
	}


	/**
	 * Liste des parametres a mettre a jour sur un Device
	 * @param Int                 $serial_number           SQL ID (device)
	 * @return                                             ParameterList<ParamDevice>
	 * @throws                                             Exception
	 **/
	public static function getParameterToUpdate($serial_number){
		$sth = Database::prepare("
			SELECT p.*, d.*
			FROM param p
			JOIN param_device d ON d.param_id = p.param_id
			WHERE (d.date_written < d.date_update OR YEAR(d.date_written) = 0)
			  AND d.serial_number = :serial AND p.name NOT LIKE '<%' AND FIND_IN_SET('WRITE', p.listen) > 0
			ORDER BY p.level ASC, tkip DESC");
		$sth->bindParam(':serial', $serial_number, PDO::PARAM_STR);
		$sth->execute();

		$ret = $sth->fetchAll(PDO::FETCH_CLASS, __CLASS__);
		return new ParameterList($ret);
	}

	/**
	 * Liste des parametres ou on veut consulter leur valeur
	 * @param Int                 $serial_number           SQL ID (device)
	 * @return                                             ParameterNames<ParamDevice>
	 * @throws                                             Exception
	 **/
	public static function getParameterToRead($serial_number){
		$sth = Database::prepare("
			SELECT p.*, d.* FROM param p
			JOIN param_device d ON d.param_id = p.param_id
			WHERE d.serial_number = :serial
			  AND ((FIND_IN_SET('READ_ONCE', p.listen) > 0 AND d.device_value = '')
			   OR FIND_IN_SET('READ_ALWAYS', p.listen) > 0)");
		$sth->bindParam(':serial', $serial_number, PDO::PARAM_STR);
		$sth->execute();

		$ret = $sth->fetchAll(PDO::FETCH_CLASS, __CLASS__);
		return new ParameterNames($ret);
	}


	/**
	 * Liste des parametres d'un Device
	 * @param Int                 $serial_number           Numero de serie
	 * @return                                             ParameterList<ParamDevice>
	 * @throws                                             Exception
	 **/
	public static function getByDevice($serial_number){
		$sth = Database::prepare("
			SELECT p.*, d.*
			FROM param p
			JOIN param_device d ON d.param_id = p.param_id
			WHERE d.serial_number = :serial");
		$sth->bindParam(':serial', $serial_number, PDO::PARAM_STR);
		$sth->execute();

		$ret = $sth->fetchAll(PDO::FETCH_CLASS, __CLASS__);
		return new ParameterList($ret);
	}


	//------------------------------
	// Sql
	//------------------------------
	public function update(){
		Database::query("
			INSERT INTO param_device SET
				serial_number = '".$this->serial_number."',
				param_id      = '".$this->param_id."',
				acs_value     = '".$this->acs_value."',
				device_value  = '".$this->device_value."',
				date_written  = '".$this->date_written."',
				date_update   = '".$this->date_update."'

			ON DUPLICATE KEY UPDATE
				acs_value     = VALUES(acs_value),
				device_value  = VALUES(device_value),
				date_written  = VALUES(date_written),
				date_update   = VALUES(date_update)");
	}

	public function delete(){
		Database::query("
			DELETE FROM param_device
			WHERE serial_number = '".$this->serial_number."'
			  AND param_id      = '".$this->param_id."'");
	}
}
