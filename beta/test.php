<?php
session_name('CVBETA');
ini_set('session.cookie_path', '/beta/');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_httponly', '1');
session_start();
if (!isset($_SESSION['user'])) { die('NOT LOGGED IN'); }
$username = htmlspecialchars($_SESSION['user']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<title>Beta Test</title>
<?php include 'theme.php'; ?>
<link rel="stylesheet" href="shared.css?v=test001">
</head>
<body>
<p style="color:white;font-family:monospace;padding:20px">Step 1: PHP OK, user=<?= $username ?></p>
<?php include 'nav.php'; ?>
<p style="color:white;font-family:monospace;padding:20px">Step 2: nav.php included OK</p>
<script>
document.write('<p style="color:lime;font-family:monospace;padding:20px">Step 3: JS running OK</p>');
</script>
<script>
<?php include 'categories.js.php'; ?>
document.write('<p style="color:lime;font-family:monospace;padding:20px">Step 4: categories.js.php OK</p>');
</script>
</body>
</html>
