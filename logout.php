<?php
session_start();
session_destroy();
header("Location: formulaire.html");
exit();
?>