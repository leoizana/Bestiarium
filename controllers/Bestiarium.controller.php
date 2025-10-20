<?php
if($path == "/bestiarium/viewall") {
   $resultat = $connexion->query("SELECT * FROM bestiarium");
$bestiaires = $resultat->fetchAll(PDO::FETCH_ASSOC);
foreach($bestiaires as $bestiaire){
    $bete = new Bestiarium($bestiaire['name'], $bestiaire['hp'], $bestiaire['damage']);
    $bete->setDescription($bestiaire['description']);
    echo $bete . "<br>";
}
}

if($path == "/bestiarium/view") {
///////////////////// Récupération d'une bête via son ID
$resulat = $connexion->query("SELECT * FROM bestiarium WHERE id=".$_GET['id']);
$bestiaire = $resulat->fetch(PDO::FETCH_ASSOC);
$bete = new Bestiarium($bestiaire['name'], $bestiaire['hp'], $bestiaire['damage']);
$bete->setDescription($bestiaire['description']);
echo $bete;

}
////////// Création d'une bête via : http://localhost:8000/bestarium/create?Name=Miaous&Hp=222&Damage=70&Description=LeGrandM%C3%A9chantLoup
if($path == "/bestiarium/create") {
$connexion->query("INSERT INTO bestiarium (name, hp, damage, description) VALUES ('".$_GET['Name']."', '".$_GET['Hp']."', '".$_GET['Damage']."', '".$_GET['Description']."')");

}