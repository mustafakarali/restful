<?php
class CDNMethod
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
    private $db;
	
    public function __construct($rr, $db) {
		// this class will deal with the actual CDN configuration
    }
	
}