#!/usr/bin/env php
<?php
/*
 * ./index.php -b 200
 *
 * -b - размер пакета обработки данных. По умолчанию - 100 строк
 */
error_reporting(E_ALL);
// Все ошибки теперь фатальны
set_error_handler(function ($code, $message, $file, $line, $args) {
    fwrite(STDERR, "ERROR: $message at $file:$line\nBacktrace: ");
    fwrite(STDERR, print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 1));
    throw new \ErrorException($message, $code);
});

$opts = getopt($optstr = 'b:');
if (empty($opts['b'])) {
    fwrite(STDERR, '  Usage: php ' . $_SERVER['PHP_SELF'] . " -b batch_size\n");
    exit(1);
}

$db = new \PDO(
    "mysql:dbname=fst;host=localhost",
    't99342',
    't99342',
    [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'",
        PDO::MYSQL_ATTR_COMPRESS     => true,
    ]
);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
if (empty($opts['b'])) {
    $opts['b'] = 100;
}
if (!is_numeric($opts['b'])) {
    throw new \RuntimeExecption("Batch size should be a numeric value");
}
$batchSize = $opts['b'];


$domainStats = [];
$currentPk   = 0;
$st = $db->prepare("SELECT email FROM users WHERE id BETWEEN ? AND ?");
$dataPresent = true;
while($dataPresent) {
    $res = $st->execute([$currentPk, $currentPk + $batchSize]);
    if (!$res) {
        throw new \RuntimeException("Statement execute failed");
    }
    $dataPresent = false;
    while($emails = $st->fetchColumn()) {
        $dataPresent = true;
        foreach (preg_split('/,\s*/', $emails, -1, PREG_SPLIT_NO_EMPTY) as $email) {
            preg_match('/@(.+)$/', $email, $m);
            $domain = $m[1];
            empty($domainStats[$domain]) ? ($domainStats[$domain] = 1) : ++$domainStats[$domain];
        }
    }

    $currentPk += $batchSize;
}

foreach ($domainStats as $domain => $count) {
    echo str_pad($domain, 30) . " " . str_pad($count, 5, ' ', STR_PAD_LEFT) . "\n";
}
