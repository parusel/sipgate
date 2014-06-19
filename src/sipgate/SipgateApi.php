<?php namespace Sipgate;

/**
 * This class is a wrapper for the Sipgate API
 */
class SipgateApi
{

    /**
     * Stores the user (email)
     */
    protected $user;

    /**
     * Stores the users password
     */
    protected $password;

    /**
     * Stores the users id ('my' as default)
     */
    protected $user_id;

    /**
     * Constructor. Save credentials on initialization.
     */
    public function __construct($user, $password, $user_id = 'my')
    {
        $this->user = $user;
        $this->password = $password;
        $this->user_id = $user_id;
    }

    /**
     * Send REST Request to the API
     *
     * @param string    Method
     * @param array     Options
     * @param string    Request type (GET, POST, ...)
     */
    private function sendRestRequest($method, $options = array(), $request = 'GET')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.sipgate.net:443/' . $this->user_id . '/' . $method . '/?' . http_build_query($options) );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->user . ":" . $this->password);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

        $output = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        return (object) array('info' => $info,'output' => $output);
    }

    /**
     * Get the current account balance
     */
    public function restBalance()
    {
        return $this->sendRestRequest('billing/balance', array('version' => '2.41.0', 'complexity' => 'full'));
    }
    
    /**
     * Returns a list of call events
     */
    public function restCallEvents()
    {
        return $this->sendRestRequest('events/calls', array('version' => '2.41.0', 'complexity' => 'full'));
    }
    
    /**
     * Returns a list of fax events
     */
    public function restFaxEvents()
    {
        return $this->sendRestRequest('events/faxes', array('version' => '2.41.0', 'complexity' => 'full'));
    }
    
    /**
     * Returns a list of voicemail events
     */
    public function restVoicemailEvents()
    {
        return $this->sendRestRequest('events/voicemails', array('version' => '2.41.0', 'complexity' => 'full'));
    }
    
    /**
     * Returns a list of call sessions
     */
    public function restSessionCalls()
    {
        return $this->sendRestRequest('sessions/calls', array('version' => '2.41.0', 'complexity' => 'full'));
    }
    
    /**
     * Starts the recording of a call
     */
    public function restRecordCall($session_id)
    {
        return $this->sendRestRequest('sessions/calls/' . $session_id . '/recording', array('version' => '2.41.0', 'complexity' => 'full'),'PUT');
    }
    
    /**
     * Send an XMLRPC request to the API
     *
     * @param string    Method
     * @param array     Options
     */
    private function sendXmlrpcRequest($method, $options = array())
    {
        if (!function_exists('xmlrpc_encode_request')) {
            throw new Exception('XMLRPC extension not found.');
        }

        $request_options = array('encoding' => 'UTF-8');
        $xmlrpc = xmlrpc_encode_request($method, $options, $request_options);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.sipgate.net:443/RPC2');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->user . ":" . $this->password);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml"));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlrpc);

        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        return $response;
    }

    /**
     * Sends an SMS
     *
     * @param string    Remote number
     * @param string    Message
     */
    public function sendSms($remote, $message)
    {
        $options = array();
        $options['RemoteUri'] = 'sip:' . $remote . '@sipgate.net';
        $options['TOS'] = 'text';
        $options['Content'] = $message;

        return $this->sendXmlrpcRequest('samurai.SessionInitiate', $options);
    }

    /**
     * Sends a fax
     *
     * @param string    Remote number
     * @param string    Message
     */
    public function sendFax($remote, $message)
    {
        $options = array();
        $options['RemoteUri'] = 'sip:' . $remote . '@sipgate.net';
        $options['TOS'] = 'fax';
        $options['Content'] = $message;

        return $this->sendXmlrpcRequest('samurai.SessionInitiate', $options);
    }

    /**
     * Returns the account balance
     */
    public function getBalance()
    {
        return $this->sendXmlrpcRequest('samurai.BalanceGet');
    }

    /**
     * Lists all available methods
     */
    public function listMethods()
    {
        return $this->sendXmlrpcRequest('system.listMethods');
    }

    /**
     * Get help for a specified method
     *
     * @param string    Method
     */
    public function methodHelp($method)
    {
        $options = array();
        $options['MethodeName'] = $method;
        return $this->sendXmlrpcRequest('system.methodHelp', $options);
    }

    /**
     * Get method signature for a specified method
     *
     * @param string    Method
     */
    public function methodSignature($method)
    {
        $options = array();
        $options['MethodeName'] = $method;
        return $this->sendXmlrpcRequest('system.methodSignature', $options);
    }

    /**
     * Returns informations about the server
     */
    public function serverInfo()
    {
        return $this->sendXmlrpcRequest('system.serverInfo');
    }

    /**
     * Returns event summary
     */
    public function getEventSummary()
    {
        return $this->sendXmlrpcRequest('samurai.EventSummaryGet');
    }

    /**
     * Returns an event list
     */
    public function getEventList()
    {
        $options = array();
        $options['Limit'] = 0;
        $options['Offset'] = 0;

        return $this->sendXmlrpcRequest('samurai.EventListGet', $options);
    }

    /**
     * Returns history by date
     */
    public function getHistoryByDate()
    {
        return $this->sendXmlrpcRequest('samurai.HistoryGetByDate');
    }


    /**
     * Returns a list of own URIs
     */
    public function getOwnUriList()
    {
        return $this->sendXmlrpcRequest('samurai.OwnUriListGet');
    }

    /**
     * Returns available labels
     */
    public function getLabelList()
    {
        return $this->sendXmlrpcRequest('samurai.LabelList');
    }

    /**
     * Initiates a session (call)
     *
     * @param string    Local number
     * @param string    Remote number
     */
    public function initiateSession($local, $remote)
    {
        $options = array();
        $options['LocalUri'] = 'sip:' . $local . '@sipgate.net'; // @sipgate.de for sipgate user (1440578e8)
        $options['RemoteUri'] = 'sip:' . $remote . '@sipgate.net';
        $options['TOS'] = 'voice';

        return $this->sendXmlrpcRequest('samurai.SessionInitiate', $options);
    }
    
    /**
     * Get status of specified session
     *
     * @param int   Session ID
     */
    public function getSessionStatus($session_id)
    {
        $options = array();
        $options['SessionID'] = $session_id;

        return $this->sendXmlrpcRequest('samurai.SessionStatusGet', $options);
    }

    /**
     * Close session (hangup)
     *
     * @param integer   Session ID
     */
    public function closeSession($session_id)
    {
        $options = array();
        $options['SessionID'] = $session_id;

        return $this->sendXmlrpcRequest('samurai.SessionClose', $options);
    }

}
