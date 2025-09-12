<?php
    $user = "aula";
    $password = "aula";
    $ip = "10.90.24.54";
    $database = "aula";
    $connection = new PDA("pgsql:host=$ip;dbname=$database",$user,$password);
?>
