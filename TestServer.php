<html>
    <head>
        <title>Test this server</title>
    </head>
    <body>
        <style type="text/css">
            table { border: 1px solid #ccc; }
            table td { border: 1px solid #ccc; width:33%;}
        </style>
        <h1>Rudimentary Server Test</h1>
        <table>
            <thead>
                <tr>
                    <td><h3>Server Item</h3></td>
                    <td><h3>Test Result</h3></td>
                    <td><h3>Info(if any)</h3></td>
                </tr>
            </thead>
            <tr>
                <td>PHP 5.3 or higher?</td>
                <td><?= ((float)PHP_VERSION >= 5.3)? "true" : "false" ?></td>
                <td>PHP_VERSION is <?= PHP_VERSION ?></td>
            </tr>
            <tr>
                <td>PDO ODBC enabled?</td>
                <?php
                    $drivers = PDO::getAvailableDrivers();
                ?>
                <td><?= (in_array('odbc',$drivers))? 'true' : 'false'?></td>
                <td></td>
            </tr>
            <tr>
                <td>PDO SQLSRV enabled?</td>
                <td><?= (in_array('sqlsrv',$drivers))? 'true' : 'false' ?></td>
                <td></td>
            </tr>
            <tr>
                <td>igbinary enabled?</td>
                <td><?= (extension_loaded('igbinary')) ? 'true' : 'false' ?></td>
                <td></td>                
            </tr>
            <tr>
                <td>Redis enabled?</td>
                <td><?= (extension_loaded('redis')) ? 'true' : 'false' ?></td>
                <td></td>                
            </tr>
            <tr>
                <td>Redis Sessions Enabled?</td>
                <td><?= (ini_get('session.save_handler') === 'redis') ? 'true' : 'false' ?></td>
                <td>Current Session Handler: <?= ini_get('session.save_handler') ?></td>                
            </tr>
<?php
//Begin Windows and Linux Specific Checks
    if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $tmpFileName = tempnam(sys_get_temp_dir(),"checkReg");
        $scriptContents = <<<'ENDSCRIPT'
Const HKEY_LOCAL_MACHINE = &H80000002
 
strComputer = "."
 
Set objRegistry = GetObject("winmgmts:\\" & strComputer & "\root\default:StdRegProv")
 
strKeyPath = "SOFTWARE\ODBC\ODBCINST.INI\ODBC Drivers"
objRegistry.EnumValues HKEY_LOCAL_MACHINE, strKeyPath, arrValueNames, arrValueTypes
 
For i = 0 to UBound(arrValueNames)
    strValueName = arrValueNames(i)
    objRegistry.GetStringValue HKEY_LOCAL_MACHINE,strKeyPath,strValueName,strValue    
    Wscript.Echo arrValueNames(i) & " -- " & strValue
Next
ENDSCRIPT;
        $handle = fopen($tmpFileName,'w');
        fwrite($handle,$scriptContents);
        fclose($handle);
        rename($tmpFileName, $tmpFileName.='.vbs');
        $scriptOutput = shell_exec("cscript.exe //Nologo $tmpFileName");
        unlink($tmpFileName);
        
        ob_start();
        $matches = array();
        phpinfo(INFO_GENERAL);
        $phpOpts = ob_get_contents();
        ob_end_clean();
        preg_match('/Loaded Configuration File(.+)/',$phpOpts,$matches);
        $loadedIniPath = (!empty($matches)) ? trim(strip_tags($matches[1])) : "";
        if(preg_match('/^=/',$loadedIniPath)) {
            $loadedIniPath = substr($loadedIniPath, strpos($loadedIniPath, DIRECTORY_SEPARATOR),strlen($loadedIniPath));
        }
?>
            <tr>
                <td>igbinary enabled BEFORE redis?</td>
                <?php
                $loadedIniContents = (!empty($loadedIniPath)) ? file($loadedIniPath) : "";
                $extensionMatches = preg_grep('/^\\s*extension\\s*=/i', $loadedIniContents);
                if(!empty($extensionMatches) && is_array($extensionMatches)) {
                    reset($extensionMatches);
                    $firstMatch = key($extensionMatches);
                    if(!empty($firstMatch)) {
                        $extensionsToEndIni = array_slice($loadedIniContents,$firstMatch,(int)(count($loadedIniContents)-$firstMatch),true);
                        unset($loadedIniContents);
                        $redisPosition = preg_grep('/redis/i', $extensionsToEndIni);
                        $igbinaryPosition = preg_grep('/igbinary/i',$extensionsToEndIni);
                    }
                    else {
                        $redisPosition = 0;
                        $igbinaryPosition = 0;
                    }
                }
                else {
                    $redisPosition = 0;
                    $igbinaryPosition = 0;
                }
                ?>
                <td><?= (key($igbinaryPosition) < key($redisPosition)) ? 'true' : 'false' ?></td>
                <td>igbinary position: <?= key($igbinaryPosition) ?> || redis position: <?= key($redisPosition) ?></td>                
            </tr>
            <tr>
                <td>iSeries ODBC Driver installed?</td>
                <td><?= (stripos($scriptOutput,'iSeries Access ODBC Driver -- Installed') >= 0) ? 'true' : 'false' ?></td>
                <td><h3>All ODBC Drivers' Status:</h3> <?= $scriptOutput ?></td>                
            </tr>
            <tr>
                <td>MS SQL ODBC Driver installed?</td>
                <td><?= (stripos($scriptOutput,'Installed SQL Server Native Client 10.0 -- Installed') >= 0 || 
                        stripos($scriptOutput,'Installed SQL Server Native Client 11.0 -- Installed') >= 0 ) ? 'true' : 'false' ?></td>
                <td></td>                
            </tr>
            <tr>
                <td>Caching enabled?</td>
                <td><?= (extension_loaded('wincache') || extension_loaded('opcache')) ? 'true' : 'false' ?></td>
                <td>Cache Extension in use:
                    <?php
                        if(extension_loaded('wincache')) echo 'Wincache';
                        elseif(extension_loaded('opcache')) echo 'Zend Opcache';
                    ?>
                </td>                
            </tr>
<?php 
    } //end Windows checks
    else {
        $drivers = array();
        exec('odbcinst -q -d', $drivers);
?>
            <tr>
                <td>iSeries ODBC Driver installed?</td>
                <td><?= (in_array('[iSeries Access ODBC Driver]',$drivers)) ? 'true' : 'false' ?></td>
                <td><h3>All ODBC Drivers' Status:</h3> <?php print_r($drivers) ?></td>                
            </tr>
            <tr>
                <td>MS SQL ODBC Driver installed?</td>
                <td><?= (in_array('[SQL Server Native Client 10.0]',$drivers) || 
                        in_array('[SQL Server Native Client 11.0]',$drivers)) ? 'true' : 'false' ?></td>
                <td></td>                
            </tr>
            <tr>
                <td>Caching enabled?</td>
                <td><?= (extension_loaded('opcache') || extension_loaded('apc') || extension_loaded('xcache')) ? 'true' : 'false' ?></td>
                <td>Cache Extension in use:
                    <?php
                        if(extension_loaded('opcache')) echo 'Zend OpCache';
                        elseif(extension_loaded('apc')) echo 'APC';
                        elseif(extension_loaded('xcache')) echo 'XCache';
                    ?>
                </td>                
            </tr>
<?php
    }
?>
            <tr>
                <td>imagick enabled?</td>
                <td><?= (extension_loaded('imagick')) ? 'true' : 'false' ?></td>
                <td>Imagick Version info: <? (extension_loaded('imagick')) ? print_r(Imagick::getVersion()) : 'not loaded'?></td>                
            </tr>
            <tr>
                <td>magickwand enabled?</td>
                <td><?= (extension_loaded('magickwand')) ? 'true' : 'false' ?></td>
                <td><?= (extension_loaded('magickwand')) ? MagickGetVersionString() : 'not loaded' ?></td>                
            </tr>
            <tr>
                <td>memcache enabled?</td>
                <td><?= (extension_loaded('memcache')) ? 'true' : 'false' ?></td>
                <td></td>                
            </tr>
            <tr>
                <td>ldap enabled?</td>
                <td><?= (extension_loaded('ldap')) ? 'true' : 'false' ?></td>
                <td></td>                
            </tr>
            
        </table>
    </body>
</html>