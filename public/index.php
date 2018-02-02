<?php

/**
 * ACS engine (PROTOCOL TR-069)
 * @uses SoapServer
 * @link http://www.broadband-forum.org/technical/download/TR-069_Amendment-2.pdf
 * */
try{
    require_once '../_commun/_init.php';
    global $device;
    $device = null;

    // Recomposition des donnees "chunked"
    function HTTPChunkDecoder($chunkedData){
        $decodedData = '';
        do{
            $tempChunk   = explode(chr(13).chr(10), $chunkedData, 2);
            $chunkSize   = hexdec($tempChunk[0]);
            $decodedData .= substr($tempChunk[1], 0, $chunkSize);
            $chunkedData = substr($tempChunk[1], $chunkSize + 2);
        }while(strlen($chunkedData) > 0);
        return $decodedData;
    }

    $input = file_get_contents('php://input');

    $headers = apache_request_headers();
    if(array_key_exists('Transfer-Encoding', $headers) && $headers['Transfer-Encoding'] == 'chunked'){
        $input = HTTPChunkDecoder($input);
    }

    // Authentification
    Utilisateur::authenticate(@$_SERVER['PHP_AUTH_USER'], @$_SERVER['PHP_AUTH_PW']);


    //------------------------------
    // Soap Server
    //------------------------------
    $soap   = new ApsServer(null, array('uri' => 'urn:dslforum-org:cwmp-1-0'));
    $soap->setClass('ACS');
    $soap->setPersistence(SOAP_PERSISTENCE_SESSION);
    $output = $soap->handle($input);
    if(isset($token)){
        Log::debug($input, $output);
    }
    header('HTTP/1.1 200 OK');
}catch(Exception $e){
    // En cas d'echec, on stop la transaction avec le device
    $output = '';
    @session_destroy();
    header_remove();
    header('HTTP/1.1 204 No Content');
}

// Reponse a envoyer au device ( +log )
if($device instanceof Device && $device->debug){
    Log::debug($input, $output);
}

echo $output;
