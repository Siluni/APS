<?php
/**
 * Definition d'un model de device (CTD3223, CTD3225, ...)
 * @uses Datebase
 **/
class Model{

	/** @param Int                $model_id                SQL ID (model) */
	public $model_id;

	/** @param String             $name                    Nom */
	public $name;

	/** @param String             $manufacturer            Constructeur */
	public $manufacturer;

	/** @param String             $oui                     OUI */
	public $oui;

	/** @param Boolean            $discover_param          0 / 1 */
	public $discover_param;


	//------------------------------
	// Constructeur
	//------------------------------
	/**
	 * Chargement d'un "model" par son ID SQL
	 * @param Int                 $value                   SQL ID (model)
	 * @return                                             Model
	 * @throws                                             Exception
	 **/
	public static function getById($value){
		$sth = Database::prepare("SELECT * FROM model WHERE model_id = :model");
		$sth->bindParam(':model', $value, PDO::PARAM_INT);
		$sth->execute();

		$ret = $sth->fetchAll(PDO::FETCH_CLASS, __CLASS__);
		if(count($ret) == 1){ return array_shift($ret); }
		throw new Exception('Model not found');
	}


	//------------------------------
	// Sql
	//------------------------------
	/**
	 * Mise a jour des informations concernant un "model"
	 **/
	public function update(){
		Database::query("
			INSERT INTO model SET
				model_id       = '".$this->model_id."',
				name           = '".$this->name."',
				manufacturer   = '".$this->manufacturer."',
				oui            = '".$this->oui."',
				discover_param = '".$this->discover_param."'

			ON DUPLICATE KEY UPDATE
				name           = VALUES(name),
				manufacturer   = VALUES(manufacturer),
				oui            = VALUES(oui),
				discover_param = VALUES(discover_param)");
	}
}