<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if ($path == "/user/create") {
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
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) $data = $decoded;
        } else $data = $_POST;
    } else $data = $_GET;

    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    $stmt = $connexion->prepare("SELECT id, username, password FROM user WHERE username=:username");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $payload = [
            'iat' => time(),
            'exp' => time() + 3600,
            'user_id' => $user['id'],
            'username' => $user['username']
        ];

        $jwt = JWT::encode($payload, SECRET_KEY , 'HS256');

        echo json_encode([
            "success" => true,
            "message" => "Utilisateur connecté",
            "token" => $jwt
        ]);
    } else {
        http_response_code(401);
        echo json_encode(["error" => "Echec de la connexion"]);
    }
}

if ($path == "/user/logout") {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(204);
    return;
}

if ($path == "/user/me") {
    header('Content-Type: application/json; charset=utf-8');

    $debug = isset($_GET['debug']);
    $diag  = [
        'step' => 'start',
        'has_auth' => false,
        'has_bearer' => false,
        'token_segments' => null,
        'jwt_alg' => null,
        'exp' => null,
        'now' => time(),
        'dt' => null,
        'secret_fp' => substr(hash('sha256', (string)SECRET_KEY), 0, 12),
        'decode_error' => null,
        'decode_error_class' => null,
    ];

    $token = getBearerToken();
    $diag['has_auth'] = $token !== null;

    if (!$token) {
        if ($debug) {
            http_response_code(401);
            $diag['step'] = 'missing_bearer';
            echo json_encode(['error' => 'Authorization manquant', 'diag' => $diag]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Authorization manquant']);
        }
        return;
    }

    $diag['has_bearer'] = true;

    $parts = explode('.', $token);
    $diag['token_segments'] = count($parts);
    if ($diag['token_segments'] === 3) {
        $hdrJson = json_decode(base64_decode(strtr($parts[0], '-_', '+/')), true);
        $pldJson = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        $diag['jwt_alg'] = $hdrJson['alg'] ?? null;
        $diag['exp']     = $pldJson['exp'] ?? null;
        if (is_numeric($diag['exp'])) {
            $diag['dt'] = (int)$diag['exp'] - $diag['now'];
        }
    }

    if ($diag['token_segments'] !== 3) {
        if ($debug) {
            http_response_code(401);
            $diag['step'] = 'bad_segments';
            echo json_encode(['error' => 'Token mal formé', 'diag' => $diag]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Token invalide ou expiré']);
        }
        return;
    }

    if ($diag['jwt_alg'] !== null && strcasecmp($diag['jwt_alg'], 'HS256') !== 0) {
        if ($debug) {
            http_response_code(401);
            $diag['step'] = 'wrong_alg';
            echo json_encode(['error' => 'Algorithme JWT non supporté (HS256 attendu)', 'diag' => $diag]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Token invalide ou expiré']);
        }
        return;
    }

    if ($diag['exp'] !== null && is_numeric($diag['exp']) && $diag['exp'] < ($diag['now'] - 60)) {
        if ($debug) {
            http_response_code(401);
            $diag['step'] = 'expired_precheck';
            echo json_encode(['error' => 'Token expiré', 'diag' => $diag]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Token invalide ou expiré']);
        }
        return;
    }

    JWT::$leeway = 60;
    try {
        $decoded = JWT::decode($token, new Key((string)SECRET_KEY, 'HS256'));
        $claims = (array)$decoded;

        echo json_encode([
            'user' => $claims['user_id'] ?? null,
            'username' => $claims['username'] ?? null,
            'diag' => $debug ? $diag : null
        ]);
    } catch (Throwable $e) {
        $diag['step'] = 'decode_failed';
        $diag['decode_error'] = $e->getMessage();
        $diag['decode_error_class'] = get_class($e);
        error_log('[ME][fp='.$diag['secret_fp'].'] '.$e->getMessage());

        http_response_code(401);
        echo json_encode([
            'error' => 'Token invalide ou expiré',
            'diag' => $debug ? $diag : null
        ]);
    }
    return;
}