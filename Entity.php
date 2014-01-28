<?php

class Entity extends Base
{

  public $_attr = array();
  public $_etx = array();
  public $_relattr = array();
  public $_union = array();

  public $_statement;
  public $_result;
  public $_found_rows;
  public $_count;
  public $_manual;

  public $_error_code = "";
  public $_error = "";
  public $_last_sql = "";
  public $_meta = array();

  public $_on_duplicate_update = false;
  public $_cfg_fetch_all = true;



  function __construct($name, $args = null, $pdox = null, $bridge = null)
  {

    $this->_name = $name;
    $this->_alias = "_".$name;
    $this->_bridge = $bridge;
    $this->_pdox = $pdox;

    foreach ($this->_col as $name => $type)
      {
	$this->$name;
      }


    if ($args)
      {
	$this->__invoke($args);
      }
    else
      {
	$this->reset();
      }
  }

  function has_attribute($name)
  {
	  return isset($this->_attr[$name]);
  }
  function meta()
  {
    if (isset($this->_bridge->orders))
      {

	foreach ($this->_bridge->orders as $o)
	  {
	    list($attr, $dir) = $o;
	    $o = new StdClass();
	    $o->entity = $attr->name();
	    $o->attribute = $attr->name;
	    $o->dir = $dir;
	    $this->_meta['orders'][] = $o;
	  }
      }

    if (isset($this->_bridge->limit))
      {
	$this->_meta['limit'] = $this->_bridge->limit;
	if (isset($this->_bridge->page))
	  {
	    $this->_meta['page'] = $this->_bridge->page;
	  }
      }
  }

  function reset()
  {

    $this->_active = false;
    $this->_on_duplicate_update = false;
    $this->_readonly = array();

    foreach ($this->_attr as $name => $a)
      {
	if ($a instanceof Attribute)
	  {
	    $a->reset();
	  }
	else
	  {

	  }
      }

    if ($this->_bridge)
      {
	$this->_bridge->add_entity($this);
      }
    else
      {

      }
  }


  function __destruct()
  {
    unset($this->_name);
    unset($this->_alias);
    unset($this->_bridge);
    unset($this->_database);
    foreach ($this->_attr as $name => $entity)
      {
	unset($this->_attr[$name]);
      }
    unset($this->_attr);
  }
  function __invoke($args = null)
  {

    $this->reset();

    if (func_num_args() > 0)
      {
	$args = is_array($args) ? $args : array($args);
	$this->pk()->eq($args);
	$this->next();
      }
  }

  function __get($name)
  {
    //Log::dbg("__get($name)");

    // defined attribute first
    if (array_key_exists($name, $this->_attr))
      {
	if (  $this->_attr[$name]->is_scalar())
	  {
	    return $this->_attr[$name]->value;
	  }
	return $this->_attr[$name];
      }

    // Has sub-result
    if ( isset( $this->_result) && $this->_result->subset($name) != false )
      {
	$db = $this->db();
	$r = $this->_result->subset($name);
	$o = $db->entity_from_array($name, $r);
	return $o;
      }


    // Defined relations
    if ( isset( $this->_rel[$name]) == true )
      {
	return $this->rel($name);
      }
    else if ( isset( $this->_rev[$name]) == true )
      {
	return $this->rev($name);
      }


    // If in col or etx, nut not as Attribute insrtance.
    if ( ( isset($this->_col[$name]) || isset($this->_etx[$name]) ) )
      {
	$this->add_attribute($name);
      }

    // If not active and col does not exists.
    if (!$this->_active)
      {
	if ( isset( $this->_col[$name]) == false )
	  {
	    $this->_etx[$name] = true;
	    $this->add_attribute($name);
	  }
      }


    if (!isset($this->_attr[$name]))
      {
	return false;
      }

    return $this->_attr[$name];
  }

  function __set($name, $value)
  {
    //Log::dbg("__set($name, $value)");

    $type = "scalar";
    if ($value instanceof Attribute)
      {
	$type = "sql";
	$value = $value->sql();
      }
    else if ($value instanceof Func)
      {
	$type = "sql";
	$value = $value->sql();
      }
    else if ($value instanceof Entity && isset( $this->_rel [$name]) )
      {
	$name = $this->_rel[$name];
	$value = $value->key();
      }

    if ( isset( $this->_col[$name]) == false )
      {
	$this->_etx[$name] = true;
      }

    if ( isset( $this->_attr[$name]) == false )
      {
	$this->add_attribute($name);
      }

     $this->_attr[$name]->value = $value;
     $this->_attr[$name]->active = true;
     $this->_attr[$name]->type = $type;
  }

  function __toString()
  {
    $str = "";

    if (!$this->active())
      {
	return $this->sql();
      }

    if (isset($this->_attr["name"]))
      {
	return "".$this->name;
      }

    if (isset($this->_attr["title"]))
      {
	return "".$this->title;
      }

    if ($this->_attr)
      {
	foreach ($this->_attr as $name => $attr)
	  {
	    $str .= $attr." , ";
	  }
      }
    else
      {
	$str = "" . $this->name()."\n";
      }
    return $str;
  }


  function add_attribute($name)
  {
    $this->_attr[$name] = $this->new_attribute($name);
  }


  /* Entity operations */

  function load()
  {
    if (!$this->active())
      {
	$q = $this->select();
      }
  }



  /**
   * Return the current result set row as an array matching an entity.
   * @name
   */
  function result($name = null, $attr = null)
  {

    if (!$this->_result)
      {
	Log::dbg(" no _result");
	return false;
      }

    $row = $this->_result->current();

    if (!$row)
      {
	Log::dbg(" no row");
	return false;
      }


    if (!$name)
      {
	$name = $this->name();
      }

    if (!isset( $row[$name]))
      {
	Log::dbg(" no row name $name");
	return false;
      }

    if (!$attr)
      {
	$result = $row;
      }
    else
      {
	$result = $row[$attr];
      }

    return $result;
  }

  function to_array($name = null)
  {

    if (!$this->_result)
      {
	return false;
      }

    if (!$name)
      {
	$name = $this->name();
      }

    if (!isset( $this->_result[0][$name]))
      {
	return false;
      }

    $result = array();
    foreach ($this->_result as $row)
      {
	$result[] = $row[$name];
      }

    return $result;
  }

  function rewind()
  {
    if (!$this->_result)
      {
	return false;
      }
    $this->_result->rewind();

  }
  function next()
  {
    $this->load();

    if (!$this->_result)
      {
	return false;
      }

    $row = $this->_result->next();
    if ($row)
      {
	$this->feed($row);
      }
    else
      {
	$this->unfeed();
	return false;
      }

    return true;
  }


  public function pk()
  {
    if ($this instanceof Entity)
      {
	$pk = new PrimaryKey($this);
	$vars = get_class_vars(__CLASS__);
	foreach ($vars as $name => $v)
	  {
	    $pk->$name =& $this->$name;
	  }
      }
    return $pk;
  }

  function unfeed()
  {
    foreach ($this->_attr as $name => $a)
      {
	if ($a instanceof Attribute)
	  {
	    $a->value = null;
	  }
      }
  }

  function feed_pk($data_array)
  {
    foreach ($this->pk as $name => $type)
      {
	if (isset( $data_array[$name]))
	  {
	    $this->$name = $data_array[$name];
	  }
      }
  }
  function feed($data_array)
  {
    if ($data_array instanceof Entity)
      {

	  $array = array();
	foreach ($data_array->_col as $key => $type)
	  {
	    $array[$key] = $data_array->$key;
	  }
	$data_array = $array;
      }
    else if (is_object($data_array))
      {
	$data_array = get_object_vars($data_array);
      }

    if (!is_array($data_array))
      {
	return ;
      }

    $this->_active = true;
    if ($this->_etx)
      {
	foreach ($this->_etx as $name => $value)
	  {
	    $key = $this->alias()."__".$name;
	    if (array_key_exists($key, $data_array))
	      {
		$this->$name = $data_array[$key];
	      }
	    if (array_key_exists($name, $data_array))
	      {
		$this->$name = $data_array[$name];
	      }
	  }
      }

    if ($this->_col)
      {
	foreach ($this->_col as $name => $type)
	  {
	    $key = $this->alias()."__".$name;
	    if (array_key_exists($key, $data_array))
	      {
		$this->$name = $data_array[$key];
	      }
	    if (array_key_exists($name, $data_array))
	      {
		$this->$name = $data_array[$name];
	      }
	  }
      }
    else
      {
	foreach ($data_array as $name => $value)
	  {
	    if (! is_numeric($name))
	      {
		$this->$name = $value;
	      }
	  }
      }
  }



  function on_duplicate_update($value = null)
  {
    if (is_null($value))
      {
	return $this->_on_duplicate_update;
      }
    $this->_on_duplicate_update = $value;
  }

  function insert_batch($flush = false)
  {
    if ($flush)
      {
	$sql = $this->_bridge->insert_batch_flush($this);
	$result = $this->execute($sql);
      }
    else
      {
	$this->_bridge->insert_batch($this);
	$this->reset();
      }
  }

  function insert()
  {

    if ($this->_uuid && !$this->_attr[$this->_uuid]->value)
      {
	$oid = Func::oid();
	$this->{$this->_uuid} = $oid;
      }

    $sql = $this->_bridge->insert($this);
    $result = $this->execute($sql);

    if ($this->_auto && !$this->_attr[$this->_auto]->value)
      {
	$this->{$this->_auto} = $this->_pdox->lastInsertId();
      }

    return $result;
  }

  function replace()
  {

    if ($this->_uuid && !$this->_attr[$this->_uuid]->value)
      {
	$oid = Func::oid();
	$this->{$this->_uuid} = $oid;
      }

    $sql = $this->_bridge->replace($this);
    $result = $this->execute($sql);

    if ($this->_auto && !$this->_attr[$this->_auto]->value)
      {
	$this->{$this->_auto} = $this->_pdox->lastInsertId();
      }

    return $result;
  }


  function update()
  {
    $sql = $this->_bridge->update($this);
    $result = $this->execute($sql);
    return $result;
  }

  function select()
  {
      $this->_active = true;

      if ($this->_bridge->union)
      {
          $sql = $this->_bridge->select_with_union($this);
      }
      else
      {
          $sql = $this->_bridge->select($this);
      }
      $result = $this->fetch($sql);
      return $result;
  }

  function union()
  {
      $this->_active = true;
      $_sql = $this->_bridge->union($this);
      return $this;
  }

  function union_end()
  {
      $this->_ignore_single = true;
      $this->_bridge->union_end($this);
      return $this;
  }


  /* Record operations */
  function save()
  {
    if ($this->key())
      {
	$sql = $this->_bridge->save($this);
	$result = $this->execute($sql);
      }
    else
      {
	$result = $this->insert();
	if ($result == 1)
	  {
	    $this->__invoke($this->key());
	  }
      }
    return $result;
  }

  function remove()
  {
    $sql = $this->_bridge->remove($this);
    $result = $this->execute($sql);
    return $result;
  }

  function delete()
  {
    $sql = $this->_bridge->delete($this);
    $result = $this->execute($sql);
    return $result;
  }

  function key()
  {
    $pk = $this->pk();
    if ($pk)
      {
	return $pk->value();
      }
    return null;
  }

  function db()
  {
    if ($this->_database)
      {
	return BaseArcaneDB::db($this->_database);
      }
    else if ($this->_pdox->database_alias)
      {
	return BaseArcaneDB::db($this->_pdox->database_alias);
      }
    return BaseArcaneDB::get();
  }

  function rel($name)
  {

    $o = null;
    if (isset( $this->_rel[$name]) && !isset($this->_relattr[$name]))
      {

	$db = $this->db();
	if (isset($this->_result) && $this->_result->subset($name))
	  {
	    $r = $this->_result->subset($name);
	    $o = $db->entity_from_array($name, $r);
	  }
	else if ($this->active())
	  {

	    $fk_name = $this->_rel[$name];
	    $fk = $this->_attr[$fk_name];
	    $o = $db->entity($name, null, $this->_pdox, $this->_bridge);
	    $o->_database = $this->_database;
	    $o->__invoke($fk->value);
	  }
	else
	  {
	    $o = $db->entity($name, null, $this->_pdox, $this->_bridge);
	    $o->_database = $this->_database;
	  }
	$this->_relattr[$name] = $o;
      }
    return $this->_relattr[$name];
  }

  function rev($name)
  {

    $o = null;
    if (isset( $this->_rev[$name]))
      {
	$db = $this->db();
	if (isset($this->_result) && ( $this->_result->subset($name)))
	  {
	    $r = $this->_result->subset($name);
	    $o = $db->entity_from_array($name, $r);
	  }
	else if ($this->active())
	  {

	    $pk = $this->key();
	    $o = $db->$name();
	    $o->_database = $this->_database;
	    $o->{$this->_rev[$name]}->eq = $pk;
	  }
	else
	  {

	    $o = $db->entity($name, null, $this->_pdox);
	    $o->_database = $this->_database;
	  }
      }
    return $o;
  }

  function error()
  {
    return $this->_error;
  }

  function manual($sql = null)
  {
      if ($sql)
      {
          $this->_manual = $sql;
      }
      else
  {
    return $this->_manual;
  }
  }


  public function found_rows()
  {
    return $this->_result->found_rows($this->_pdox);
  }

  public function count()
  {
    if (is_numeric($this->_count))
      {
	return $this->_count;
      }
    $this->load();
    return $this->_count;
  }


  function sql()
  {
    $sql = $this->name() . " AS " . $this->alias();
    return $sql;
  }

  function report()
  {
    $str = "";
    if ($this->_bridge)
      {
	$str .= $this->_bridge->last_sql."\n";
      }
    if ($this->_statement)
      {
	$str .= " /* ErrorCode ".$this->_statement->errorCode() . " */\n";
      }

    $str .= " /* Active ".$this->active() . " */\n";
    if ($this->active())
      {
	$str .= " /* Result " . $this->count() . " */\n";
      }
    return $str . "\n";
  }

  function json($array = array())
  {
    $this->load();


    $array["request"] = $_GET;

    $array["meta"] = array("entity" => $this->name(),
			   "attribute" => array_keys($this->_col),
			   "type" => "list");
    $array["result"] = $this->_result;

    return

    $array["rows"] = $this->_result->found_rows($this->_pdox);
    $result = json_encode($array);

    if (isset( $_GET["callback"]))
      {
	if ($_GET["callback"])
	  {
	    $result = "{$_GET["callback"]}('$result')";
	  }
      }

    return $result;
  }

  function extend()
  {

    if ( count($this->_rel) > 0 )
      {
	foreach ($this->_rel as $name => $attr)
	  {

	    $e = $this->rel($name);
	    $this->outer_join($e);
	  }
      }
  }

  function join($e)
  {

    $this->_bridge->join("JOIN", $this, $e);
  }

  function outer_join($e)
  {
      $entities = func_get_args();
      $this->_bridge->join("LEFT OUTER JOIN", $this, $entities);
  }

  function left_join($e)
  {
      $entities = func_get_args();
      $this->_bridge->join("LEFT OUTER JOIN", $this, $entities);
  }


  function display($name)
  {
    $result = $this->$name;
    if ($result instanceof Entity)
      {
	$result = "<span data-op='Select' data-value='{$result->key()}' data-entity='{$result->_name}'>{$result}</span>";
      }
    return $result;
  }

  function odd_even()
  {
  }

  function fw($load = true)
  {
    $array = array();

    if ($load) {
      $this->load();
    }


    $array["request"] = $_GET;

    $array["meta"] = array("entity" => $this->name(),
			   "view" => $array["request"]["view"],
			   "attribute" => array_keys($this->_col),
			   "relation" => $this->_rel,
			   "type" => "list");
    $array["result"] = $this->_result;

    $file = $GLOBALS["BASE_PATH"]."view_".$this->name()."_".$array["request"]["view"].".php";
    if (!file_exists($file))
      {
	$file = $GLOBALS["BASE_PATH"]."view_".$array["request"]["view"].".php";
      }
    $tlp = file_get_contents($file);

    ob_start();
    $source = "?>". $tlp ."<? ";

    error_reporting(E_ALL | E_STRICT);
    set_error_handler('my_eval_error_handler');
    $result = eval($source);
    restore_error_handler();

    $array["html"] = ob_get_contents();
    ob_end_clean();

    return $array;
  }

  function template($tpl = null)
  {

    $file = $GLOBALS["BASE_PATH"]."view_".$tpl.".php";
    Log::dbg($file);
    if (!file_exists($file))
      {
	return false;
      }
    $code = file_get_contents($file);
    ob_start();
    $source = "?>". $code ."<? ";
    eval($source);
    $result = ob_get_contents();
    ob_end_clean();
    Log::dbg("TEMPLATE!°°!!!!!");
    Log::dbg($code);
    return $result;
  }

  function properties()
  {

    $result = array();
    if ($this->_rel)
      {
	$result = array_keys($this->_rel);
      }

    if ($this->_col)
      {
	$result = array_merge($result, array_keys($this->_col));
      }

    return $result;
  }

  function page($num = null)
  {
    if ($this->active())
      {
	if (isset($this->_meta['page']))
	  {
	    return $this->_meta['page'];
	  }
	return null;
      }
    $this->_bridge->add_page($num);
  }

  function limit($num = null)
  {
    Log::dbg("Entity::limit($num = null)");
    if ($this->active())
      {
	if (isset($this->_meta['limit']))
	  {
	    return $this->_meta['limit'];
	  }
	return null;
      }

    $this->_bridge->add_limit($num);
  }
  function order($name = null, $dir = 'ASC')
  {

    if ($this->active())
      {
	if (isset($this->_meta['orders']))
	  {
	    foreach  ($this->_meta['orders'] as $o)
	      {
		if ($name == null)
		  {
		    return $o;
		  }
		else if ($name == $o->attribute)
		  {
		    return $o;
		  }
	      }
	  }
	return null;
      }


    if (!$name)
      {
	return false;
      }

    $ok = true;
    if (isset($this->_attr[$name]) || isset($this->_etx[$name]))
      {
	$this->_attr[$name]->order($dir);
      }
    else
      {
	$ok = false;
      }

    return $ok;
  }

  function variant($value)
  {
    $this->_bridge->variant["SELECT"][] = $value;
  }

  function execute($sql)
  {
    Log::dbg("execute --------------------------------------- ");
    Log::dbg($sql);
    $ms1 = microtime(true);
    $param = $this->_bridge->values;
    if (count($param)>0)
      {
	Log::dbg($param);
      }

    $this->_statement = $this->_pdox->prepare($sql);
    $this->_statement->execute($param);

    $this->_count = $this->_statement->rowCount();
    $this->meta();
    if ($this->_statement->errorCode() > 0)
      {
	$info = $this->_statement->errorInfo();
	$this->_error = $info[2];
	$this->_error_code = $info[0];
      }

    $this->_bridge->reset();

    $ms2 = microtime(true);
    Log::dbg($ms1.' '.$ms2.' = '.($ms2-$ms1));
    return $this->_count;
  }

  function fetch($sql)
  {

    if ($this->manual())
      {
	$sql = $this->manual();
      }

    $this->execute($sql);

    if ($this->_cfg_fetch_all)
      {
	$this->_result = new ResultArray($this->_statement);
	$this->_count = $this->_result->count();
      }
    else
      {
	$this->_result = new ResultStatement($this->_statement);
	$this->_count = $this->_result->count($this->_pdox);
      }

    Log::dbg("Fetch RowCount " . $this->_count);

    return $this->_count;
  }


}

abstract class Result
{
  private $found_rows;

  abstract function next();
  abstract function rewind();
  abstract function current();
  abstract function count($pdox = null);

  public function found_rows($pdox = null)
  {
    if (!isset($this->found_rows))
      {
	$c = $pdox->fetch("SELECT FOUND_ROWS() AS count;");
	$this->found_rows = (int)$c[0]["count"];
      }
    return $this->found_rows;
  }
  public function subset($entity_name)
  {
    $row = $this->current();

    if (!$row)
      {
	return array();
      }

    $result = array();
    if ($row)
      {
	foreach ($row as $key => $value)
	  {
	    if (strpos($key, $entity_name) === 1 || strpos($key, $entity_name) === 0 )
	      {
		$k = substr($key, strlen($entity_name)+2 + strpos($key, $entity_name));
		$result[$k] = $value;
	      }
	  }
      }
    return $result;
  }

  public function toArray()
  {
    $row = $this->current();

    if (!$row)
      {
	return array();
      }

    $result = array();
    if ($row)
      {
	foreach ($row as $key => $value)
	  {
	    $result[$key] = $value;
	  }
      }
    return $result;
  }

}

class ResultArray extends Result
{

  private $data;
  public $pos;

  public function __construct($statement)
  {
    $data = $statement->fetchAll();
    $this->data =& $data;
    $this->pos = -1;
  }

  public function next()
  {
    $this->pos++;
    $data =  $this->current();
    return $data;
  }

  public function rewind()
  {
    $this->pos = -1;
  }

  public function current()
  {
    $p = ($this->pos < 0) ?  0 : $this->pos;
    $c = count($this->data);

    if ($p < $c)
      {
	return $this->data[$p];
      }
    return false;
  }


  public function count($pdox = null)
  {
    return count($this->data);
  }

}

class ResultStatement extends Result
{

  private $statement;
  private $row;

  public function __construct($statement)
  {
    $this->statement = $statement;
  }

  public function next()
  {
    $this->row = $this->statement->fetch();
    return $this->current();
  }

  public function rewind()
  {
  }

  public function current()
  {
    return $this->row;
  }

  public function count($pdox = null)
  {
    return $this->found_rows($pdox);
  }

}


?>
