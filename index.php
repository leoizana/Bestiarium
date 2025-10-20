<?php
spl_autoload_register(function ($classe){
    include "classes/" . $classe . ".class.php";
});
require("BD/connect.php");
$dsn="mysql:dbname=".BASE.";host=".SERVER.":3307";
    try{
      $connexion=new PDO($dsn,USER,PASSWD);
      // echo "Connexion établie\n";
    }
    catch(PDOException $e){
      printf("Échec de la connexion : %s\n", $e->getMessage());
      exit();
    }
    
//////////////////// Création d'une bête manuellement
// $b1 = new Bestiarium("Albator", 150, 25);
// $b1->setDescription($b1->getName(). " est un terrible dragon issu des Terres Arrides au Nord. Renommé pour ses écailles solides, personne n a jamais réussi à lui infliger la moindre égratignure.");
// echo $b1;
// $b1->setName("MichMich");
// $b1->setHp("1500");
// $connexion->query("INSERT INTO bestiarium (name, hp, damage, description) VALUES ('".$b1->getName()."', '".$b1->getHp()."', '".$b1->getDamage()."', '".$b1->getDescription()."')");

//////////////////// Création d'une bête via : http://localhost:8000/index.php?Name=Miaous&Hp=222&Damage=70&Description=LeGrandM%C3%A9chantLoup
// Echo "La bête se nomme ".$_GET['Name']. "\nElle possède ". $_GET['Hp'] . " points de vie \nElle inflige ". $_GET['Damage'] . " points de dégâts \nDescription : ".$_GET['Description'];
// $connexion->query("INSERT INTO bestiarium (name, hp, damage, description) VALUES ('".$_GET['Name']."', '".$_GET['Hp']."', '".$_GET['Damage']."', '".$_GET['Description']."')");

///////////////////// Récupération de toutes les bêtes depuis la base de données
// $resultat = $connexion->query("SELECT * FROM bestiarium");
// $bestiaires = $resultat->fetchAll(PDO::FETCH_ASSOC);
// foreach($bestiaires as $bestiaire){
//     $bete = new Bestiarium($bestiaire['name'], $bestiaire['hp'], $bestiaire['damage']);
//     $bete->setDescription($bestiaire['description']);
//     echo $bete . "<br>";
// }


///////////////////// Récupération d'une bête via son ID
// $resulat = $connexion->query("SELECT * FROM bestiarium WHERE id=".$_GET['id']);
// $bestiaire = $resulat->fetch(PDO::FETCH_ASSOC);
// $bete = new Bestiarium($bestiaire['name'], $bestiaire['hp'], $bestiaire['damage']);
// $bete->setDescription($bestiaire['description']);
// echo $bete;