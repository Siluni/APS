<?php

/**
 * Liste de nom de parametre
 **/
class ParameterNames extends ArrayObject{

	public function getParameterValuesResponse($parameterList){
		foreach($parameterList as $ParameterValueStruct){
			foreach($this as $paramDevice){
				if($paramDevice->name == $ParameterValueStruct->Name){
					$paramDevice->device_value = $ParameterValueStruct->Value;
					$paramDevice->update();
				}
			}
		}
	}


	/**
	 * Formatage XML de l'objet
	 * @return                                             String
	 **/
	public function to_xml(){
		$out = '<ParameterNames SOAP-ENC:arrayType="xsd:string['.count($this).']">';
		foreach($this as $paramDevice){ $out .= '<string>'.$paramDevice->name.'</string>'; }
		$out .= '</ParameterNames>'; return $out;
	}
}
