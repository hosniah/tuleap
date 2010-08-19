<?php

// Find the first branch revision
$log = simplexml_load_string(shell_exec('svn log --xml --stop-on-copy'));
$lastEntry = count($log->logentry) - 1;
$firstRevision = (int) $log->logentry[$lastEntry]['revision'];

// Find all added .php files
$diff = simplexml_load_string(shell_exec('svn diff --xml --summarize -r '.$firstRevision.':HEAD'));
foreach ($diff->xpath('paths/path') as $path) {
    if ($path['item'] == 'added') {
        $p = (string) $path;
        if (preg_match('%.php$%', $p) && strpos($p, '/tests/') === false && is_file($p)) {
            if (strpos($p, 'plugins/git/gitphp-0.1.0') !== false) {
                continue;
            }
            if (strpos($p, 'plugins/webdav/include/lib') !== false) {
                continue;
            }
            echo $p.PHP_EOL;
        }
    }
}

?>