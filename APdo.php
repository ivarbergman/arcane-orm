<?php
/*
 *   Copyright (C) 2010  Ivar Bergman
 *
 *   This file is part of the Poq library (Database Layer - a PHP O/R mapping lib).
 *
 *   Poq is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   Poq is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with Poq.  If not, see <http://www.gnu.org/licenses/>.
 */
class APdo extends PDO
{

  public $result = array();
  public $args = array();
  public $hooks = array();

  public $prefix = "r";
  public $throw_exception = false;

  public $host;
  public $server;
  public $driver;
  public $user;
  public $database;
  public $database_alias;
  public $database_md5;
  public $dsn;
  private $passwd;
  public static $query_count = 0;

  public static function get($prop = array(), $alias = null)
  {
    $key = 'APdo_'.(isset($prop["DB_HOST"]) ? $prop["DB_HOST"] : $GLOBALS["DB_HOST"]);
    $key .= '_'.(isset($prop["DB_DATABASE"]) ? $prop["DB_DATABASE"] : $GLOBALS["DB_DATABASE"]);
    

    if (isset($_REQUEST[$key]) == false)
      {
	$_REQUEST[$key] = new APdo($prop, $alias);	
      }
    return $_REQUEST[$key];
  }

  function __construct($prop = array(), $alias = null) {

    //Log::dbg($alias);
    //Log::dbg($prop);
    $this->host     = array_key_exists("DB_HOST", $prop) ? $prop["DB_HOST"] : $GLOBALS["DB_HOST"];
    $this->user     = array_key_exists("DB_USER", $prop) ? $prop["DB_USER"] : $GLOBALS["DB_USER"];
    $this->passwd   = array_key_exists("DB_PASSWD", $prop) ? $prop["DB_PASSWD"] : $GLOBALS["DB_PASSWD"];
    $this->database = array_key_exists("DB_DATABASE", $prop) ? $prop["DB_DATABASE"] : $GLOBALS["DB_DATABASE"];
    $this->prefix   = array_key_exists("DB_DEFAULT_PREFIX", $prop) ? $prop["DB_DEFAULT_PREFIX"] : 
      ( array_key_exists("DB_DEFAULT_PREFIX", $GLOBALS) ? $GLOBALS["DB_DEFAULT_PREFIX"] : $this->prefix );
    $this->database_alias = $alias ?: $this->database;

    if ($this->server == "PGSQL")
      {
	$this->dsn = "pgsql:host={$this->host} port=5432 dbname={$this->database}"; 
      }
    else
      {
	$this->dsn = "mysql:dbname={$this->database};host={$this->host}";
      }
    //Log::dbg("APdo construct ".$this->dsn);
    parent::__construct($this->dsn, $this->user, $this->passwd);

    $this->exec("SET NAMES utf8;");

    $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
    //$this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $this->driver = strtoupper($this->getAttribute(PDO::ATTR_DRIVER_NAME));


  }  


  function close()
  {
    if (isset($_REQUEST["APdo"]))
      {
	unset($_REQUEST["APdo"]);
      }
    return true;
  }

  function prepare($str, $args = array()) 
  {    
    //Log::dbg("APdo prepare ".$this->dsn);
    $s = parent::prepare($str);
    if (!$s)
      {
	//Log::dbg($this->errorInfo());
      }
    $s = new APdoStatement($s);
    $s->prefix = $this->prefix;
    return $s;
  }

  function batch($str, $args = array()) 
  {    
    $lines = preg_split("/[;]/", $str);
    $result = true;
    foreach ($lines as $idx => $line)
      {
	$line = trim($line);
	if ($line)
	  {
	    $st = $this->prepare($line, $args);
	    $result = $st->execute($args) && $result;
	    $st->fetchAll();
	    if (!$result)
	      {
		return $result;
	      }
	  }
      }
    return $result;
  }

  function execute($str, $args = array()) 
  {    
    //Log::dbg("APdo execute ".$this->dsn);
    Log::dbg($str);
    $st = $this->prepare($str, $args);
    $result = $st->execute($args);
    if (!$return)
      {
	//Log::dbg($this->errorInfo());
      }
    return $result;
  }

  function fetch($str, $args = null)
  {
    $st = $this->prepare($str, $args);
    $st->execute($args);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }


  function clear()
  {
    unset($this->hooks);
    $this->hooks = array();
  }

  function hook($hook)
  {
    $this->hooks[] = $hook;
  }

  function begin($clear_hooks = true) 
  {    
    if ($clear_hooks)
      {
	$this->clear();
      }
    $ok = parent::beginTransaction();
    foreach ($this->hooks as $h)
      {
	if ($h instanceof PdoHook)
	  {
	    $h->begin($this);
	  }
      }
    return $ok;
  }

  function commit() 
  {    
    $ok = parent::commit();
    foreach ($this->hooks as $h)
      {
	if ($h instanceof PdoHook)
	  {
	    $h->commit($this);
	  }
      }
    return $ok;
  }

  function rollback() 
  {    
    $ok = parent::rollback();
    foreach ($this->hooks as $h)
      {
	if ($h instanceof PdoHook)
	  {
	    $h->rollback($this);
	  }
      }
    return $ok;
  }

  function __toString()
  {
    return "<APdo>";
  }

}

class APdoStatement 
{

  var $stm;
  var $args = array();
  var $prefix = "r";

  function __construct(&$stm)
  {
    $this->stm = $stm;

  }

  function __call( $name, $args ) {
    if( method_exists( $this->stm, $name ) )
      {
	return call_user_func_array(array($this->stm, $name), $args); 
      }
  }

  function __set( $name, $value ) {
    if( $this->stm && property_exists( $this->stm, $name ) ) 
      {
	$this->stm->$name = $value;
      }
    return;
  }

  function __get( $name ) {
    if( $this->stm && property_exists( $this->stm, $name ) ) 
      {
	return $this->stm->$name;
      }
  }

  function execute($args = null)
  {
    //$this->analys();

    $result = $this->stm->execute($args);
    if (!$result)
	{	  
	  $info = $this->stm->errorInfo();
	  Log::dbg($info[2]);
	}
    APdo::$query_count++;
    return $result;
  }

  function analys()
  {
    $str = $this->stm->queryString;
    if (is_string($str) == false)
      {
	return $str;
      }
    if (strpos($str, ":") === false)
      {
	return $str;
      }
    preg_match_all("|:([a-zA-Z]_)?([a-zA-Z_0-9]*)|",$str, $matches);
    if (count($matches[1])>0)
      {
	foreach ($matches[1] as $i => $m)
	  {
	    if (strlen($m) > 0 && $m[1]=='_')
	      {
		$p = $m[0];
	      }
	    else
	      {
		$p = $this->prefix;
	      }
	    $k = $matches[2][$i];
	    $kn = $matches[0][$i];
	    //Log::dbg("p $p, k $k, kn $kn");
	    switch ($p)
	      {
	      case 'r':
		$v = $_REQUEST[$k];
		break;
	      case 'p':
		$v = $_POST[$k];
		break;
	      case 'g':
		$v = $_GET[$k];
		break;
	      case 'G':
		$v = $GLOBALS[$k];
		break;
	      case 's':
		$v = $_SESSION[$k];
		break;
	      case 'd':
		$v = $this->$k;
		break;
	      case 'c':
		$v = $this->vars->$k;
		break;
	      }
	    if ($v && $this->bindValue($kn, $v, PDO::PARAM_STR))
	      {
		Log::dbg("bindValue($kn, $v)");
	      }
	  }
      }
    return true;
  }

  function __toString()
  {
    return "<APdoStatement>";
  }

}

class PdoHook 
{
  function begin($query)
  {
    return true;
  }
  function commit($query)
  {
    return true;
  }
  function rollback($query)
  {
    return true;
  }
}

?>
