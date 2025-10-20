<?php
///////////////////// Récupération des bêtes
if ($path == "/bestiarium/viewall") {
    $resultat = $connexion->query("SELECT * FROM bestiarium");
    $bestiaires = $resultat->fetchAll(PDO::FETCH_ASSOC);
    foreach ($bestiaires as $bestiaire) {
        $bete = new Bestiarium($bestiaire['name'], $bestiaire['hp'], $bestiaire['damage']);
        $bete->setDescription($bestiaire['description']);
        echo $bete . "<br>";
    }
}

///////////////////// Récupération d'une bête via son ID
if ($path == "/bestiarium/view") {
    $resulat = $connexion->query("SELECT * FROM bestiarium WHERE id=" . $_GET['id']);
    $bestiaire = $resulat->fetch(PDO::FETCH_ASSOC);
    $bete = new Bestiarium($bestiaire['name'], $bestiaire['hp'], $bestiaire['damage']);
    $bete->setDescription($bestiaire['description']);
    echo $bete;
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

