<?php

require_once 'config.php';

session_start();

if($_SERVER['REQUEST_METHOD']=="POST"){

$nom_usuari=$_POST['nom_usuari'];
$contrasenya=$_POST['contrasenya'];

$stmt=$pdo->prepare("SELECT * FROM usuaris WHERE nom_usuari=?");
$stmt->execute([$nom_usuari]);

$usuari=$stmt->fetch();

if($usuari && password_verify($contrasenya,$usuari['contrasenya'])){

session_regenerate_id(true);

$_SESSION['usuari_id']=$usuari['id'];
$_SESSION['nom_usuari']=$usuari['nom_usuari'];
$_SESSION['rol']=$usuari['rol'];

header("Location: dashboard.php");
exit;

}else{

$error="Usuari o contrasenya incorrectes";

}

}

?>

<!DOCTYPE html>
<html lang="ca">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Projecte Gimnàs</title>

<style>

body{
    margin:0;
    font-family: Arial, sans-serif;
    background:#111;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}

/* Caixa login */
.login-container{
    background:white;
    padding:40px;
    border-radius:10px;
    width:320px;
    box-shadow:0 0 15px rgba(0,0,0,0.3);
    text-align:center;
}

.login-container h2{
    margin-bottom:20px;
}

/* Inputs */
input{
    width:100%;
    padding:12px;
    margin:10px 0;
    border-radius:5px;
    border:1px solid #ccc;
}

/* Botó */
button{
    width:100%;
    padding:12px;
    background:#00ff88;
    border:none;
    border-radius:5px;
    font-weight:bold;
    cursor:pointer;
}

button:hover{
    background:#00cc6a;
}

/* Enllaç */
a{
    display:block;
    margin-top:15px;
    text-decoration:none;
    color:#333;
}

a:hover{
    color:#00ff88;
}

.logo{
    margin-bottom:20px;
    font-size:22px;
    font-weight:bold;
}

</style>
</head>

<body>

<div class="login-container">

<div class="logo">Projecte Gimnàs</div>

<h2>Inicia sessió</h2>

<form method="POST" action="login.php">
<input type="text" name="nom_usuari" placeholder="Nom d'usuari" required>
<input type="password" name="contrasenya" placeholder="Contrasenya" required>

<button type="submit">Entrar</button>
</form>

<a href="index.html">← Tornar a la pàgina principal</a>

</div>

</body>
</html>
