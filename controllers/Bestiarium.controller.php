<?php
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

if ($path == "/bestiarium/create") {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $contentType = $_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? '');
    $data = [];
    if ($method === 'POST') {
        if (stripos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        } else {
            $data = $_POST; 
        }
    } else {
        $data = $_GET; 
    }

    $name = $data['name'] ?? $data['Name'] ?? null;
    $hp = $data['hp'] ?? $data['Hp'] ?? null;
    $damage = $data['damage'] ?? $data['Damage'] ?? null;
    $description = $data['description'] ?? $data['Description'] ?? null;

    if ($name === null || $hp === null || $damage === null) {
        http_response_code(400);
        echo "Paramètres manquants.";
        return;
    }
    $stmt = $connexion->prepare(
        "INSERT INTO bestiarium (name, hp, damage, description) VALUES (:name, :hp, :damage, :description)"
    );
    $stmt->execute([
        ':name' => $name,
        ':hp' => $hp,
        ':damage' => $damage,
        ':description' => $description,
    ]);
}

