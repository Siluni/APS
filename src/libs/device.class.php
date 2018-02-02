<?php

/**
 * Gestion des devices (Box, CPE, ...)
 *
 * @uses Database
 * @uses Param
 * */
class Device{

    /** @param String             $serial_number           Numero de serie (PRIMARY KEY) */
    public $serial_number;

    /** @param Int                $group_id                SQL ID (group) */
    public $group_id;

    /** @param DateTime           $date_created            Date de creation dans l'ACS */
    public $date_created;

    /** @param String             $firmware                Firmware actuel (ressource_file.command_key) */
    public $firmware;

    /** @param String             $config_file             Fichier de conf (ressource_file.command_key) */
    public $config_file;

    /** @param String             $version_engine          Version du moteur de generation utilise */
    public $version_engine;

    /** @param String             $version_config          Version du fichier de configuration utilise */
    public $version_config;

    /** @param String             $auth_mode               Mode d'authentification */
    public $auth_mode;

    /** @param String             $famille                 Departement et technologie associe */
    public $famille;

    /** @param Boolean            $debug                   Activer l'outil de debugage ? */
    public $debug;

    /** @param Boolean            $baned                   La box est bannie ? */
    public $baned;

    //------------------------------
    // Constructeur
    //------------------------------
    /**
     * Chargement d'un device par son serial
     * @param String              $serial                  Serial number
     * @return                                             Device
     * @throws                                             Exception
     * */
    public static function getBySerial($serial){
        $sth = Database::prepare("SELECT * FROM device WHERE serial_number = :serial");
        $sth->bindParam(':serial', $serial, PDO::PARAM_STR);
        $sth->execute();

        $ret = $sth->fetchAll(PDO::FETCH_CLASS, __CLASS__);
        if(count($ret) == 1){
            return array_shift($ret);
        }
        throw new Exception('Device not found');
    }

    //------------------------------
    // Fonctions
    //------------------------------
    /**
     * Reinitialise le firmware, fichier de configuration et les parametres du device
     * @throws                                             Exception
     * */
    public function resetDevice(){
        if($this->serial_number != null){
            $sth = Database::prepare("UPDATE device SET firmware = NULL, config_file = NULL WHERE serial_number = :serial");
            $sth->bindParam(':serial', $this->serial_number, PDO::PARAM_STR);
            $sth->execute();

            $this->firmware    = null;
            $this->config_file = null;
            $this->resetParam();
        }
    }

    /**
     * Reinitialise le transfert de tout les parametres du device
     * @throws                                             Exception
     * */
    public function resetParam(){
        if($this->serial_number != null){
            $sth = Database::prepare("UPDATE param_device SET date_written = NULL, device_value = '' WHERE serial_number = :serial");
            $sth->bindParam(':serial', $this->serial_number, PDO::PARAM_STR);
            $sth->execute();
        }
    }

    //------------------------------
    // Sql
    //------------------------------
    /**
     * Mise a jour des informations dans la BDD
     * @throws                                             Exception
     * */
    public function update(){
        Database::query("
			INSERT INTO device SET
				serial_number  = ".Database::quote($this->serial_number, PDO::PARAM_STR).",
				group_id       = ".Database::quote($this->group_id, PDO::PARAM_INT).",
				firmware       = ".Database::quote($this->firmware, PDO::PARAM_STR).",
				config_file    = ".Database::quote($this->config_file, PDO::PARAM_STR).",
				version_engine = ".Database::quote($this->version_engine, PDO::PARAM_STR).",
				version_config = ".Database::quote($this->version_config, PDO::PARAM_STR).",
				auth_mode      = ".Database::quote($this->auth_mode, PDO::PARAM_STR).",
				famille        = ".Database::quote($this->famille, PDO::PARAM_STR).",
				baned          = ".Database::quote($this->baned ? '1' : '0', PDO::PARAM_STR).",
				date_created   = NOW()

			ON DUPLICATE KEY UPDATE
				famille        = VALUES(famille),
				firmware       = VALUES(firmware),
				auth_mode      = VALUES(auth_mode),
				config_file    = VALUES(config_file),
				version_engine = VALUES(version_engine),
				baned          = VALUES(baned),
				version_config = VALUES(version_config)");
    }

    /**
     * Suppression du device
     * throws                                              Exception
     * */
    public function delete(){
        Database::query("DELETE FROM param_device WHERE serial_number = '".$this->serial_number."'");
        Database::query("DELETE FROM port_mapping WHERE serial_number = '".$this->serial_number."'");
//		Database::query("DELETE FROM log_error    WHERE serial_number = '".$this->serial_number."'");
//		Database::query("DELETE FROM log          WHERE serial_number = '".$this->serial_number."'");
        Database::query("DELETE FROM device       WHERE serial_number = '".$this->serial_number."'");
    }

    public function vendorSpecificFirmware($firmware_version){

        /*
          X_00604C_SystemUpgrade.StartDownload                  // true/false
          X_00604C_SystemUpgrade.ForceUpgrade                   // true/false
          X_00604C_SystemUpgrade.NewVersionAvailable            // true/false
          X_00604C_SystemUpgrade.NewVersionNumber               // String
          X_00604C_SystemUpgrade.NewVersionDescription          // String
          X_00604C_SystemUpgrade.DownloadUrl                    // String
          X_00604C_SystemUpgrade.UpgradeStatus                  // READ / String
          X_00604C_SystemUpgrade.DownloadStatus                 // READ / String
         */
    }

}