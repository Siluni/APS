<?php

/**
 * Parametre lie a un "Group"
 * @uses Param, Database, ParameterNames, ParameterList
 **/
class ParamGroup extends Param{

	/** @param Int                $group_id                SQL ID (group) */
	public $group_id;

	/** @param String             $value                   Valeur a defaut */
	public $value;


	//------------------------------
	// Constructeur
	//------------------------------
	/**
	 * Chargement des parametres a defaut pour un group
	 * @param Int                 $group_id                SQL ID (group)
	 * @return                                             ParameterList<ParamGroup>
	 **/
	public static function getByGroupId($group_id){
		$sth = Database::prepare("
			SELECT p.*, g.* FROM param p
			JOIN param_group g ON g.param_id = p.param_id
			WHERE g.group_id = :group");
		$sth->bindParam(':group', $group_id, PDO::PARAM_INT);
		$sth->execute();

		$ret = $sth->fetchAll(PDO::FETCH_CLASS, __CLASS__);
		return new ParameterList($ret);
	}
}
