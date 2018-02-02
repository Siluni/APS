<?php

/**
 * Fichier a telecharger
 * @uses Database
 **/
class RessourceFile{

	/** @var String               $command_key             SQL ID (ressource_file) */
	public $command_key;

	/** @var String               $file_type               Type de fichier */
	public $file_type;

	/** @var String               $url                     URL du fichier a telecharger */
	public $url;

	/** @var String               $username                $_SERVER['PHP_AUTH_USER'] */
	public $username;

	/** @var String               $password                $_SERVER['PHP_AUTH_PW'] */
	public $password;

	/** @var Int                  $file_size               Taille du fichier (en octet) */
	public $file_size;

	/** @var String               $target_file_name        Nom fichier cible (sans l'ext) */
	public $target_file_name;

	/** @var Int                  $delay_seconds           Temps (en sec) avant debut du download */
	public $delay_seconds;

	/** @var String               $success_url             URL si transfert reussi */
	public $success_url;

	/** @var String               $failure_url             URL si transfert echoue */
	public $failure_url;

	/** @var String               $version                 Nom de la version */
	public $version;

	/** @var String               $commentaire             Commentaire */
	public $commentaire;


	//------------------------------
	// Constructeur
	//------------------------------
	/**
	 * Chargement d'un RessourceFile par sa command_key
	 * @param String              $value                   Reference
	 * @return                                             RessourceFile
	 * @throws                                             Exception
	 **/
	public static function getByCommandKey($value){
		$sth = Database::prepare("SELECT * FROM ressource_file WHERE command_key = :value");
		$sth->bindParam(':value', $value, PDO::PARAM_STR);
		$sth->execute();

		$ret = $sth->fetchAll(PDO::FETCH_CLASS, __CLASS__);
		if(count($ret) == 1){ return array_shift($ret); }
		throw new Exception('RessourceFile not found');
	}

	/**
	 * Chargement d'un RessourceFile par son nom
	 * @param String              $value                   Reference
	 * @return                                             RessourceFile
	 * @throws                                             Exception
	 **/
	public static function getByVersion($value){
		$sth = Database::prepare("SELECT * FROM ressource_file WHERE version = :value");
		$sth->bindParam(':value', $value, PDO::PARAM_STR);
		$sth->execute();

		$ret = $sth->fetchAll(PDO::FETCH_CLASS, __CLASS__);
		if(count($ret) == 1){ return array_shift($ret); }
		throw new Exception('RessourceFile not found');
	}


	//------------------------------
	// Fonctions
	//------------------------------
	/**
	 * Formatage XML de l'objet
	 * @return                                             String
	 **/
	public function to_xml(){
		return
			'<CommandKey>'.$this->command_key.'</CommandKey>'.
			'<FileType>'.$this->file_type.'</FileType>'.
			'<URL>'.$this->url.'</URL>'.
			'<Username>'.$this->username.'</Username>'.
			'<Password>'.$this->password.'</Password>'.
			'<FileSize>'.$this->file_size.'</FileSize>'.
			'<TargetFileName>'.$this->target_file_name.'</TargetFileName>'.
			'<DelaySeconds>'.$this->delay_seconds.'</DelaySeconds>'.
			'<SuccessURL>'.$this->success_url.'</SuccessURL>'.
			'<FailureURL>'.$this->failure_url.'</FailureURL>';
	}
}
