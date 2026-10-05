<?php
//validamos datos del servidor
$user = "root";
$pass = "";
$host = "localhost";
//hacemos llamado al imput de formuario
$datab = "cafeteria";
//conetamos al base datos
$connection = new mysqli($host, $user, $pass,$datab);
//indicamos selecionar a la base datos
?>