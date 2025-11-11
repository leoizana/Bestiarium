<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

///////////////////// Récupération des bêtes
if ($path == "/bestiarium") {
    $resultat = $connexion->query("SELECT * FROM bestiarium");
    $bestiaires = $resultat->fetchAll(PDO::FETCH_ASSOC);
    foreach ($bestiaires as $bestiaire) {
        $bete = new Bestiarium($bestiaire['name'], $bestiaire['hp'], $bestiaire['damage']);
        $bete->setDescription($bestiaire['description']);
        echo json_encode ($bete->toArray());
    }
    
}

///////////////////// Récupération d'une bête via son ID
if (preg_match('#^/bestiarium/(\d+)$#', $path, $matches)) {
    $id = (int)$matches[1];

    $stmt = $connexion->prepare("SELECT * FROM bestiarium WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $bestiaire = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bestiaire) {
        http_response_code(404);
        echo json_encode(['error' => 'Bête non trouvée']);
        return;
    }

    $bete = new Bestiarium($bestiaire['name'], $bestiaire['hp'], $bestiaire['damage']);
    $bete->setDescription($bestiaire['description']);
    echo json_encode($bete->toArray());
    return;
}
if ($path === "/bestiarium/create") {
    header('Content-Type: application/json; charset=utf-8');

    // --- Récupération du header Authorization ---
    $auth = null;
    if (function_exists('getallheaders')) {
        foreach (getallheaders() as $k => $v) {
            if (strcasecmp($k, 'Authorization') === 0) { $auth = $v; break; }
        }
    }
    if ($auth === null && function_exists('apache_request_headers')) {
        foreach (apache_request_headers() as $k => $v) {
            if (strcasecmp($k, 'Authorization') === 0) { $auth = $v; break; }
        }
    }
    if ($auth === null && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['HTTP_AUTHORIZATION'];
    }
    if ($auth === null && !empty($_SERVER['Authorization'])) {
        $auth = $_SERVER['Authorization'];
    }

    if (!$auth || stripos($auth, 'Bearer ') !== 0) {
        http_response_code(401);
        echo json_encode(["error" => "Token manquant"]);
        return;
    }

    $token = trim(substr($auth, 7));
    try {
        $decoded = JWT::decode($token, new Key(SECRET_KEY, 'HS256'));
        $userId = $decoded->user_id ?? null;
    } catch (Throwable $e) {
        http_response_code(401);
        echo json_encode(["error" => "Token invalide"]);
        return;
    }

    if (!$userId) {
        http_response_code(401);
        echo json_encode(["error" => "Utilisateur inconnu"]);
        return;
    }

    // --- Vérifier que l'utilisateur existe ---
    $stmtUser = $connexion->prepare("SELECT id FROM user WHERE id = :id");
    $stmtUser->execute([':id' => $userId]);
    if (!$stmtUser->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(401);
        echo json_encode(["error" => "Utilisateur introuvable"]);
        return;
    }

    // --- Récupération des données JSON ---
    $data = json_decode(file_get_contents('php://input'), true) ?? [];

    $name        = $data['name'] ?? null;
    $hp          = $data['hp'] ?? null;
    $damage      = $data['damage'] ?? null;
    $description = $data['description'] ?? '';

    if (!$name || !$hp || !$damage) {
        http_response_code(400);
        echo json_encode(["error" => "Paramètres manquants"]);
        return;
    }

    // --- INSERT : placeholders nommés + bindValue explicite ---
    $sql = "INSERT INTO bestiarium (id_user, name, hp, damage, description)
            VALUES (:id_user, :name, :hp, :damage, :description)";

    $stmtInsert = $connexion->prepare($sql);

    $stmtInsert->bindValue(':id_user', $userId,      PDO::PARAM_INT);
    $stmtInsert->bindValue(':name',    $name,        PDO::PARAM_STR);
    $stmtInsert->bindValue(':hp',      (int)$hp,     PDO::PARAM_INT);
    $stmtInsert->bindValue(':damage',  (int)$damage, PDO::PARAM_INT);
    $stmtInsert->bindValue(':description', $description, PDO::PARAM_STR);

    if (!$stmtInsert->execute()) {
        $errorInfo = $stmtInsert->errorInfo();
        http_response_code(500);
        echo json_encode(["error" => $errorInfo[2] ?? "Erreur SQL"]);
        return;
    }

    echo json_encode([
        "success" => true,
        "message" => "Créé",
        "id" => $connexion->lastInsertId()
    ]);
    return;
}
