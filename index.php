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

// parse_url() analyse une URL et retourne ses composants
$parsed_url = parse_url($_SERVER['REQUEST_URI']);

// soit l'url en question a un chemin et sinon le chemin est la racine
$path = isset($parsed_url['path']) ? $parsed_url['path'] : '/';

// si le chemin est bien toto alors on fait appel au fichier
if (strpos($path, '/bestiarium') === 0) {
    require_once($_SERVER["DOCUMENT_ROOT"].'/controllers/Bestiarium.controller.php');
}
if (strpos($path, '/user') === 0) {
    require_once($_SERVER["DOCUMENT_ROOT"].'/controllers/Users.controller.php');
}
