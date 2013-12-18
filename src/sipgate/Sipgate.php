<?php

define('SIPGATE_USER', 'xxxxxx'); // Credential to access sipgate.
define('SIPGATE_PASS', 'xxxxxx'); // Same as login for webpage. Not the sipid for phone calls

// API endpoint for Sipgate Team
define('SIPGATE_XMLRPC_URL', 'https://api.sipgate.net:443/RPC2');
define('SIPGATE_REST_URL', 'https://api.sipgate.net:443/my/');

// API endpoint for Sipgate Basic and Sipgate Plus
define('SIPGATE_XMLRPC_URL', 'https://api.sipgate.net:443/RPC2');
define('SIPGATE_REST_URL', 'https://api.sipgate.net:443/my/');

class Sipgate
{
	
	private function sendRestRequest($method, $options = array(), $request = 'GET')
	{
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, SIPGATE_REST_URL . $method . '/?' . http_build_query($options) );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, SIPGATE_USER . ":" . SIPGATE_PASS);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

        $output = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        return (array(
            'info' => $info,
            'output' => $output));
	}

	public function rest_balance()
	{
		return $this->sendRestRequest('billing/balance', array('version' => '2.37.0', 'complexity' => 'full'));
	}
	
	public function rest_call_events()
	{
		return $this->sendRestRequest('events/calls', array('version' => '2.37.0', 'complexity' => 'full'));
	}
	
	public function rest_session_calls()
	{
		return $this->sendRestRequest('sessions/calls', array('version' => '2.37.0', 'complexity' => 'full'));
	}
	
	public function rest_record_call($session_id)
	{
		return $this->sendRestRequest('sessions/calls/' . $session_id . '/recording', array('version' => '2.37.0', 'complexity' => 'full'),'PUT');
	}
	
    private function sendXmlrpcRequest($method, $options = array())
    {
        if (!function_exists('xmlrpc_encode_request')) {
            die('install xmlrpc extension for php5.');
        }

        $request_options = array('encoding' => 'UTF-8');
        $xmlrpc = xmlrpc_encode_request($method, $options, $request_options);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, SIPGATE_XMLRPC_URL);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, SIPGATE_USER . ":" . SIPGATE_PASS);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml"));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlrpc);

        $output = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        return (array(
            'info' => $info,
            'output' => $output,
            'request' => $xmlrpc));
    }


    public function send_sms($remote, $message)
    {
        $options = array();
        $options['RemoteUri'] = 'sip:' . $remote . '@sipgate.net';
        $options['TOS'] = 'text';
        $options['Content'] = $message;

        return $this->sendXmlrpcRequest('samurai.SessionInitiate', $options);
    }

    public function get_balance()
    {
        return $this->sendXmlrpcRequest('samurai.BalanceGet');
    }

    public function list_methods()
    {
        return $this->sendXmlrpcRequest('system.listMethods');
    }

    public function method_help($method)
    {
        $options = array();
        $options['MethodeName'] = $method;
        return $this->sendXmlrpcRequest('system.methodHelp', $options);
    }

    public function method_signature($method)
    {
        $options = array();
        $options['MethodeName'] = $method;
        return $this->sendXmlrpcRequest('system.methodSignature', $options);
    }

    public function server_info()
    {
        return $this->sendXmlrpcRequest('system.serverInfo');
    }

    public function get_event_summary()
    {
        return $this->sendXmlrpcRequest('samurai.EventSummaryGet');
    }

    public function get_event_list()
    {
        return $this->sendXmlrpcRequest('samurai.EventListGet');
    }

    public function get_history_by_date()
    {
        return $this->sendXmlrpcRequest('samurai.HistoryGetByDate');
    }


    public function get_own_uri_list()
    {
        return $this->sendXmlrpcRequest('samurai.OwnUriListGet');
    }

    public function get_label_list()
    {
        return $this->sendXmlrpcRequest('samurai.LabelList');
    }


    public function click2Dial($remote, $local)
    {
        $options = array();
        $options['LocalUri'] = $local;
        $options['RemoteUri'] = 'sip:' . $remote . '@sipgate.net';
        $options['TOS'] = 'voice';

        return $this->sendXmlrpcRequest('samurai.SessionInitiate', $options);
    }
    
    public function get_session_status($session_id)
    {
        $options = array();
        $options['SessionID'] = $session_id;

        return $this->sendXmlrpcRequest('samurai.SessionStatusGet', $options);
    }

}
