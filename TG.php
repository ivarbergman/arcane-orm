<?php


$shortopts = "o::"; 
$longopts  = array(
    "code",
    "test",
    "output::"
);

$cols =  exec('tput cols')-10;

$opt = getopt($shortopts, $longopts);

$cli = $_SERVER["_"];
if (!$cli)
  {
    $cli = "php";
  }

if (strpos($_SERVER["SCRIPT_FILENAME"], "TG.php") > 0 )
  {
    TG::output("Find all test*.php");
    TG::output($_SERVER["SCRIPT_FILENAME"]);
    $files = scandir("./", 1);
    foreach ($files as $f)
      {
	if (preg_match("/^test_.*\.php$/", $f))
	  {
	    $tests[] = $f;
	  }
      }
    foreach ($tests as $t)
      {
	$output = "";
	$code = file_get_contents($t);
	$rv = exec("$cli $t", $output, $result);

	if ($result != 1 || !$rv)
	  TG::output(sprintf("%-{$cols}s [FAILED]", $t, $output[1]));
	else
	  TG::output(sprintf("%-{$cols}s [OK]", $t, $output[1]));
      }
    exit;
  }

if (isset($opt["code"]))
  {
    $code = file_get_contents($_SERVER["SCRIPT_FILENAME"]);
    $output = preg_replace("/TG::name\(\"([^()]*)\"\);/", "// $1", $code);
    $output = preg_replace("/TG::comment\(\"([^()]*)\"\);/", "// $1", $code);
    $output = preg_replace("/TG::start\(\"([^()]*)\"\);/", "/* $1 */", $output);
    $output = preg_replace("/TG::example\((.+)\);/", "echo($1);", $output);
    $output = preg_replace("/TG::[^;]+;/", "", $output);
    $output = preg_replace("/\n+/", "\n", $output);
    $output = preg_replace("/\/\//", "\n//", $output);
    $output = preg_replace("/\/\*/", "\n\n/*", $output);
    $output = preg_replace("/\?>/", "\n?>", $output);
    $output = preg_replace("/<\?php/", "<?php\n", $output);
    $output = preg_replace("/.*tollgate.php.*\n/", "", $output);
    echo $output.PHP_EOL;
    exit;
  }



function tg_eval_error_handler($errno, $errstr, $errfile, $errline)
{
  Log::dbg("Error number ".$errno."\nError: ". $errstr."\nFile: ". $errfile."\nLine: ".  $errline."!!");
  Log::dbg(debug_backtrace());
  TG::report("Error number ".$errno."\nError: ". $errstr."\nFile: ". $errfile."\nLine: ".  $errline."!!");
}
set_error_handler('tg_eval_error_handler');  


class TG
{
  static $_name = "";
  static $_tg_num = 0;
  static $_test_num = 0;
  static $_fail_num = 0;
  static $_last_memeory = 0;
  static $_delta_memeory = 0;
  static $_ignore_memeory = 0;
  static $_delta_sum = 0;

  public function __construct()
  {
    
  }

  public static function output($str)
  {
    if (true)
      {
	echo $str.PHP_EOL;
      }
  }

  public static function name($str)
  {
    self::$_tg_num++;
    self::$_test_num = 0;
    self::$_fail_num = 0;
    self::$_name = $str;
    self::output($str);
  }

  public static function comment($str)
  {
    return ;
  }

  public static function start($str)
  {
    self::output("===========================================================================");
    self::$_tg_num = 0;
    self::output("Test file: ".$_SERVER["SCRIPT_FILENAME"]);
    self::output($str);
    self::output("---------------------------------------------------------------------------");
  }

  public static function end()
  {
    if (self::$_fail_num > 0)
      {
	return exit(0);
      }

    if (self::$_test_num == 0)
      {
	self::report("Test ended without any tests performed.");
      }

    self::$_tg_num = 0;
    return exit(1);
  }

  public static function report($str)
  {
    self::$_fail_num++;
    self::output(" [".self::$_test_num."] - " . $str);
  }

  public static function example($str)
  {
    self::output(" [example] - " . $str);
  }

  public static function equal($arg1, $arg2, $msg = "")
  {
    self::$_test_num++;
    if ($arg1 !== $arg2)
      {
	$arg1_type = gettype($arg1);
	$arg2_type = gettype($arg2);
	self::report("expected <$arg1_type:$arg1> got <$arg2_type:$arg2>");
      }
  }

  public static function equal_class($arg1, $arg2, $msg = "")
  {
    self::$_test_num++;
    $arg1_class = get_class($arg1);
    $arg2_class = get_class($arg2);
    if ($arg1_class !== $arg2_class)
      {
	self::report("expected equal class got <$arg1_class> and <$arg2_class>");
      }
  }

  public static function mem_ignore($number)
  {
    self::$_ignore_memeory = $number;
  }

  public static function mem_check()
  {
    if (self::$_delta_sum > 0)
      {
	self::report("memory increase " . self::$_delta_sum);
	self::report("memory increase " . ( self::$_delta_sum / self::$_last_memeory) . " %");
      }
  }

  public static function mem_record()
  {

    self::$_test_num++;
    
    if (self::$_ignore_memeory > 0)
      {
	self::$_ignore_memeory--;
	self::$_last_memeory = memory_get_usage();
	return ;
      }
    if (self::$_last_memeory == 0)
      {
	self::$_last_memeory = memory_get_usage();
      }
    else
      {
	self::$_delta_memeory = memory_get_usage() - self::$_last_memeory;
	self::$_last_memeory = memory_get_usage();
	self::$_delta_sum += self::$_delta_memeory;
      }
    Log::dbg("MEM " . self::$_last_memeory);
  }

  public static function is_object($obj)
  {
    self::$_test_num++;
    $result = is_object($obj);
    if (!$result)
      {
	$arg_type = gettype($obj);
	self::report("did not pass is_object <$arg_type:$obj>");
      }
  }


  public static function is_string($obj)
  {
    self::$_test_num++;
    $result = is_string($obj);
    if (!$result)
      {
	$arg_type = gettype($obj);
	self::report("did not pass is_object <$arg_type:$obj>");
      }
  }

  public static function is_true($obj)
  {
    self::$_test_num++;
    if (!$obj)
      {
	$arg_type = gettype($obj);
	self::report("did not pass is_true <$arg_type:$obj>");
      }
  }

  public static function __callStatic($func, $arg)
  {
    self::$_test_num++;
    if (is_array($arg))
      {
	$arg = $arg[0];
      }

    if (!function_exists($func))
      {
	self::report("no such function $func.");	
      }
    else
      {
	$result = $func($arg);
	if (!$result)
	  {
	    $arg_type = gettype($arg);
	    self::report("did not pass $func <$arg_type:$arg>");
	  }
      }
  }
}


if (!function_exists("memory_get_usage"))
  {
    function memory_get_usage() { return 0; }
  }


?>
