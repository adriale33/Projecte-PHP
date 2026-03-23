<?php

session_start();

function estaAutenticat(){
    return isset($_SESSION['usuari_id']);
}

function requireLogin(){

    if(!estaAutenticat()){
        header("Location: login.php");
        exit;
    }

}

function teRol($rol){

    return isset($_SESSION['rol']) && $_SESSION['rol']==$rol;

}

function requireAdmin(){

    requireLogin();

    if(!teRol('admin')){
        die("Accés denegat");
    }

}

?>
