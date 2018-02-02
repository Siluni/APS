<?php

/**
 * ACS class based on TR_069 specification
 *
 * @uses Model
 * @uses Group
 * @uses Device
 * @uses Log
 * @uses ParameterList
 *
 * @link http://www.broadband-forum.org/technical/download/TR-069_Amendment-2.pdf
 * @link http://www.broadband-forum.org/cwmp/tr-069-1-0-0.html SetParameterValue
 * */
class ACS{

    /** @param Model              $model                   Modele associe au device */
    public $model;

    /** @param Group              $group                   Groupe associe au device */
    public $group;

    /** @param Device             $device                  Device */
    public $device;
    //------------------------------
    // Internal
    //------------------------------
    /** @param String             $ID                      Token */
    private $ID = null;

    /** @param EventManager       $event_manager           Declanchement des evenements */
    private $event_manager = null;

    /** @param String             $next_method             Next command to send (CPE) */
    public static $next_method = null;

    /** @param String             $call_method             CPE method */
    public static $call_method = null;

    //------------------------------
    // Internal function
    //------------------------------
    /**
     * SoapServer safe function
     * @param String                   $method                  Method name
     * @param Array                    $arguments               Method arguments
     * @return                                                  Mixed
     * */
    public function __call($method, $arguments){
        // Inform first!
        if(!$this->device instanceof Device || $this->device->serial_number == null){
            Log::error($this->device->serial_number, $method, 'Device not registered');
            throw new SoapFault('8001', 'Request denied (no reason specified)');
        }

        // Device request
        if(method_exists($this, $method)){
            return call_user_func_array(array($this, $method), $arguments);
        }

        // Callback process / Event manager
        self::$call_method = $method.'Response';
        if($this->event_manager == null){
            $this->event_manager = EventManager::buildFromAcs($this);
        }elseif($this->event_manager->valid()){
            $event = $this->event_manager->current();
            if(is_callable($event['callback'])){
                call_user_func_array($event['callback'], $arguments);
                Log::add($this->device->serial_number, $method, json_encode($arguments));
            }

            $this->event_manager->next();
        }

        // ACS next command
        self::$next_method = null;
        if($this->event_manager->valid()){
            $event             = $this->event_manager->current();
            self::$next_method = $event['method'];
            Log::add($this->device->serial_number, self::$next_method, $event['log']);
            return new SoapVar($event['args'], XSD_ANYXML);
        }
    }

    public function __wakeup(){
        global $device;
        $device = $this->device;
    }

    /**
     * Erreur de communication avec le device
     * @param Int                 $faultcode               Numero de l'erreur
     * @param String              $faultstring             Information
     * */
    public function Fault($faultcode, $faultstring){
        Log::add(($this->device instanceof Device ? $this->device->serial_number : $_SERVER['REMOTE_ADDR']), $faultstring);
        self::$call_method = 'ABC';
        self::$next_method = null; // End transaction
    }

    //------------------------------
    // ACS function
    //------------------------------
    /**
     * Token de la transaction
     * @param String              $token                   TOKEN_ID
     * @return                                             SoapHeader
     * */
    public function ID($token){
        $this->ID = in_array($token, array('', $this->ID)) ? rand(10000, 999999) : $token;
        return new SoapHeader('urn:dslforum-org:cwmp-1-0', 'ID', $this->ID, true);
    }

    /**
     * Available ACS function
     * @return                                             SoapParam
     * */
    private function GetRPCMethods(){
        Log::add($this->device->serial_number, 'GetRPCMethods');
        $_implemented_func = get_class_methods($this);
        $_available_method = array(
            'GetRPCMethods', 'Inform', 'TransferComplete', 'Kicked',
            'AutonomousTransferComplete', 'RequestDownload');

        $resp = array_intersect($_implemented_func, $_available_method);
        return new SoapParam(array_values($resp), 'MethodList');
    }

    /**
     * CPE identificate function
     * @param DeviceIdStruct           $DeviceId                CPE information
     * @param EventStruct              $Event                   Event send by CPE
     * @param Int                      $MaxEnvelopes            Max envelope per message
     * @param DateTime                 $CurrentTime             The current DateTime known by the CPE
     * @param Int                      $RetryCount              Number of retry (to get Session)
     * @param ParameterValueStruct     $ParameterList           CPE parameter
     * @return                                                  SoapVar
     * @throws                                                  SoapFault
     * */
    public function Inform($DeviceId, $Event, $MaxEnvelopes, $CurrentTime, $RetryCount, $ParameterList){

        try{ // Chargement des informations
            try{
                $this->device = Device::getBySerial($DeviceId->SerialNumber);
            } // Device connu
            catch(Exception $e){
                $this->device = Autocreate::device($DeviceId);
            } // Creation auto du device
            $this->group = Group::getById($this->device->group_id);
            $this->model = Model::getById($this->group->model_id);
        }catch(Exception $e){
            Log::error($DeviceId->SerialNumber, __METHOD__, $e->getMessage());
            throw new SoapFault('8001', 'Request denied (no reason specified)');
        }

        // Action specifique sur "evenement"
        $_event = array();
        foreach($Event as $EventStruct){
            if($EventStruct->EventCode == '0 BOOTSTRAP'){
                $this->device->resetDevice();
            }
            //if(preg_match('/^[0-9]+ [A-Z ]+$/', $EventStruct->EventCode)){ $_event[] = $EventStruct->EventCode; }
            $_event[] = $EventStruct->EventCode;
        }

        // Detection du firmware
        $device_ip = '';
        foreach($ParameterList as $ParameterValueStruct){
            if($ParameterValueStruct->Name == 'InternetGatewayDevice.DeviceInfo.SoftwareVersion'){
                try{
                    $file                   = RessourceFile::getByVersion($ParameterValueStruct->Value);
                    $this->device->firmware = $file->command_key;
                }catch(Exception $e){ // Firmware inconnu
                    $this->device->firmware = $ParameterValueStruct->Value;

                    // Vendor firmware validation ( Android )
                    $this->device->vendorSpecificFirmware($ParameterValueStruct->Value);
                }

                $this->device->update();
            }

            if(strpos($ParameterValueStruct->Name, '.1.ExternalIPAddress') !== false){
                $device_ip = $ParameterValueStruct->Value;
            }
        }

        Log::add($DeviceId->SerialNumber, 'Inform', implode(', ', $_event).', '.$device_ip);

        // Suppression de l'effet "boule de neige" en cas de fichier incorrect
        if(count(Log::check_error($DeviceId->SerialNumber, '5 MINUTE')) > 0){
            throw new SoapFault('Request denied (no reason specified)', 8001);
        }

        global $device;
        $device = $this->device;
        return new SoapVar('<MaxEnvelopes>1</MaxEnvelopes>', XSD_ANYXML);
    }

    /**
     * Information de completion d'un Download de fichier
     * @param String              $CommandKey              Reference du fichier fournit
     * @param FaultStruct         $FaultStruct             Une erreur est survenue durant le transfert
     * @param DateTime            $StartTime               Date du debut du transfert (UTC)
     * @param DateTime            $CompleteTime            Date de completion du transfert (UTC)
     * */
    private function TransferComplete($CommandKey, $FaultStruct, $StartTime, $CompleteTime){
        Log::add($this->device->serial_number, 'TransferComplete', $FaultStruct->FaultString);
        if($FaultStruct->FaultCode > 0){
            Log::error($this->device->serial_number, __METHOD__, $FaultStruct->FaultString);
            return new SoapVar('', XSD_ANYXML);
        }

        if($CommandKey == $this->group->firmware){
            $this->device->firmware    = $CommandKey;
            $this->device->config_file = '';
            $this->device->update();
        }

        if($CommandKey == $this->group->config_file || $CommandKey == $this->device->serial_number){
            $this->device->config_file = $CommandKey;
            $this->device->resetParam();
            $this->device->update();
        }

        return new SoapVar('', XSD_ANYXML);
    }

    /**
     * Download a l'initiative du device
     * @param String              $AnnounceURL             URL ayant declancher le telechargement
     * @param String              $TransferURL             URL fichier telecharge
     * @param Boolean             $IsDownload              Download (1) / upload (0)
     * @param String              $FileType                File type (vendor config, firmware upgrade, ...)
     * @param UnsignedInt         $FileSize                Taille (en bytes)
     * @param String              $TargetFileName          Nom du fichier sur le device
     * @param FaultStruct         $FaultStruct             Erreur durant le download ?
     * @param DateTime            $StartTime               Debut de telechargement
     * @param DateTime            $CompleteTime            Fin du telechargement
     * @return                                             SoapVar
     * */
    private function AutonomousTransferComplete($AnnounceURL, $TransferURL, $IsDownload, $FileType, $FileSize, $TargetFileName, $FaultStruct, $StartTime, $CompleteTime){
        Log::add($this->device->serial_number, 'AutonomousTransferComplete', $FileType.':'.$TransferURL.' ('.$FaultStruct->FaultString.')');
        return new SoapVar('', XSD_ANYXML);
    }

    /* Optional methods */
    // private function RequestDownload($FileType, $FileTypeArg){}
    // private function Kicked($Command, $Referer, $Arg, $Next){}
}