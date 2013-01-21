<?php
/*
    Copyright 2013 Weydson Lima
    
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// http://pear.php.net/package/Config needs to be installed and in the include path
require_once 'Config.php';
$conf = new Config();

/**
 * Return all virtual host config files from Apache
 * For that, we grab the output from httpd -ST 
 */
function get_vhosts_config_files(){
	$handle = popen('httpd -ST 2>&1', 'r');
	$content = '';
	while($read = fread($handle, 4096)){
		$content .= $read;
	}
	pclose($handle);

	$pattern = '/^.*\((.*)\)$/';
	
	foreach(preg_split("/((\r?\n)|(\r\n?))/", $content) as $line){
		preg_match($pattern, $line, $matches);
		if(is_array($matches) && count($matches) > 1){
			$cnf = explode(':', $matches[1]);
			$confs[] = $cnf[0];
		}
	}
	
	$confs = array_unique($confs);
	
	return $confs;
}

$config_files = get_vhosts_config_files();
foreach($config_files as $file){
	// Parse vhost config file
	$root = $conf->parseConfig($file, 'apache');
	if (PEAR::isError($root)) {
		echo 'Error reading config: ' . $root->getMessage() . "\n";
		exit(1);
	}
	
	$vhosts = array();
	$i = 0;
	
	// Loop through each VirtualHost section
	while ($vhost = $root->getItem('section', 'VirtualHost', null, null, $i++)) {
		// Virtual Host attributes
		$lst = $vhost->getAttributes();
		
		// DocumentRoot for that virtual host
		$droot = $vhost->getItem('directive', 'DocumentRoot')->content;
		
		$project = explode('/', $droot);
		
		if($lst && $droot){
			$host = explode(':', $lst[0]);
			if(is_array($host) && count($host) > 1){
				list($ip, $port) = $host;
				if($ip == '*'){
					$ip = 'localhost';
				}
				$vhosts[] = array(
					'ip' => $ip,
					'port' => $port,
					'root' => $droot,
					'project' => $project[count($project)-1]
				);
			}
		}
	}
}
?>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<?php foreach($vhosts as $vhost):?>
	<div class='vhost'>
		<?php $address = $vhost['ip'].':'.$vhost['port'];?>
		<div class='name'><?php echo $vhost['project'];?></div>
		<div class='address'>
			<a href='http://<?php echo $address;?>'><?php echo $address;?></a>
		</div>
		<div class='folder'><?php echo $vhost['root'];?></div>
	</div>
	<div class='clear'></div>
<?php endforeach;?>
</body>
</html>