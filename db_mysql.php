<?php
/*
  ****************************************************************************
  ***                                                                      ***
  ***                                                                      ***
  ****************************************************************************
*/

define("NUMBER",    1);
define("TEXT",      2);
define("DATETIME",  3);
define("FLOAT",     4);
define("INTEGER",   5);
define("DATE",      6);
define("TIME",      7);
define("TIMESTAMP", 8);

class SQL {
  
  var $DBHost         = "";
  var $DBDatabase     = "";
  var $DBUser         = "";
  var $DBPassword     = "";

	/* dates formats */
	var $DatetimeMask   = array("YYYY","-","MM","-","DD"," ","HH",":","mm",":","ss");
	var $DateMask       = array("YYYY","-","MM","-","DD");
	var $TimeMask       = array("HH",":","mm",":","ss");
	var $TimestampMask  = array("YYYY","MM","DD","HH","mm","ss");

  var $AutoFree       = 0;     
  var $LinkID         = 0;
  var $QueryID        = 0;
  var $PageNumber     = 0;
  var $RecordsPerPage = 0;
  var $Record         = array();
  var $Row            = 0;
  var $queryString = "";
  
  var $Errno       = 0;
  var $Error       = "";
  var $HaltOnError = "yes"; // "yes", "no", "report"


  function SQL($query = "") {
		$this->RecordsPerPage = 0;
		$this->query($query);
  }

  function check_lib() {
		return function_exists ("mysqli_connect");
	}

  function connect() {
    if (!$this->LinkID) {
		$server = $this->DBHost;
		if($server == ""){
			$server="localhost";
		}
	    $this->LinkID = mysqli_connect($server, $this->DBUser, $this->DBPassword, $this->DBDatabase);

      if (!$this->LinkID) {
        $this->halt("Connect failed.");
        return 0;
      }
    }
    return $this->LinkID;
  }

  function free() {
		if ($this->QueryID) {
			@mysqli_free_result($this->QueryID);
			$this->QueryID = 0;
		}
  }

  function query($Query_String) {
    if ($Query_String == "") {
      return 0;
		}
	
	$this->queryString = $Query_String;
	
    if (!$this->connect()) {
      return 0; 
    };

    if ($this->QueryID) {
      $this->free();
    }
    $this->QueryID = mysqli_query($this->LinkID,$Query_String);
    $this->Row   = 0;
    $this->Errno = mysql_errno();
    $this->Error = mysql_error();
    if (!$this->QueryID) {
      $this->halt("Invalid SQL: ".$Query_String);
    }

    return $this->QueryID;
  }

  function next_record() {
    if (!$this->QueryID) {
      $this->halt("next_record called with no query pending.");
      return 0;
    }

    $this->Record = @mysqli_fetch_array($this->QueryID);
    $this->Row   += 1;
    $this->Errno  = mysql_errno();
    $this->Error  = mysql_error();

    $stat = is_array($this->Record);
    if (!$stat && $this->AutoFree) {
      $this->free();
    }
    return $stat;
  }

  function affected_rows() {
    return @mysqli_affected_rows($this->LinkID);
  }

  function num_rows() {
    return @mysqli_num_rows($this->QueryID);
  }

  function num_fields() {
    return @mysqli_num_fields($this->QueryID);
  }

  function field_name($column){
	return @mysqli_field_name($this->QueryID, $column);
  }
  function f($Name, $field_type = TEXT) 
	{
	  if(isset($this->Record[$Name]))
	  {
			$value = $this->Record[$Name];
			switch($field_type)
			{
				case DATETIME:
					$value = parse_date($this->DatetimeMask, $value);
					break;
				case DATE:
					$value = parse_date($this->DateMask, $value);
					break;
				case TIME:
					$value = parse_date($this->TimeMask, $value);
					break;
			}
      return $value; 
		}
    else { return "";}
  }

  function halt($message) {
    global $t, $is_admin_path, $settings;

      if (!$this->Error) { $this->Error = $message; }

      if ($this->HaltOnError == "no") {
	    return;
	}

	  if ($this->HaltOnError != "report") {
			exit;
		}
	  elseif($this->HaltOnError == "report"){
		echo $this->queryString."<br>";
		echo $this->Error."<br>";
		return;
	  }
	  
	}


	function tosql($value, $value_type, $is_delimiters = true, $use_null = true)
	{
    if(is_array($value) || strlen($value)) 
		{
			switch($value_type)
			{
				case NUMBER:
				case FLOAT:
	        return doubleval(str_replace("," , ".", $value));
					break;
				case TEXT:
					$value = addslashes($value);
					break;
				case DATETIME:
					if(!is_array($value) && is_int($value)) { $value = va_time($value); }
					if(is_array($value)) { $value = va_date($this->DatetimeMask, $value); }
					else { return "NULL"; }
					break;
				case INTEGER:
	        return intval($value);
					break;
				case DATE:
					if(!is_array($value) && is_int($value)) { $value = va_time($value); }
					if(is_array($value)) { $value = va_date($this->DateMask, $value); }
					else { return "NULL"; }
					break;
				case TIME:
					if(!is_array($value) && is_int($value)) { $value = va_time($value); }
					if(is_array($value)) { $value = va_date($this->TimeMask, $value); }
					else { return "NULL"; }
					break;
				case TIMESTAMP:
					if(!is_array($value) && is_int($value)) { $value = va_time($value); }
					if(is_array($value)) { $value = va_date($this->TimestampMask, $value); }
					else { return "NULL"; }
					break;
			}
			if($is_delimiters) {
				$value = "'" . $value . "'";
			}
		} 
		else if($use_null) 
		{
			$value = "NULL";
		} 
		else 
		{
			if($value_type == INTEGER || $value_type == FLOAT || $value_type == NUMBER) {
				$value = 0;
			} else if ($is_delimiters) {
				$value = "''";
			}
		} 
		return $value;
	}
	function table_exist($table_name)
	{
			$sql= "SHOW TABLES FROM ".$this->DBDatabase;
			$this->query($sql);
			$return=0;
			while ($this->next_record())
			{
				if($this->f(0)==$table_name)
				{$return=1;
				break;
				}
			}
	  return $return;
	}
      function last_id(){
		return mysqli_insert_id($this->LinkID);
      }
}
?>