<?php
class Request
{
    /**
     * Property: HTTPmethod
     * The HTTP method this request was made in, either GET, POST, PUT or DELETE
     */
    public $HTTPmethod = '';
    /**
     * Property: args
     * Any additional URI components after the endpoint and verb have been removed, in our
     * case, an integer ID for the resource. eg: /<endpoint>/<verb>/<arg0>/<arg1>
     * or /<endpoint>/<arg0>
     */
    public $args = Array();
    /**
     * Property: endpoint
     * The Model requested in the URI. eg: /cdn/
     */
    public $endpoint = '';
    /**
     * Property: ID
     * The Model requested in the URI. eg: /cdn/343434
     */
    public $ID = '';
    /**
     * Property: func
     * Function requested in URI. eg: /cdn/343434/purge
     */
    public $func = '';
    /**
     * Property: data
     * Parameters sent in post request
     */
	public $data = Array();
	public $Error = Array();
	public $auth;
	
	
 public function __construct() {

        $this->HTTPmethod = $_SERVER['REQUEST_METHOD'];
		$headers = apache_request_headers();
		if(isset($headers["Authentication"])){
			$this->auth = $headers["Authentication"];
		}
		else{
			$this->Error = array("code" => 401, "text" => "Unauthorized");
		}
		
        $this->args = explode('/', rtrim($_SERVER["REQUEST_URI"], '/'));
		if(isset($this->args[1])){
			$this->endpoint = $this->args[1];
			// If endpoint == /auth
			// need to generate API key
			if($this->endpoint == "auth"){
				$this->Error = array();
			}
		}
		else{
			$this->Error = array("code" => 405, "text" => "No recognized method");
		}
		if(isset($this->args[2])){
			$this->ID = $this->args[2];
		}
		if(isset($this->args[3])){
			$this->func = $this->args[3]; 
		}
        if ($this->HTTPmethod == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
            if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
                $this->method = 'DELETE';
            } else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
                $this->method = 'PUT';
            } else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'POST') {
                $this->method = 'POST';
            } else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'GET') {
                $this->method = 'GET';
            } else {
                throw new Exception("Unexpected Header");
            }
        }    

       switch($this->HTTPmethod) {
        case 'DELETE':
			$this->Error = array("code" => 405, "text" => "Request method not allowed");
			break;
        case 'PUT':
			$this->Error = array("code" => 405, "text" => "Request method not allowed");
			break;
        case 'POST':
            $this->data = $this->_cleanInputs($_POST);
            break;
        case 'GET':
		// get method currently disabled.
//            $this->data = $this->_cleanInputs($_GET);
            break;
        default:
            $this->HTTPmethod = "INVALID";
            break;
        }
    }
    protected function getRequest($key = "request"){
        if(isset($_REQUEST[$key])){
            return $_REQUEST[$key];
        }
        else{
            return false;
        }
    }
    private function _cleanInputs($data) {
        $clean_input = Array();
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $clean_input[$k] = $this->_cleanInputs($v);
            }
        } else {
            $clean_input = trim(strip_tags($data));
        }
        return $clean_input;
    }
}