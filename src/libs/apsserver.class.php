<?php

/** Extenssion du SoapServer */
class ApsServer extends SoapServer{

	/**
	 * Handles a SOAP request
	 * @see SoapServer::handle()
	 *
	 * @param String[optional]    $soap_request            String
	 * @return                                             String
	 * @throws                                             Exception
	 **/
	public function handle($soap_request = null){
		if(!$soap_request){
			$soap_request =
				'<?xml version="1.0"?>'.PHP_EOL.
				'<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cwmp="urn:dslforum-org:cwmp-1-0"'.
				' xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'.
				' xmlns:xsd="http://www.w3.org/2001/XMLSchema"><SOAP-ENV:Header><cwmp:ID /></SOAP-ENV:Header><SOAP-ENV:Body>'.
				'<cwmp:acsHandler /></SOAP-ENV:Body></SOAP-ENV:Envelope>'; // Call custom ACS function
		}

		// SoapServer call
		try{
			ob_start();
			parent::handle($soap_request);
			$output = ob_get_contents();
			ob_end_clean();

		}catch(Exception $e){
			$output = $e;
		}

		// "ns1" to "cwmp"
		if(strpos($output, 'xmlns:ns1=') !== false){
			$output = substr_replace($output, 'xmlns:cwmp=', strpos($output, 'xmlns:ns1='), strlen('xmlns:ns1='));
			$output = preg_replace('~(</?)ns1:([a-zA-z]+)(/?>)~', '\1cwmp:\2\3', $output);
		}

		// Method response change
		if(ACS::$call_method){
			if(!ACS::$next_method){ throw new Exception('TRANSACTION END'); }
			$output = preg_replace('~(</?cwmp:)('.ACS::$call_method.')(/?>)~', '\1'.ACS::$next_method.'\3', $output);
		}

		return $output;
	}
}
