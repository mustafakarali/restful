<?php
class bsAPI
{
    /**
     * Property: request
     * The request class
     */
    protected $request = '';
    
    /**
     * Property: status
     * Http status code for the response
     */
    public $status = '';
    /**
     * Property: processResult
     * An array contains the result of the requested process
     */
    public $processResult = array();
	protected $db;
    
    public function __construct($rr, $db) {
		$this->db = $db;
        $this->request = $rr;
		$this->PreProcess();
    }
    /**
     * Method: APIProcess
     * Possible endpoints: cdn, dns, or auth
	 * Authentication is always accepting
     */
	public function APIProcess($data){
		switch ($this->request->endpoint)
            {
                case "cdn":
					{
						$method = new CDNMethod($this->request, $this->db);
						$this->prepareResponse($method);
						exit;
					}
				case "dns":
					{
						$method = new DNSMethod($this->request, $this->db);
						$this->prepareResponse($method);
						exit;
					}
				case "auth":
					{
						$ApiKey = $key = md5(microtime().rand());
						if(sizeof($this->request->data) == 0){
							$this->setError(406, "Missing data");
						}
						else{
							if(isset($this->request->data["domain"])){
								$sql = "INSERT INTO API_keys (API_key, domain) VALUES ('";
								$sql .= $ApiKey."','".$this->request->data["domain"]."')";
								$this->db->query($sql);
								if($this->db->last_id()>0){
									$this->status = 200;
									$this->processResult = array(
										"Success" => "OK",
										"Response" => array("API_key" => $ApiKey)
									);
									$this->APIResponse();
								}
								else{
									$this->setError(503, "Database Error");
								}
							}
							else{
								$this->setError(406, "Missing data");
							}
						}
					}
				default:
					{
						$this->setError(405, "Method Not Allowed");
					}
			}
    }
		
	private function PreProcess(){
		if(sizeof($this->request->Error)>0){
			$this->setError($this->request->Error["code"], $this->request->Error["text"]);
		}
		else{
			$sql = "SELECT API_key FROM API_keys WHERE API_key='".$this->request->auth."'";
			$this->db->query($sql);
			if(!$db->next_record()){
				$this->setError(401, "Unathorized");
			}
			// including the right endpoint controller from the controller directory
			$includeClass = CONTROLLER_DIR . $this->request->endpoint.".php";
			if(is_file($includeClass)){
				require_once($includeClass);
			}
			else{
				$this->setError(405, "Method Not Allowed");
			}
		}
	}
	private function prepareResponse($method){
		$this->status = $method->status;
		$this->processResult = $method->processResult;
		$this->APIResponse();
	}
	private function APIResponse(){
		http_response_code($this->status);
		echo json_encode($this->processResult);
		exit;
	}
	private function setError($status, $text){
		$this->status = $status;
		$this->processResult = array(
			"Success" => "Error",
			"Error" => $text
		);
		$this->APIResponse();
		exit;
	}
}