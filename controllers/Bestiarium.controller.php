<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

///////////////////// Récupération des bêtes
if ($path == "/bestiarium") {
    $resultat = $connexion->query("SELECT * FROM bestiarium");
    $bestiaires = $resultat->fetchAll(PDO::FETCH_ASSOC);
    foreach ($bestiaires as $bestiaire) {
        $bete = new Bestiarium($bestiaire['name'], $bestiaire['image']);
        $bete->setHp((int)$bestiaire['hp']);
        $bete->setDamage((int)$bestiaire['damage']);
        $bete->setDefense((int)($bestiaire['defense'] ?? 0));
        $bete->setHeads((int)($bestiaire['heads'] ?? 1));
        $bete->setDescription($bestiaire['description'] ?? '');
        echo json_encode($bete->toArray());
    }
}

if (preg_match('#^/bestiarium/delete/(\d+)$#', $path, $matches)) {
    $id = (int)$matches[1];
    $stmt = $connexion->prepare("SELECT * FROM bestiarium WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $bestiaire = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$bestiaire) {
        http_response_code(404);
        echo json_encode(['error' => 'Bête non trouvée']);
        return;
    }
    $stmt = $connexion->prepare("DELETE FROM bestiarium WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $bestiaire = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'message' => 'Bête supprimée']);
    return;
}


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

    $bete = new Bestiarium($bestiaire['name'], $bestiaire['image']);
    $bete->setHp((int)$bestiaire['hp']);
    $bete->setDamage((int)$bestiaire['damage']);
    $bete->setDefense((int)($bestiaire['defense'] ?? 0));
    $bete->setHeads((int)($bestiaire['heads'] ?? 1));
    $bete->setDescription($bestiaire['description'] ?? '');
    echo json_encode($bete->toArray());
    return;
}

if ($path === "/bestiarium/create") {
    header('Content-Type: application/json; charset=utf-8');

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

    $stmt = $connexion->prepare("SELECT id FROM user WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(401);
        echo json_encode(["error" => "Utilisateur introuvable"]);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true) ?? [];

    $name        = $data['name'] ?? null;
    $hp          = $data['hp'] ?? null;
    $damage      = $data['damage'] ?? null;
    $defense     = $data['defense'] ?? null;
    $heads       = $data['heads'] ?? null;
    $description = $data['description'] ?? null;

    if ($name === null || trim($name) === '') {
        http_response_code(400);
        echo json_encode(["error" => "Le nom est requis"]);
        return;
    }

    $needsAi = (
        $hp === null ||
        $damage === null ||
        $defense === null ||
        $heads === null ||
        $description === null ||
        trim((string)$description) === ''
    );

    if ($needsAi) {
        $system = "Tu génères des créatures de bestiaire JDR. Réponds UNIQUEMENT en JSON strict, sans texte autour, avec EXACTEMENT ces clés: hp (int 10-300), damage (int 1-80), defense (int 0-60), heads (int 1-7), description (string FR, 1-3 phrases, cohérentes avec le nom).";
        $userPrompt = "Génère les stats pour la créature nommée \"{$name}\". Respecte les bornes et renvoie du JSON strict uniquement.";

        $base = "https://text.pollinations.ai/";
        $url  = $base . rawurlencode($userPrompt)
             . "?json=true"
             . "&model=" . rawurlencode("openai")
             . "&temperature=1"
             . "&system=" . rawurlencode($system);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => ["Accept: application/json"],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError || !$response) {
            http_response_code(502);
            echo json_encode([
                "error"   => "Erreur lors de l'appel à l'IA (Pollinations)",
                "details" => $curlError ?: "Réponse vide"
            ]);
            return;
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            http_response_code(502);
            echo json_encode([
                "error"   => "Erreur HTTP Pollinations",
                "code"    => $httpCode,
                "upstream"=> $response
            ]);
            return;
        }

        $ai = json_decode($response, true);

        $okJson = is_array($ai)
            && isset($ai['hp'], $ai['damage'], $ai['defense'], $ai['heads'], $ai['description'])
            && is_numeric($ai['hp'])
            && is_numeric($ai['damage'])
            && is_numeric($ai['defense'])
            && is_numeric($ai['heads'])
            && is_string($ai['description']);

        if (!$okJson) {
            http_response_code(502);
            echo json_encode([
                "error" => "Réponse IA mal formatée",
                "raw"   => $response
            ]);
            return;
        }
        if ($hp === null)        $hp = (int)$ai['hp'];
        if ($damage === null)    $damage = (int)$ai['damage'];
        if ($defense === null)   $defense = (int)$ai['defense'];
        if ($heads === null)     $heads = (int)$ai['heads'];
        if ($description === null || trim($description) === '') {
            $description = trim($ai['description']);
        }
    }

    $imgPrompt = $name . " — " . $description . ". dark fantasy creature, ultra detailed, dramatic lighting, 8k, concept art";
    $image = "https://image.pollinations.ai/prompt/" . rawurlencode($imgPrompt) . "?width=768&height=768&nologo";

    $sql = "INSERT INTO bestiarium (id_user, name, hp, damage, defense, heads, description, image)
            VALUES (:id_user, :name, :hp, :damage, :defense, :heads, :description, :image)";

    $stmt = $connexion->prepare($sql);
    $ok = $stmt->execute([
        ':id_user'     => $userId,
        ':name'        => $name,
        ':hp'          => (int)$hp,
        ':damage'      => (int)$damage,
        ':defense'     => (int)$defense,
        ':heads'       => (int)$heads,
        ':description' => $description,
        ':image'       => $image,
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
        "id" => $connexion->lastInsertId(),
        "generated" => [
            "hp" => (int)$hp,
            "damage" => (int)$damage,
            "defense" => (int)$defense,
            "heads" => (int)$heads,
            "description" => $description,
            "image" => $image
        ]
    ]);
    return;
}

