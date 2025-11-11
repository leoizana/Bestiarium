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

    // 1. Récupération + décodage du token
    $token = getBearerToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(["error" => "Token manquant"]);
        return;
    }

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

    // 2. Vérifier que l'utilisateur existe
    $stmt = $connexion->prepare("SELECT id FROM user WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(401);
        echo json_encode(["error" => "Utilisateur introuvable"]);
        return;
    }

    // 3. Récupération des données
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

    // 4. INSERT
    $sql = "INSERT INTO bestiarium (id_user, name, hp, damage, description)
            VALUES (:id_user, :name, :hp, :damage, :description)";

    $stmt = $connexion->prepare($sql);
    $ok = $stmt->execute([
        ':id_user'     => $userId,
        ':name'        => $name,
        ':hp'          => (int)$hp,
        ':damage'      => (int)$damage,
        ':description' => $description,
    ]);

    if (!$ok) {
        $errorInfo = $stmt->errorInfo();
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
