<?php

/**
 * Liste de parametres (ParamDevice)
 **/
class ParameterList extends ArrayObject{


	//------------------------------
	// Mise a jour des parametre
	//------------------------------
	public function setParameterValuesResponse(){
		foreach($this as $paramDevice){
			$paramDevice->date_written = date('Y-m-d H:i:s');
			$paramDevice->update();
		}
	}



	/**
	 * Formatage XML de l'objet
	 * @return                                             String
	 **/
	public function to_xml(){
		$out = '<ParameterList SOAP-ENC:arrayType="cwmp:ParameterValueStruct['.count($this).']">';
		foreach($this as $paramDevice){
			$xsd = $paramDevice->xsd? 'xsi:type="xsd:'.$paramDevice->xsd.'"' : '';
			$out .=
				'<ParameterValueStruct>'.
					'<Name>'.$paramDevice->name.'</Name>'.
					'<Value '.$xsd.'>'.$paramDevice->acs_value.'</Value>'.
				'</ParameterValueStruct>';
		}
		$out .= '</ParameterList>';
		return $out;
	}
}
