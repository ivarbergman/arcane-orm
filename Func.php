<?php


class Func
{
  var $name;
  var $value;

  public function __construct($name)
  {
    $this->name = $name;
  }

  public function sql($exp = "")
  {
    if ($exp)
      {
	return "{$this->name}({$exp})";
      }
    else if ($this->value)
      {	
	return "{$this->name}({$this->value})";
      }
    return "{$this->name}()";
  }

  public function __toString()
  {
    return $this->sql();
  }

  public static function length()
  {
    $f = new Func("LENGTH");
    return $f;
  }

  public static function sum()
  {
    $f = new Func("SUM");
    return $f;
  }

  public static function max()
  {
    $f = new Func("MAX");
    return $f;
  }

  public static function min()
  {
    $f = new Func("MIN");
    return $f;
  }

  public static function lower()
  {
    $f = new Func("LOWER");
    return $f;
  }

  public static function upper()
  {
    $f = new Func("UPPER");
    return $f;
  }


  public static function reverse()
  {
    $f = new Func("REVERSE");
    return $f;
  }

  public static function date()
  {
    $f = new Func("DATE");
    return $f;
  }

  public static function not()
  {
    $f = new Func("NOT");
    return $f;
  }

  public static function uuid()
  {
    $f = new Func("UUID");
    return $f;
  }

  public static function md5()
  {
    $f = new Func("MD5");
    return $f;
  }


  public static function now()
  {
    $f = new Func("NOW");
    return $f;
  }


  public static function strcmp($string)
  {
    $list = func_get_args();
    if (count($list) == 0)
      {
	return ;
      }
    $args = "";
    
    foreach ($list as $a)
      {
	if (is_object($a) && method_exists($a, 'sql'))
	  {
	    $args[] = $a->sql();
	  }
	else
	  {	    
	    $args[] = $a;
	  }
      }

    $f = new Func("STRCMP");
    $f->value = implode($args, ',');
    return $f;
  }

  public static function exp($exp, $argv)
  {
    $argv = func_get_args();
    
    if (count($argv) == 0)
      {
	return ;
      }
    $exp = array_shift($argv);

    $args = array();
    foreach ($argv as $a)
      {
	if (is_object($a) && method_exists($a, 'sql'))
	  {
	    $args[] = $a->sql();
	  }
	else
	  {	    
	    $args[] = $a;
	  }
      }
    Debugger::log("((((((((((((((((((((((((((((((((((((((((((((((((((");
    Debugger::log($exp);
    Debugger::log($args);
    $str = vsprintf($exp, $args);
    Debugger::log($str);


    $f = new Func("");
    $f->value = $str;
    return $f;
  }


  public static function addtime($list)
  {

    $list = func_get_args();
    if (count($list) == 0)
      {
	return ;
      }
    $sql = "";
    $sep = "";
    
    foreach ($list as $a)
      {
	if ($a instanceof Attribute)
	  {
	    $sql .= $sep . $a->sql();
	  }
	else if ($a instanceof Func)
	  {
	    $sql .= $sep . $a->sql();
	  }
	else
	  {	    
	    $sql .= $sep . $a;
	  }
	$sep = ", ";
      }

    $f = new Func("ADDTIME");
    $f->value = $sql;
    return $f;
  }

  public static function date_add($list)
  {

    $list = func_get_args();
    if (count($list) == 0)
      {
	return ;
      }
    $sql = "";
    $sep = "";
    
    foreach ($list as $a)
      {
	if ($a instanceof Attribute)
	  {
	    $sql .= $sep . $a->sql();
	  }
	else if ($a instanceof Func)
	  {
	    $sql .= $sep . $a->sql();
	  }
	else
	  {	    
	    $sql .= $sep . $a;
	  }
	$sep = ", ";
      }

    $f = new Func("DATE_ADD");
    $f->value = $sql;
    return $f;
  }

  public static function count($a = '*')
  {
    $f = new Func("COUNT");
    if ($a instanceof Attribute )
      {
	$f->value = $a->sql();
      }
    else
      {
	$f->value = $a;
      }
    return $f;
  }

  public static function count_distinct($a = '*')
  {
    $f = new Func("COUNT");
    if ($a instanceof Attribute )
      {
	$f->value = 'distinct '. $a->sql();
      }
    else
      {
	$f->value = 'distinct '. $a;
      }
    return $f;
  }

  public static function group_concat()
  {
    $f = new Func("GROUP_CONCAT");
    return $f;
  }

  public static function concat($list)
  {

    $list = func_get_args();
    if (count($list) == 0)
      {
	return ;
      }
    $sql = "";
    $sep = "";
    
    foreach ($list as $a)
      {
	if ($a instanceof Attribute)
	  {
	    $sql .= $sep . $a->sql();
	  }
	else
	  {	    
	    $sql .= $sep . $a;
	  }
	$sep = ", ";
      }

    $f = new Func("CONCAT");
    $f->value = $sql;
    return $f;
  }


  public static function add($list)
  {

    $list = func_get_args();
    if (count($list) == 0)
      {
	return ;
      }
    $sql = "";
    $sep = "";
    
    foreach ($list as $a)
      {
	if ($a instanceof Attribute)
	  {
	    $sql .= $sep . $a->sql();
	  }
	else
	  {	    
	    $sql .= $sep . $a;
	  }
	$sep = " + ";
      }

    $f = new Func("");
    $f->value = $sql;
    return $f;
  }

  public static function replace($list)
  {
    Log::dbg("Func::replace()");
    $list = func_get_args();
    Log::dbg($list);
    list($match, $replace) = $list[0];
    $f = new FuncReplace();
    $f->value = "'$match', '$replace'";
    Log::dbg($f->sql());
    return $f;
  }

  public static function oid()
  {
    $con = APdo::get();
    $res = $con->fetch("SELECT UUID() AS uuid;");
    return $res[0]["uuid"];
  }
}

class FuncReplace extends Func
{

  public function __construct()
  {
    $this->name = "REPLACE";
  }

  public function sql($exp = "")
  {
    if ($exp)
      {
	return "{$this->name}({$exp}, {$this->value})";
      }
    else if ($this->value)
      {	
	return "{$this->name}({$this->value})";
      }
    return "{$this->name}()";
  }

}

?>
