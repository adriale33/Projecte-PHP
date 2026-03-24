<?php
require_once 'auth.php';
requireLogin();
?>


<!DOCTYPE html>
<html lang="ca">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - Projecte Gimnàs</title>

<style>

body{
    margin:0;
    font-family:Arial, sans-serif;
    display:flex;
}

.sidebar{
    width:220px;
    background:#111;
    color:white;
    height:100vh;
    padding:20px;
}

.sidebar a{
    display:block;
    color:white;
    text-decoration:none;
    margin:15px 0;
    padding:10px;
    border-radius:5px;
}

.sidebar a:hover{
    background:#00ff88;
    color:#111;
}

.main{
    flex:1;
    background:#f4f4f4;
    padding:20px;
}

.topbar{
    background:white;
    padding:15px;
    border-radius:10px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
}

.logout{
    background:#ff4d4d;
    color:white;
    padding:8px 15px;
    border-radius:5px;
    text-decoration:none;
}

table{
    width:100%;
    border-collapse:collapse;
    background:white;
    border-radius:10px;
    overflow:hidden;
}

table th, table td{
    padding:12px;
    border-bottom:1px solid #ddd;
}

table th{
    background:#111;
    color:white;
}

.btn{
    padding:5px 8px;
    border:none;
    border-radius:5px;
    cursor:pointer;
}

.join{ background:#007bff; color:white; }
.add{ background:#00ff88; }
.edit{ background:#ffc107; }
.delete{ background:#ff4d4d; color:white; }

.admin-only{
    display:none;
}

</style>
</head>

<body>

<div class="sidebar">
<h2>Projecte Gimnàs</h2>
<a href="#clases">💪 Classes</a>
<a href="#clases">📅 Reserves</a>
</div>

<div class="main">

<div class="topbar">
<h3>Dashboard</h3>
<a href="logout.php" class="logout">Tancar sessió</a>
</div>

<h2 id="clases">Activitats dirigides</h2>

<table>
<tr>
<th>Activitat</th>
<th>Durada</th>
<th>Reserva</th>

<?php if($_SESSION['rol'] == 'admin'): ?>
<th>Edició</th>
<?php endif; ?>

</tr>

<!-- FILA 1 -->
<tr>
<td>BodyPump</td>
<td>1h</td>

<td>
<button class="btn join" onclick="apuntar()">Apuntar-se</button>
</td>

<?php if($_SESSION['rol'] == 'admin'): ?>
<td>
<button class="btn add">➕</button>
<button class="btn edit">✏️</button>
<button class="btn delete">🗑️</button>
</td>
<?php endif; ?>
</tr>

<!-- FILA 2 -->
<tr>
<td>Core</td>
<td>30min</td>

<td>
<button class="btn join" onclick="apuntar()">Apuntar-se</button>
</td>

<?php if($_SESSION['rol'] == 'admin'): ?>
<td>
<button class="btn add">➕</button>
<button class="btn edit">✏️</button>
<button class="btn delete">🗑️</button>
</td>
<?php endif; ?>
</tr>

<!-- FILA 3 -->
<tr>
<td>BodyBalance</td>
<td>1h</td>

<td>
<button class="btn join" onclick="apuntar()">Apuntar-se</button>
</td>

<?php if($_SESSION['rol'] == 'admin'): ?>
<td>
<button class="btn add">➕</button>
<button class="btn edit">✏️</button>
<button class="btn delete">🗑️</button>
</td>
<?php endif; ?>
</tr>

<!-- FILA 4 -->
<tr>
<td>Pilates</td>
<td>1h</td>

<td>
<button class="btn join" onclick="apuntar()">Apuntar-se</button>
</td>

<?php if($_SESSION['rol'] == 'admin'): ?>
<td>
<button class="btn add">➕</button>
<button class="btn edit">✏️</button>
<button class="btn delete">🗑️</button>
</td>
<?php endif; ?>
</tr>

<!-- FILA 5 -->
<tr>
<td>Yoga</td>
<td>1h</td>

<td>
<button class="btn join" onclick="apuntar()">Apuntar-se</button>
</td>

<?php if($_SESSION['rol'] == 'admin'): ?>
<td>
<button class="btn add">➕</button>
<button class="btn edit">✏️</button>
<button class="btn delete">🗑️</button>
</td>
<?php endif; ?>

</tr>

<!-- FILA 6 -->
<tr>
<td>BodyCombat</td>
<td>1h</td>

<td>
<button class="btn join" onclick="apuntar()">Apuntar-se</button>
</td>

<?php if($_SESSION['rol'] == 'admin'): ?>
<td>
<button class="btn add">➕</button>
<button class="btn edit">✏️</button>
<button class="btn delete">🗑️</button>
</td>
<?php endif; ?>
</tr>

<!-- FILA 7 -->
<tr>
<td>Boxa</td>
<td>1h</td>

<td>
<button class="btn join" onclick="apuntar()">Apuntar-se</button>
</td>

<?php if($_SESSION['rol'] == 'admin'): ?>
<td>
<button class="btn add">➕</button>
<button class="btn edit">✏️</button>
<button class="btn delete">🗑️</button>
</td>
<?php endif; ?>
</tr>

<!-- FILA 8 -->
<tr>
<td>HBX Boxing</td>
<td>1h</td>

<td>
<button class="btn join" onclick="apuntar()">Apuntar-se</button>
</td>

<?php if($_SESSION['rol'] == 'admin'): ?>
<td>
<button class="btn add">➕</button>
<button class="btn edit">✏️</button>
<button class="btn delete">🗑️</button>
</td>
<?php endif; ?>
</tr>

<!-- FILA 9 -->
<tr>
<td>Ciclo indoor</td>
<td>45min</td>

<td>
<button class="btn join" onclick="apuntar()">Apuntar-se</button>
</td>

<?php if($_SESSION['rol'] == 'admin'): ?>
<td>
<button class="btn add">➕</button>
<button class="btn edit">✏️</button>
<button class="btn delete">🗑️</button>
</td>
<?php endif; ?>
</tr>

<!-- FILA 10 -->
<tr>
<td>Ciclo Virtual</td>
<td>45min</td>

<td>
<button class="btn join" onclick="apuntar()">Apuntar-se</button>
</td>

<?php if($_SESSION['rol'] == 'admin'): ?>
<td>
<button class="btn add">➕</button>
<button class="btn edit">✏️</button>
<button class="btn delete">🗑️</button>
</td>
<?php endif; ?>
</tr>

<!-- FILA 11 -->
<tr>
<td>BodyPump Virtual</td>
<td>1h</td>

<td>
<button class="btn join" onclick="apuntar()">Apuntar-se</button>
</td>

<?php if($_SESSION['rol'] == 'admin'): ?>
<td>
<button class="btn add">➕</button>
<button class="btn edit">✏️</button>
<button class="btn delete">🗑️</button>
</td>
<?php endif; ?>
</tr>

<!-- FILA 12 -->
<tr>
<td>Core Virtual</td>
<td>30min</td>

<td>
<button class="btn join" onclick="apuntar()">Apuntar-se</button>
</td>

<?php if($_SESSION['rol'] == 'admin'): ?>
<td>
<button class="btn add">➕</button>
<button class="btn edit">✏️</button>
<button class="btn delete">🗑️</button>
</td>
<?php endif; ?>
</tr>

<!-- FILA 13 -->
<tr>
<td>Aguagym</td>
<td>45min</td>

<td>
<button class="btn join" onclick="apuntar()">Apuntar-se</button>
</td>

<?php if($_SESSION['rol'] == 'admin'): ?>
<td>
<button class="btn add">➕</button>
<button class="btn edit">✏️</button>
<button class="btn delete">🗑️</button>
</td>
<?php endif; ?>
</tr>

<!-- FILA 14 -->
<tr>
<td>Zumba</td>
<td>1h</td>

<td>
<button class="btn join" onclick="apuntar()">Apuntar-se</button>
</td>

<?php if($_SESSION['rol'] == 'admin'): ?>
<td>
<button class="btn add">➕</button>
<button class="btn edit">✏️</button>
<button class="btn delete">🗑️</button>
</td>
<?php endif; ?>
</tr>

<!-- FILA 15 -->
<tr>
<td>Step</td>
<td>45min</td>

<td>
<button class="btn join" onclick="apuntar()">Apuntar-se</button>
</td>

<?php if($_SESSION['rol'] == 'admin'): ?>
<td>
<button class="btn add">➕</button>
<button class="btn edit">✏️</button>
<button class="btn delete">🗑️</button>
</td>
<?php endif; ?>
</tr>

</table>


</div>

<script>

let rol = "admin"; // canvia a "usuari" o "entrenador"

if(rol === "admin"){
    document.querySelectorAll(".admin-only").forEach(el => {
        el.style.display = "inline";
    });
}

function apuntar(){
    alert("T'has apuntat a la classe!");
}

function afegir(){ alert("Afegir activitat"); }
function editar(){ alert("Editar activitat"); }
function eliminar(){
    if(confirm("Eliminar activitat?")){
        alert("Eliminada");
    }
}

</script>

</body>
</html>
