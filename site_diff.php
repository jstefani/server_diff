#!/usr/bin/php -qc /dev/null
<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);

$version = ".8";
$verbose = false;
$compare = false;
$ip = false;
$out_dir = getcwd();
$out_dir .= "/";

process_command_line();

if ($input_file == "") {
  show_help();
  exit();
 }

# die if required files do not exist
file_exists($input_file) || die("$input_file does not exist\n");
file_exists($out_dir) || die("$out_dir does not exist\n");

if ($verbose) { "Outputing to $out_dir\n"; }
$domains = file($input_file);
$domain_id = 0;

if ($compare) {
  $diff_dir = $out_dir . "/diffs/";
  @mkdir($diff_dir, 0700);
 }

foreach($domains as $domain_name) {

  $domain_name = rtrim($domain_name, "\n");
  if ($domain_name == "") { continue; }

  $domain_id++;

  if ($ip && (gethostbyname($domain_name)) != $ip) {
      if ($verbose) { echo "$domain_name not hosted on $ip. Skipping...\n"; }
      continue;
  }

  $contents = get_domain_contents($domain_name, $domain_id);

  $cached_contents = "";
  if ($compare) {
    $cached_contents = read_domain_copy($domain_id, $out_dir);
    if (($cached_contents != "") && ($cached_contents != $contents)) {
      $diff_string = "compare with : diff " . $diff_dir . $domain_id . ".html " . $out_dir . $domain_id . ".html";
      echo "$domain_name ($domain_id) is different - $diff_string\n";
      save_domain_copy($domain_id, $contents, $diff_dir);
    }
  }
  else {
    if ($contents) { save_domain_copy($domain_id, $contents, $out_dir); }
  }

}

// if we're in compare mode, we are done. no need to check again for dynamic content
if ($compare) {
  exit();
}


// go through again and remove any that have already changed (ie. have dynmaic content)
if ($verbose) { echo "Checking for dynamic content...\n"; }
$domain_id = 0;
foreach($domains as $domain_name) {

  $domain_name = rtrim($domain_name, "\n");
  if ($domain_name == "") { continue; }

  $domain_id++;

  if ($ip && (gethostbyname($domain_name)) != $ip) {
      if ($verbose) { echo "$domain_name not hosted on $ip. Skipping...\n"; }
      continue;
  }

  $contents = get_domain_contents($domain_name, $domain_id);

  $cached_contents = read_domain_copy($domain_id, $out_dir);
    if ($cached_contents != $contents) {
      remove_cached_copy($domain_id, $out_dir);
      echo "Warning: Removing changed domain $domain_name ($domain_id)\n";
    }

}  

exit();





function get_domain_contents($domain_name, $domain_id)
{

  global $verbose;

  $domain_name = create_url($domain_name);
  if ($verbose) { echo "$domain_id: Getting $domain_name ...\n"; }

  @set_error_handler(@create_function('$severity, $message, $file, $line','throw new ErrorException($message, $severity, $severity, $file, $line);'));

  $contents = "error";
  try {
    $contents = @file_get_contents($domain_name);
  }
  catch (Exception $e) {
    if ($verbose) { echo "Error on $domain_name ($domain_id) : " . $e->getMessage() . "\n"; }
  }

  restore_error_handler();
  return $contents;

}

function read_domain_copy($domain_id, $location)
{
  $file = $location . $domain_id . ".html";
  if (!file_exists($file)) { return false; }
  return file_get_contents($file);
}


function remove_cached_copy($domain_id, $out_dir)
{
    $f = $out_dir . $domain_id . ".html";
    unlink($f);
}


function save_domain_copy($domain_id, $contents, $out_dir)
{
    $f = $out_dir . $domain_id . ".html";

    if (!$handle = fopen($f, "w")) {
      echo("Could not open $f for writing\n");
      return false;
    }
  
    fwrite($handle, $contents);
    fclose($handle);
}


function show_help()
{

  global $version;

echo <<< END_OF_TEXT
site_diff version $version
Usage: site_diff [OPTION]... [URL]...

Logging and input file:
  -o,  --output-dir=DIR     output results to directory DIR
  -i,  --input-file=FILE    read list of domains/urls from FILE
  -h,  --help               display usage information
  -v,  --verbose            display more verbose output
  -r,  --ip=IP_ADDRESS      ignore URLS's that do not resolve to IP_ADDRESS
  -c,  --compare            scan and make comparision to previous output in --output-dir DIR

END_OF_TEXT;

  return true;
}


function process_command_line()
{

  global $compare, $verbose, $input_file, $out_dir, $ip;

  $shortopts  = "";
  $shortopts .= "o:"; // Optional value
  $shortopts .= "i:"; // Optional value
  $shortopts .= "h"; // Optional value
  $shortopts .= "v"; // Optional value
  $shortopts .= "c"; // Optional value
  $shortopts .= "r:"; // Optional value

  $longopts  = array(
	  	   "output-dir:",    
		   "input-file:",    
		   "help",
		   "verbose",
		   "compare",
		   "ip:"
		   );

  $options = getopt($shortopts, $longopts);

  if (isset($options['verbose']) || isset($options['v'])) {
    $verbose = true;
  }

  foreach (array_keys($options) as $opt) switch ($opt) {
 
    case 'o':
    case 'output-dir' :
      $out_dir = $options[$opt] . "/";
      if ($verbose) { echo "Output directory : $out_dir\n"; }
      break;

    case 'i':
    case 'input-file':
      $input_file = $options[$opt];
      if ($verbose) { echo "Input file is : $input_file\n"; }
      break;

    case 'v' :
    case 'verbose' :
      $verbose = true;
      break;

    case 'c' :
    case 'compare' :
      $compare = true;
      if ($verbose) { echo "Running in compare mode...\n"; }
      break;

    case 'r' :
    case 'ip' :
      $ip = $options[$opt];
      if ($verbose) { echo "Scanning restricted to $ip ...\n"; }
      break;

    case 'h' :
    case 'help' :
      show_help();
      exit();
      break;
  }

  return;

}


function create_url($s)
{

  if ((substr($s, 0, 7) == "http://") ||
      (substr($s, 0, 8) == "https://"))
    {
      return $s;
    }

  return "http://" . $s . "/";

}


?>
