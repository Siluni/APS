<?php

/**
 * Definition d'un parametre. Nom ACS, alias, ...
 **/
class Param{

	/** @var Int                  $param_id                SQL ID (param) */
	public $param_id;

	/** @var String               $name                    Nom protocol TR069 */
	public $name;

	/** @var String               $alias                   Alias (protocol) */
	public $alias;

	/** @var String               $xsd                     Type XSD */
	public $xsd;

	/** @var String               $param_enum              Validation valeur (param_device) */
	public $param_enum;

	/** @var String               $param_default           Valeur a defaut */
	public $param_default;

	/** @var String               $listen                  ENUM */
	public $listen;


	//------------------------------
	// Constructeur
	//------------------------------
	/**
	 * Chargement de tout les "param" definie
	 * @return                                             Array<Param>
	 * @throws                                             PDOException
	 **/
	public static function getAll(){
		$sth = Database::query("SELECT * FROM param");
		return $sth->fetchAll(PDO::FETCH_CLASS, __CLASS__);
	}
}
