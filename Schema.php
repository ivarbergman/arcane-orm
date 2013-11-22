<?php

class Schema
{
  var $cs;
  var $database;
  var $database_alias;
  var $pdox;

  static function load($pdox)
  {
    $schema = new Schema($pdox);
    $api = $GLOBALS["ARCANE_OUT_PATH"]."/".$schema->database_alias."_api.php";
    if (!file_exists($api))
      {
	$schema->generate();
      }

    if ($GLOBALS["ARCANE_AUTO_GENERATE"] && !$schema->checksum())
      {
	$schema->generate();
      }

    //Log::dbg("db schema include ". $api);
    include_once($api);

    if (file_exists($GLOBALS["ARCANE_MODEL_PATH"].$schema->database_alias."_api_local.php"))
      {
	//Log::dbg("db schema include local: " . $GLOBALS["ARCANE_MODEL_PATH"].$schema->database_alias."_api_local.php");
	include_once($GLOBALS["ARCANE_MODEL_PATH"].$schema->database_alias."_api_local.php");
      }
    else if (file_exists($GLOBALS["ARCANE_OUT_PATH"].$schema->database_alias."_api_local.php"))
      {
	//Log::dbg("db schema include local2: " . $GLOBALS["ARCANE_OUT_PATH"].$schema->database_alias."_api_local.php");
	include_once($GLOBALS["ARCANE_OUT_PATH"].$schema->database_alias."_api_local.php");
      }
  }

  function __construct($pdox)
  {
    $this->pdox = $pdox;
    $this->database = $pdox->database;
    $this->database_alias = $pdox->database_alias;
  }

  function checksum()
  {
    $cs_file = $GLOBALS["ARCANE_OUT_PATH"]."/".$this->database_alias."_api_cs.php";
    if (!file_exists($cs_file))
      {
	return false;
      }
    $cs =  file_get_contents($GLOBALS["ARCANE_OUT_PATH"]."/".$this->database_alias."_api_cs.php");
    $this->pdox->execute("select @CS:='CS_SEED_STR';");
    $this->pdox->execute("select @CS:=MD5(CONCAT(TABLE_NAME, CREATE_TIME, @CS)) from information_schema.tables where TABLE_SCHEMA=:database;", array("database" => $this->database));
    $result = $this->pdox->fetch("select @CS as CS;");
    $this->cs = $result[0]["CS"];
    if ($cs == $this->cs)
      {
	return true;
      }

    return false;
  }



  function generate()
  {
    if (!$GLOBALS["ARCANE_AUTO_GENERATE"])
      {
	return true;
      }

    $cs_file = $GLOBALS["ARCANE_OUT_PATH"]."/".$this->database_alias."_api_cs.php";
    if (file_exists($cs_file))
      {
	$cs = file_get_contents($cs_file);
      }

    $dbis = BaseArcaneDB::information_schema($this->database_alias);


    $t = $dbis->tables();
    $t->TABLE_SCHEMA->eq = $this->database;
    while ($t->next())
      {
	      if (!preg_match('/^[a-zA-Z0-9_-]+$/', $t->TABLE_NAME))
	      {
		      continue;
	      }
	$schema[$t->TABLE_NAME] = array();
	$schema[$t->TABLE_NAME]["col"] = array();
	$schema[$t->TABLE_NAME]["rel"] = array();
	$schema[$t->TABLE_NAME]["rev"] = array();
      }

    $c = $dbis->columns();
    $c->TABLE_SCHEMA->eq = $this->database;
    $columns = array();
    while ($c->next())
      {
	$columns[$c->TABLE_NAME][] = $c->_result->toArray();
      }

    $kcu = $dbis->key_column_usage();
    $kcu->TABLE_SCHEMA->eq = $this->database;
    $relations = array();
    while ($kcu->next())
      {
          $relations[$kcu->TABLE_NAME][] = $kcu->_result->toArray();
      }

    $kcu = $dbis->key_column_usage();
    $kcu->TABLE_SCHEMA->eq = $this->database;
    $rev_relations = array();
    while ($kcu->next())
    {
        $rev_relations[$kcu->REFERENCED_TABLE_NAME][] = $kcu->_result->toArray();
      }

    foreach ($schema as $t => $a)
    {
        foreach (@$columns[$t] as $idx => $col) {

            $schema[$t]["col"][$col['COLUMN_NAME']] = $col['DATA_TYPE'];
            if ($col['COLUMN_KEY'] == 'PRI')
            {
                $schema[$t]["pri"][] = $col['COLUMN_NAME'];
            }

            if ($col['COLUMN_KEY'] == 'PRI' && $col['EXTRA'] == 'auto_increment')
            {
                $schema[$t]["auto"] = $col['COLUMN_NAME'];
            }

            if ($col['COLUMN_KEY'] == 'PRI' && $col['COLUMN_TYPE'] == 'char(36)')
            {
                $schema[$t]["uuid"] = $col['COLUMN_NAME'];
            }
        }

        if (isset($relations[$t]))
        {
            foreach ($relations[$t] as $idx => $rel) {

                if (!is_null($rel['REFERENCED_TABLE_NAME']))
                {
                    $schema[$t]["rel"][$rel['REFERENCED_TABLE_NAME']] = $rel['COLUMN_NAME'];
                }
            }
        }
        if (isset($rev_relations[$t]))
        {
            foreach (@$rev_relations[$t] as $idx => $rel) {

                if (!is_null($rel['TABLE_NAME']))
                {
                    $schema[$t]["rev"][$rel['TABLE_NAME']] = $rel['COLUMN_NAME'];
                }

            }
        }
    }

    Log::dbg($schema);

    $tlp = file_get_contents($GLOBALS["ARCANE_PATH"]."/schema.tpl.php");

    ob_start();
    eval("echo '<?php\n'; ?>\n".$tlp. "\n<?php echo '?>\n';");
    $code = ob_get_contents();
    ob_end_clean();

    Log::dbg("Schema.php: write api files: " . $GLOBALS["ARCANE_OUT_PATH"].$this->database_alias."_api.php");
    file_put_contents($GLOBALS["ARCANE_OUT_PATH"].$this->database_alias."_api.php", $code);
    file_put_contents($GLOBALS["ARCANE_OUT_PATH"].$this->database_alias."_api_cs.php", $this->cs);

    return $code;
  }

}

function tpl_string($val)
{
  if (isset($val))
    return "\"".$val."\"";
  return "null";
}
function tpl_array($array)
{
  if (! is_array($array))
    {
      return "null";
    }
  $assoc = false;
  if (is_assoc($array))
    {
      $assoc = true;
    }
  $str = "array(";
  $sep = "";
  foreach ($array as $k => $v)
    {
      $str .= "$sep";
      if ($assoc)
	{
	  $str .= "'$k'=>";
	}
      $str .= "'$v'";
      $sep = ",";
    }
  $str .= ")";
  return $str;
}

?>
