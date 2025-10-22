<?php

if ($path == "/user/create") {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $contentType = $_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? '');
    $data = [];

    if ($method === 'POST') {
        if (stripos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input'); // <= corrigé
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

    $username = $data['username'] ?? null;
    $password = $data['password'] ?? null;
    $email = $data['email'] ?? null;

    if ($username === null || $password === null || $email === null) {
        http_response_code(400);
        echo json_encode(["error" => "Paramètres manquants"]);
        return;
    }

    $stmt = $connexion->prepare("
        INSERT INTO user (username, password, email)
        VALUES (:username, :password, :email)
    ");

    $stmt->execute([
        ':username' => $username,
        ':password' => password_hash($password, PASSWORD_DEFAULT),
        ':email' => $email,
    ]);

    echo json_encode(["success" => true, "message" => "Utilisateur créer"]);
}
if ($path == "/user/login") {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $contentType = $_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? '');
    $data = [];
    if ($method === 'POST') {
        if (stripos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input'); // <= corrigé
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
    $username = $data['username'];
    $password = $data['password'];

    $stmt = $connexion->prepare("
        SELECT id, username, password from user WHERE username=:username
    ");
    $stmt->execute([
        ':username' => $username,

    ]);
     $user = $stmt->fetch(PDO::FETCH_ASSOC);
    // echo json_encode($results);
    // echo json_encode($password);
    // echo json_encode(password_verify($password ,$results[0]['password']));
     if ($user && password_verify($password, $user['password'])) {

        $token = bin2hex(random_bytes(32));

        $stmt = $connexion->prepare("UPDATE user SET token=:token WHERE id=:id");
        $stmt->execute([':token' => $token, ':id' => $user['id']]);
        var_dump($user['id']);
        // var_dump($stmt);

        echo json_encode([
            "success" => true,
            "message" => "Utilisateur connecté",
            "token" => $token
        ]);
    } else {
        http_response_code(401);
        echo json_encode(["error" => "Echec de la connexion"]);
    }
    // echo json_encode ($row["password"]);
    // echo json_encode(password_verify( $passworddb, PASSWORD_DEFAULT));
}

if ($path == "/user/logout") {
    // Récupération du header Authorization (Bearer token que je recupère depuis postman)
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? null;

    if ($auth && stripos($auth, 'Bearer ') === 0) {
        $token = trim(substr($auth, 7)); // enlève "Bearer "
    } else {
        $token = null;
    }

    if ($token) {
        $stmt = $connexion->prepare("UPDATE user SET token=NULL WHERE token=:token");
        $stmt->execute([':token' => $token]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => true, "message" => "Déconnecté"]);
            return;
        } else {
            echo json_encode(["error" => true, "message" => "Token invalide ou déjà déconnecté"]);
            return;
        }
    }

    echo json_encode(["error" => true, "message" => "Pas Déconnecté"]);
}
