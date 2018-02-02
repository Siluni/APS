<?php

class Autocreate{
	public static function device($deviceid){
		// Autocreate OK ?
		$sth = Database::prepare("SELECT group_id FROM oui_autocreate WHERE oui = :oui
			AND (:serial_number REGEXP serial_regex OR serial_regex IS NULL)");
		$sth->bindParam(':oui', $deviceid->OUI, PDO::PARAM_STR);
		$sth->bindParam(':serial_number', $deviceid->SerialNumber, PDO::PARAM_STR);
		$sth->bindColumn('group_id', $group_id, PDO::PARAM_INT);
		$sth->execute(); $sth->fetch(PDO::FETCH_BOUND);

		// Group de definition
		if(!$group_id){ throw new Exception('no autocreate'); }

		// Creation du device
		$device = new Device();
		$device->serial_number = $deviceid->SerialNumber;
		$device->date_created = date('Y-m-d H:i:s');
		$device->group_id = $group_id;
		$device->update();

		// Insertion de la configuration par defaut
		$parameter_list = ParamGroup::getByGroupId($group_id);
		foreach($parameter_list as $paramGroup){
			$obj = new ParamDevice();
			$obj->serial_number = $device->serial_number;
			$obj->param_id      = $paramGroup->param_id;
			$obj->acs_value     = $paramGroup->value;
			$obj->date_update   = date('Y-m-d H:i:s');
			$obj->update();
		}

		return $device;
	}
}