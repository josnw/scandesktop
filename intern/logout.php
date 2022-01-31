<?php

$_SESSION['typ'] = 'logout';
$_SESSION['level'] = '0';
$_SESSION['user'] = null;
$_SESSION['name'] = '<B>Zum Bearbeiten bitte mit Factodaten einloggen!</B>';
$_SESSION['uid'] = 0;
setcookie("scandesk", base64_encode(serialize($_SESSION)), time()-1);
session_destroy();


?>