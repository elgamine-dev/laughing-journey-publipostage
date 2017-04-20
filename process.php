<?php
require_once __DIR__ . '/vendor/autoload.php';

$csvPath = __DIR__ . '/data/ds.csv';
$mailPath = __DIR__ . '/data/mail.txt';

$output = __DIR__ . '/output';

$config = require 'config.php';
$dry = false;



$csv = file_get_contents($csvPath);
$mail = file_get_contents($mailPath);

$sheet = explode("\n", $csv);
$headers = str_getcsv(array_shift($sheet));

$sheet = array_map(function($line) use ($headers){
    return  array_combine($headers, str_getcsv($line));
}, $sheet);

function send($mail, $config) {
    $transport = Swift_SmtpTransport::newInstance('smtp.gmail.com', 465, "ssl")
    ->setUsername($config['gmail']['username'])
    ->setPassword($config['gmail']['password']);

    $mailer = Swift_Mailer::newInstance($transport);

    $message = Swift_Message::newInstance($config['subject'])
    ->setFrom(array($config['sender']['email'] => $config['sender']['name']))
    ->setTo(array($mail['to']))
    ->setBody($mail['body']);

    $result = $mailer->send($message);
}



function template($data, $text) {
   
    // si le genre est défini on peut lire une clé f dans le template pour accorder au féminin !
    if (isset($data['gender']) && $data['gender'] === 'f') {
        $data['f'] = true;
    }
    return (new Mustache_Engine)->render($text, $data);
}


if ($dry) {
    array_map('unlink', glob("$output/*.*"));
}

foreach($sheet as $k => $user) {
    $body = template($user, $mail);
    $to = $user['email'];
    if ($dry) {
        file_put_contents($output . "/{$k}-{$user['prenom']}.txt", $body);
    } else {
        send(["to"=>$to, "body"=>$body], $config);
    }
}
echo 'done';

