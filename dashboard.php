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
<a href="#">💪 Classes</a>
<a href="#">📅 Reserves</a>
</div>

<div class="main">

<div class="topbar">
<h3>Dashboard</h3>
<a href="logout.php" class="logout">Tancar sessió</a>
</div>

<h2>Activitats dirigides</h2>

<table>
<tr>
<th>Activitat</th>
<th>Durada</th>
<th>Accions</th>
</tr>

<tr><td>BodyPump</td><td>1h</td><td>
<button class="btn join" onclick="apuntar()">Apuntar-se</button>
<span class="admin-only">
<button class="btn add" onclick="afegir()">➕</button>
<button class="btn edit" onclick="editar()">✏️</button>
<button class="btn delete" onclick="eliminar()">🗑️</button>
</span>
</td></tr>

<tr><td>Core</td><td>30 min</td><td>
<button class="btn join" onclick="apuntar()">Apuntar-se</button>
<span class="admin-only">
<button class="btn add">➕</button>
<button class="btn edit">✏️</button>
<button class="btn delete">🗑️</button>
</span>
</td></tr>

<tr><td>BodyBalance</td><td>1h</td><td>
<button class="btn join" onclick="apuntar()">Apuntar-se</button>
<span class="admin-only"><button class="btn add">➕</button><button class="btn edit">✏️</button><button class="btn delete">🗑️</button></span>
</td></tr>

<tr><td>Pilates</td><td>1h</td><td>
<button class="btn join" onclick="apuntar()">Apuntar-se</button>
<span class="admin-only"><button class="btn add">➕</button><button class="btn edit">✏️</button><button class="btn delete">🗑️</button></span>
</td></tr>

<tr><td>Yoga</td><td>1h</td><td>
<button class="btn join" onclick="apuntar()">Apuntar-se</button>
<span class="admin-only"><button class="btn add">➕</button><button class="btn edit">✏️</button><button class="btn delete">🗑️</button></span>
</td></tr>

<tr><td>BodyCombat</td><td>1h</td><td>
<button class="btn join" onclick="apuntar()">Apuntar-se</button>
<span class="admin-only"><button class="btn add">➕</button><button class="btn edit">✏️</button><button class="btn delete">🗑️</button></span>
</td></tr>

<tr><td>Boxeo</td><td>1h</td><td>
<button class="btn join" onclick="apuntar()">Apuntar-se</button>
<span class="admin-only"><button class="btn add">➕</button><button class="btn edit">✏️</button><button class="btn delete">🗑️</button></span>
</td></tr>

<tr><td>HBX Boxing</td><td>1h</td><td>
<button class="btn join" onclick="apuntar()">Apuntar-se</button>
<span class="admin-only"><button class="btn add">➕</button><button class="btn edit">✏️</button><button class="btn delete">🗑️</button></span>
</td></tr>

<tr><td>Ciclo indoor</td><td>45 min</td><td>
<button class="btn join" onclick="apuntar()">Apuntar-se</button>
<span class="admin-only"><button class="btn add">➕</button><button class="btn edit">✏️</button><button class="btn delete">🗑️</button></span>
</td></tr>

<tr><td>Ciclo Virtual</td><td>45 min</td><td>
<button class="btn join" onclick="apuntar()">Apuntar-se</button>
<span class="admin-only"><button class="btn add">➕</button><button class="btn edit">✏️</button><button class="btn delete">🗑️</button></span>
</td></tr>

<tr><td>BodyPump Virtual</td><td>1h</td><td>
<button class="btn join" onclick="apuntar()">Apuntar-se</button>
<span class="admin-only"><button class="btn add">➕</button><button class="btn edit">✏️</button><button class="btn delete">🗑️</button></span>
</td></tr>

<tr><td>Core Virtual</td><td>30 min</td><td>
<button class="btn join" onclick="apuntar()">Apuntar-se</button>
<span class="admin-only"><button class="btn add">➕</button><button class="btn edit">✏️</button><button class="btn delete">🗑️</button></span>
</td></tr>

<tr><td>Aquagym</td><td>45 min</td><td>
<button class="btn join" onclick="apuntar()">Apuntar-se</button>
<span class="admin-only"><button class="btn add">➕</button><button class="btn edit">✏️</button><button class="btn delete">🗑️</button></span>
</td></tr>

<tr><td>Zumba</td><td>1h</td><td>
<button class="btn join" onclick="apuntar()">Apuntar-se</button>
<span class="admin-only"><button class="btn add">➕</button><button class="btn edit">✏️</button><button class="btn delete">🗑️</button></span>
</td></tr>

<tr><td>Step</td><td>45 min</td><td>
<button class="btn join" onclick="apuntar()">Apuntar-se</button>
<span class="admin-only"><button class="btn add">➕</button><button class="btn edit">✏️</button><button class="btn delete">🗑️</button></span>
</td></tr>

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
