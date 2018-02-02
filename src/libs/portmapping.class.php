<?php

class PortMapping{

	/** @var Int                  $id                      SQL ID (port_mapping) */
	public $id;

	/** @var String               $serial_number           SQL ID (device) */
	public $serial_number;

	/** @var Int                  $port_in_start           Port WAN debut */
	public $port_in_start;

	/** @var Int                  $port_in_end             Port WAN fin */
	public $port_in_end;

	/** @var Int                  $port_out_start          Port LAN debut */
	public $port_out_start;

	/** @var Int                  $port_out_end            Port LAN fin */
	public $port_out_end;

	/** @var String               $protocol                UDP / TCP */
	public $protocol;

	/** @var String               $ip                      LAN IP cible */
	public $ip;

	/** @var String               $name                    Description */
	public $name;


	//------------------------------
	// Constructeur
	//------------------------------


}
