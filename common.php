<?php


function iskey($key, $array)
{
  return array_key_exists($key, $array);
}

function aval(&$array, $key, $default = null)
{
  return array_key_exists($key, $array) ? $array[$key] : $default;
}

function array_push_after($arr,$new,$pos)
{
  if (!is_array($new))
    {
      $new = array($new);
    }
    if(is_int($pos)) 
      {
	$result = array_merge(array_slice($arr,0,$pos+1), $new, array_slice($arr,$pos+1));
      }
    else
      {
        foreach($arr as $k=>$v)
	  {
	    $result[$k]=$v;
	    if($k == $pos)
	      {
		$result = array_merge($result,$new);
	      }
	  }
      }
    return $result;
}

function is_assoc(&$arr)
{
  return (is_array($arr) && (!count($arr) || count(array_filter(array_keys($arr),'is_string')) == count($arr)));
}


if (!function_exists("spl_object_hash"))
  {
    function spl_object_hash($obj) 
    { 
      if (!isset($obj->_oid))
	{
	  $obj->_oid = uniqid();
	}
      return $obj->_oid;
    }
  }





