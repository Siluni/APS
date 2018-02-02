<?php

class EventManager implements Iterator{

    private $position = 0;
    private $array    = array();

    //------------------------------
    // Constructeur
    //------------------------------
    private function __construct(){

    }

    /**
     * Liste des evenements a declancher
     * @param Acs                 $acs                     Acs class
     * @return                                             EventManager
     * */
    public static function buildFromAcs(Acs $acs){
        $obj = new EventManager();
        if($acs->device->baned){
            return $obj;
        }

        //==============================
        // File download request
        //==============================
        $this->firmware_update($acs, $obj);
        $this->config_update($acs, $obj);

        //==============================
        // setParameterValues
        //==============================
        $param_write = ParamDevice::getParameterToUpdate($acs->device->serial_number);
        if(count($param_write) > 0){
            $resp = $param_write->to_xml().'<ParameterKey></ParameterKey>';
            $obj->add('SetParameterValues', $resp, array($param_write, 'setParameterValuesResponse'));
            if($acs->group->reboot_needed == 1){
                $obj->add('Reboot', array('<CommandKey>REBOOT</CommandKey>'));
            }
        }

        //==============================
        // getParameterValues
        //==============================
        $param_read = ParamDevice::getParameterToRead($acs->device->serial_number);
        if(count($param_read) > 0){
            $resp = $param_read->to_xml();
            $obj->add('GetParameterValues', $resp, array($param_read, 'getParameterValuesResponse'));
        }

        return $obj;
    }

    //------------------------------
    // Interface (Iterator)
    //------------------------------
    public function current(){
        return $this->array[$this->position];
    }

    public function key(){
        return $this->position;
    }

    public function next(){
        ++$this->position;
    }

    public function rewind(){
        $this->position = 0;
    }

    public function valid(){
        return isset($this->array[$this->position]);
    }

    //------------------------------
    // Fonctions
    //------------------------------
    /** Close transaction with device */
    public function stop(){
        $this->position = count($this->array);
    }

    /**
     * Compare and update firmware
     * @param Acs                 $acs                     Acs instance
     * @param EventManager        $manager                 Acs event handler
     */
    private function firmware_update(Acs $acs, EventManager $manager){

        // Basic TR069
        if($acs->group->firmware && $acs->group->firmware != $acs->device->firmware){
            $resp = RessourceFile::getByCommandKey($acs->group->firmware);
            $manager->add('Download', $resp->to_xml(), array($manager, 'stop'), 0, array($resp->file_type, $resp->url));
        }

        // Vendor specific ( Android )
        $param_write   = ParamDevice::getParameterToUpdate($acs->device->serial_number);
        $android_param = array_filter($param_write, function($e){
            return strpos($e->name, 'X_00604C_SystemUpgrade.') === 0;
        });

        // Android firmware update
        if(count($android_param) /* TODO && $acs->device->firmware */){
            $resp = $android_param->to_xml().'<ParameterKey></ParameterKey>';
            $obj->add('SetParameterValues', $resp, array($android_param, 'setParameterValuesResponse'), true);
        }
    }

    /**
     * Update device configuration by file
     * @param Acs                 $acs                     Acs instance
     * @param EventManager        $manager                 Acs event handler
     */
    private function config_update(Acs $acs, EventManager $manager){
        // Auto generated file
        if($acs->group->config_template != '' && $acs->device->serial_number != $acs->device->config_file){
            $resp              = new RessourceFile();
            $resp->command_key = $acs->device->serial_number;
            $resp->file_type   = '3 Vendor Configuration File';
            $resp->url         = 'http://aps2-dl.wibox.fr/conf/'.$acs->device->serial_number.'.conf';

            $manager->add('Download', $resp->to_xml(), array($manager, 'stop'), 0, array($resp->file_type, $resp->url));
        }
        // Static config file
        elseif($acs->group->config_file && $acs->group->config_file != $acs->device->config_file){
            $resp = RessourceFile::getByCommandKey($acs->group->config_file);
            $manager->add('Download', $resp->to_xml(), array($manager, 'stop'), 0, array($resp->file_type, $resp->url));
        }
    }

    /**
     * Add action to handler ( FIFO stack )
     * @param String              $method                  TR69 method
     * @param Array[optional]     $args                    TR69 method args
     * @param Callable[optional]  $callback                Callback when receive responseEvent
     * @param Boolean[optional]   $next                    Close transaction ?
     * @param String[optional]    $log                     Print log in DB
     * */
    public function add($method, $args = array(), $callback = null, $next = false, $log = null){
        $event = array(array('method' => $method, 'args' => $args, 'callback' => $callback, 'log' => $log));
        array_splice($this->array, ($next ? $this->position + 1 : count($this->array)), 0, $event);
    }

}