<?php

$redirect = './?' . http_build_query(array('pi' => 'ViewBrowserPlugin') + $_GET);
ob_end_clean();
header('Location: ' . $redirect);
exit();
