<?php
session_name('CVBETA'); session_start();
session_destroy();
header('Location: /beta/index.php');
exit;
