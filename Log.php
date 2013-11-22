<?php

class Log
{

  public $file;
  public $fp;

  public function __construct()
  {

    if ($GLOBALS["DBG_FILE"])
      {
	$this->file = $GLOBALS["DBG_FILE"];
      }
    else
      {
	$this->file = "/tmp/arcane.log";
      }

    $this->open();

  }

  public function __destruct()
  {
    if (is_resource($this->fp))
      {
	fclose($this->fp);
      }
  }

  public function open()
  {
    if (is_resource($this->fp))
      {
	fclose($this->fp);
	unset($this->fp);
      }
    $this->fp = fopen($this->file, "a");
  }


  public function write($str, $newline = 1)
  {
    
    $out = "";
    if (isset($str) == false)
      {
	$out = "[not set]";
      }
    else if (is_array($str))
      {
	$out = $this->array_to_string($str);
      }
    else if (is_object($str))
      {
	$out = $this->array_to_string($str);
      }
    else if (! $out)
      {
	$out = $str;
      }

    //$out = substr($out, 0, 5000);

    $out .= str_repeat("\n", $newline);


    $a = fstat($this->fp);
    if (!$a["nlink"])
      {
	$this->open();
      } 

    fwrite($this->fp, $out);
    fflush($this->fp);
    
  }
  
  public static $log;
  public static function dbg($str, $newline = 1)
  {

    if (!self::$log)
      {
	self::$log = new Log();
      }

    self::$log->write($str, $newline);
  }

  public static function dbg_to_file($file, $str, $newline = 1)
  {
    self::dbg($str, $newline);
  }




  private function array_to_string($array, $depth = 1) 
  { 
    $str = "";
    if ($depth == 1)
	{
	  $str = "array(";
	}
      foreach($array as $name => $value ) 
	{ 
	  $str .= "\n".str_repeat(" ", $depth *4 ) . "'$name' => ";
	  if( is_array( $value ) ) 
	    { 
	      if ($depth<10)
		$str .= $this->array_to_string($value, $depth+1); 
	    } 
	  else if( is_object( $value ) ) 
	    { 
	      if ($depth<10)
		$str .= $this->array_to_string($value, $depth+1); 
	    } 
	  else 
	    { 
	      $str .= "'$value',";
	    } 
	} 
      $str .= ")";
      if ($depth == 1)
	{
	  $str .= ";";
	}
      return $str;
  } 
}

?>
