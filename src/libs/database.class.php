<?php

/**
 * Classe de connexion a un SGBD (Singleton)
 * @see /config/database.ini.php
 * @link http://php.net/manual/fr/book.pdo.php
 **/
class Database{

	/** @var PDO                    $pdo                   Connexion au SGBD */
	private static $pdo = null;


	//------------------------------
	// Singleton
	//------------------------------
	private function __construct(){}
	private function __clone(){}


	/**
	 * Connexion a un serveur
	 * @return                                             PDO
	 * @throws                                             PDOException
	 **/
	public static function _connect(){
		if(!self::$pdo){
			$conf = parse_ini_file(CONF_PATH.'/database.ini.php', true);

			$dsn = $conf['db_driver'].':';
			foreach($conf['dsn'] as $k => $v){ $dsn .= $k.'='.$v.';'; }
			self::$pdo = new PDO($dsn, $conf['db_user'], $conf['db_password'], $conf['db_options']);

			foreach($conf['db_attributes'] as $k => $v){
				self::$pdo->setAttribute(constant('PDO::'.$k), constant('PDO::'.$v));
	        }
		}
		return self::$pdo;
	}

	/**
	 * Function de rappel
	 * @example <code>
	 * 	$sth = Database::prepare("SELECT nom, prenom FROM users WHERE id = :id");
	 * 	$sth->execute(array(':id' => 50));
	 * </code>
	 *
	 * @param String              $name                    PDO methods
	 * @param Array               $args                    Methods args
	 * @return                                             Mixed
	 * @throws                                             PDOException
	 **/
	public static function __callStatic($name, $args){
		$callback = array(self::_connect(), $name);
		return call_user_func_array($callback, $args);
	}
}
