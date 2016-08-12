#!/usr/bin/php -qc /dev/null
<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);

$version = ".5";
//$input_file = STDIN;
$verbose = false;
$compare = false;
$ip = false;

process_command_line();

if ($input_file == "") {
  show_help();
  exit();
 }

$domains = file($input_file);
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

  $cached_contents = "";
  if ($compare) {
    $cached_contents = read_domain_copy($domain_id, $out_dir);
    if (($cached_contents != "") && ($cached_contents != $contents)) {
      echo "$domain_name ($domain_id) is different\n";
    }
  }
  else {
    if ($contents) { save_domain_copy($domain_id, $contents, $out_dir); }
  }

}  


  if ($compare) {
    exit();
  }




// go through again and remove any that have already changed (ie. have dynmaic content)

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
      if ($verbose) { echo "Removing changed domain $domain_name ($domain_id)\n"; }
    }


}  

exit();





function get_domain_contents($domain_name, $domain_id)
{

  global $verbose;

  $domain_name = create_url($domain_name);
  if ($verbose) { echo "Getting $domain_name ($domain_id) ...\n"; }

  set_error_handler(create_function('$severity, $message, $file, $line','throw new ErrorException($message, $severity, $severity, $file, $line);'));

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
      continue;
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
  -o,  --output-dir=DIR     output resuls to directory DIR
  -i,  --input-file=FILE    read list of domains from FILE
  -h,  --help               display usage information
  -v,  --verbose            display more verbose output
  -r,  --ip=IP_ADDRESS      restrict scanning to the IP address IP_ADDRESS
  -c,  --compare            scan and make comparision to saved output in --output-dir DIR

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
		   "output-dir:",     // Required value
		   "input-file:",    // Optional value
		   "help",
		   "verbose",
		   "compare",
		   "ip:"
		   );

$options = getopt($shortopts, $longopts);



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

if (isset($options['verbose']) || isset($options['v'])) {
  $verbose = true;
 }

if (isset($options['input-file'])) {
      $input_file = $options['input-file'];
      if ($verbose) { echo "Input file is : $input_file\n"; }
 }

if (isset($options['output-dir'])) {
  $out_dir = $options['output-dir'] . "/";
  if ($verbose) { echo "Output directory : $out_dir\n"; }
 }
 else {
   $out_dir = "";
 }

if (isset($options['help']) || isset($options['h'])) {
  show_help();
  exit();
 }

if (isset($options['compare']) || isset($options['c'])) {
  $compare = true;
  if ($verbose) { echo "Running in compare mode...\n"; }
 }

if (isset($options['ip']) || isset($options['r'])) {
  if (isset($options['ip'])) { $ip = $options['ip']; }
  if (isset($options['r'])) { $ip = $options['r']; }
  if ($verbose) { echo "Scanning restricted to $ip ...\n"; }
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
