<?php
spl_autoload_register(function ($classe){
    include "classes/" . $classe . ".class.php";
});
require_once __DIR__ . '/vendor/autoload.php';

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
function getBearerToken(): ?string
{
    $auth = null;

    if (function_exists('getallheaders')) {
        foreach (getallheaders() as $k => $v) {
            if (strcasecmp($k, 'Authorization') === 0) {
                $auth = $v;
                break;
            }
        }
    }

    if ($auth === null && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['HTTP_AUTHORIZATION'];
    }

    if ($auth === null && !empty($_SERVER['Authorization'])) {
        $auth = $_SERVER['Authorization'];
    }

    if (!$auth || stripos($auth, 'Bearer ') !== 0) {
        return null;
    }

    return trim(substr($auth, 7));
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
