<?php
/**
 * Definition d'un groupe specifique a un model
 * @uses Datebase
 **/
class Group{

	/** @var Int                  $group_id                SQL ID (group) */
	public $group_id;

	/** @var Int                  $model_id                SQL ID (model) */
	public $model_id;

	/** @var String               $name                    Nom associe au groupe */
	public $name;

	/** @var String               $firmware                Firmware cible (ressource_file.command_key) */
	public $firmware;

	/** @var String               $config_file             Fichier de conf (ressource_file.command_key) */
	public $config_file;

	/** @var String               $config_engine           Generateur fichier de conf (moteur) */
	public $config_engine;

	/** @var String               $config_template         Generateur fichier de conf (template) */
	public $config_template;

	/** @var String               $type_auth               WANIPConnection / WANPPPConnection */
	public $type_auth;

	/** @var String               $commentaire             Commentaire */
	public $commentaire;

	/** @var Boolean              $reboot_needed           Necessite un reboot apres un setParameterValue */
	public $reboot_needed;


	//------------------------------
	// Constructeur
	//------------------------------
	/**
	 * Chargement d'un "group" par son ID
	 * @param Int                 $value                   SQL ID (group_list)
	 * @return                                             Group
	 * @throws                                             Exception
	 **/
	public static function getById($value){
		$sth = Database::prepare("SELECT * FROM group_list WHERE group_id = :group_id");
		$sth->bindParam(':group_id', $value, PDO::PARAM_INT);
		$sth->execute();

		$ret = $sth->fetchAll(PDO::FETCH_CLASS, __CLASS__);
		if(count($ret) == 1){ return array_shift($ret); }
		throw new Exception('Group not found');
	}
}
