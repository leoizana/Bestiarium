<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if ($path == "/matchs") {
    header('Content-Type: application/json; charset=utf-8');
    $sql = "
        SELECT
            c.id, c.creature_1, c.creature_2, c.result, c.created_at,
            b1.name AS c1_name, u1.id AS u1_id, u1.username AS u1_username,
            b2.name AS c2_name, u2.id AS u2_id, u2.username AS u2_username
        FROM fight c
        JOIN bestiarium b1 ON b1.id = c.creature_1
        JOIN user u1       ON u1.id = b1.id_user
        JOIN bestiarium b2 ON b2.id = c.creature_2
        JOIN user u2       ON u2.id = b2.id_user
        ORDER BY c.id DESC
    ";
    $rows = $connexion->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $out = [];
    foreach ($rows as $r) {
        $winner = null;
        if ($r['result'] === 'creature_1') {
            $winner = [
                'user_id' => (int)$r['u1_id'],
                'username' => $r['u1_username'],
                'creature_id' => (int)$r['creature_1'],
                'creature_name' => $r['c1_name'],
            ];
        } elseif ($r['result'] === 'creature_2') {
            $winner = [
                'user_id' => (int)$r['u2_id'],
                'username' => $r['u2_username'],
                'creature_id' => (int)$r['creature_2'],
                'creature_name' => $r['c2_name'],
            ];
        }
        $out[] = [
            'id' => (int)$r['id'],
            'result' => $r['result'],
            'created_at' => $r['created_at'],
            'player1' => [
                'user_id' => (int)$r['u1_id'],
                'username' => $r['u1_username'],
                'creature' => ['id' => (int)$r['creature_1'], 'name' => $r['c1_name']]
            ],
            'player2' => [
                'user_id' => (int)$r['u2_id'],
                'username' => $r['u2_username'],
                'creature' => ['id' => (int)$r['creature_2'], 'name' => $r['c2_name']]
            ],
            'winner' => $winner
        ];
    }
    echo json_encode($out);
    return;
}

if (preg_match('#^/matchs/(\d+)$#', $path, $m)) {
    header('Content-Type: application/json; charset=utf-8');
    $id = (int)$m[1];
    $sql = "
        SELECT
            c.id, c.creature_1, c.creature_2, c.result, c.created_at,
            b1.name AS c1_name, u1.id AS u1_id, u1.username AS u1_username,
            b2.name AS c2_name, u2.id AS u2_id, u2.username AS u2_username
        FROM fight c
        JOIN bestiarium b1 ON b1.id = c.creature_1
        JOIN user u1       ON u1.id = b1.id_user
        JOIN bestiarium b2 ON b2.id = c.creature_2
        JOIN user u2       ON u2.id = b2.id_user
        WHERE c.id = :id
        LIMIT 1
    ";
    $stmt = $connexion->prepare($sql);
    $stmt->execute([':id' => $id]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$r) { http_response_code(404); echo json_encode(['error' => 'Match non trouvé']); return; }
    $winner = null;
    if ($r['result'] === 'creature_1') {
        $winner = [
            'user_id' => (int)$r['u1_id'],
            'username' => $r['u1_username'],
            'creature_id' => (int)$r['creature_1'],
            'creature_name' => $r['c1_name'],
        ];
    } elseif ($r['result'] === 'creature_2') {
        $winner = [
            'user_id' => (int)$r['u2_id'],
            'username' => $r['u2_username'],
            'creature_id' => (int)$r['creature_2'],
            'creature_name' => $r['c2_name'],
        ];
    }
    echo json_encode([
        'id' => (int)$r['id'],
        'result' => $r['result'],
        'created_at' => $r['created_at'],
        'player1' => [
            'user_id' => (int)$r['u1_id'],
            'username' => $r['u1_username'],
            'creature' => ['id' => (int)$r['creature_1'], 'name' => $r['c1_name']]
        ],
        'player2' => [
            'user_id' => (int)$r['u2_id'],
            'username' => $r['u2_username'],
            'creature' => ['id' => (int)$r['creature_2'], 'name' => $r['c2_name']]
        ],
        'winner' => $winner
    ]);
    return;
}

if (preg_match('#^/matchs/delete/(\d+)$#', $path, $m)) {
    header('Content-Type: application/json; charset=utf-8');
    $id = (int)$m[1];
    $sql = "
        DELETE FROM fight WHERE id = :id";
    $stmt = $connexion->prepare($sql);
    $stmt->execute([':id' => $id]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'message' => 'Match supprimé']);
    return;
}

if ($path === "/matchs/create") {
    header('Content-Type: application/json; charset=utf-8');

    $token = getBearerToken();
    if (!$token) { http_response_code(401); echo json_encode(["error" => "Token manquant"]); return; }

    try { $decoded = JWT::decode($token, new Key(SECRET_KEY, 'HS256')); $userId = $decoded->user_id ?? null;
    } catch (Throwable $e) { http_response_code(401); echo json_encode(["error" => "Token invalide"]); return; }

    if (!$userId) { http_response_code(401); echo json_encode(["error" => "Utilisateur inconnu"]); return; }

    $stmt = $connexion->prepare("SELECT id, username FROM user WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    $me = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$me) { http_response_code(401); echo json_encode(["error" => "Utilisateur introuvable"]); return; }

    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $opponentUsername = isset($data['opponent']) ? trim($data['opponent']) : '';
    if ($opponentUsername === '') { http_response_code(400); echo json_encode(["error" => "Paramètre 'opponent' requis (pseudo)"]); return; }

    $stmt = $connexion->prepare("SELECT id, username FROM user WHERE username = :u LIMIT 1");
    $stmt->execute([':u' => $opponentUsername]);
    $opp = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$opp) { http_response_code(404); echo json_encode(["error" => "Adversaire introuvable"]); return; }
    $oppId = (int)$opp['id'];

    $stmt = $connexion->prepare("SELECT id, name, hp, damage, defense, heads FROM bestiarium WHERE id_user = :uid ORDER BY RAND() LIMIT 1");
    $stmt->execute([':uid' => $userId]);
    $A = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$A) { http_response_code(404); echo json_encode(["error" => "Aucune créature pour l'utilisateur connecté"]); return; }

    $stmt->execute([':uid' => $oppId]);
    $B = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$B) { http_response_code(404); echo json_encode(["error" => "Aucune créature pour l'adversaire"]); return; }

    if ((int)$A['id'] === (int)$B['id'] && $userId === $oppId) {
        $stmt = $connexion->prepare("SELECT id, name, hp, damage, defense, heads FROM bestiarium WHERE id_user = :uid AND id <> :avoid ORDER BY RAND() LIMIT 1");
        $stmt->execute([':uid' => $userId, ':avoid' => (int)$A['id']]);
        $B = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$B) { http_response_code(400); echo json_encode(["error" => "Pas assez de créatures distinctes pour un duel"]); return; }
    }

    $A['hp'] = (int)$A['hp']; $A['damage'] = (int)$A['damage']; $A['defense'] = (int)($A['defense'] ?? 0); $A['heads'] = (int)($A['heads'] ?? 1);
    $B['hp'] = (int)$B['hp']; $B['damage'] = (int)$B['damage']; $B['defense'] = (int)($B['defense'] ?? 0); $B['heads'] = (int)($B['heads'] ?? 1);

    $effDam = function($dmg, $heads, $oppDef) { return max(1, (int)floor($dmg * max(1, $heads) - floor(max(0,$oppDef) / 2))); };
    $AtoB = $effDam($A['damage'], $A['heads'], $B['defense']);
    $BtoA = $effDam($B['damage'], $B['heads'], $A['defense']);

    $turnsA = (int)ceil(max(1,$B['hp']) / max(1,$AtoB));
    $turnsB = (int)ceil(max(1,$A['hp']) / max(1,$BtoA));

    $winner = null;
    if ($turnsA < $turnsB) $winner = $A['id'];
    elseif ($turnsB < $turnsA) $winner = $B['id'];
    else $winner = ($AtoB === $BtoA) ? 0 : ($AtoB > $BtoA ? $A['id'] : $B['id']);

    $result = $winner === 0 ? 'draw' : ($winner === $A['id'] ? 'creature_1' : 'creature_2');

    $sql = "INSERT INTO fight (user_id, creature_1, creature_2, result, created_at) VALUES (:user_id, :c1, :c2, :result, NOW())";
    $stmt = $connexion->prepare($sql);
    $ok = $stmt->execute([
        ':user_id' => $userId,
        ':c1' => (int)$A['id'],
        ':c2' => (int)$B['id'],
        ':result' => $result
    ]);
    if (!$ok) { $errorInfo = $stmt->errorInfo(); http_response_code(500); echo json_encode(["error" => $errorInfo[2] ?? "Erreur SQL"]); return; }

    $isAwin = ($result === 'creature_1');
    $winner_user_id = $isAwin ? (int)$me['id'] : $oppId;
    $winner_username = $isAwin ? $me['username'] : $opp['username'];
    $winner_creature = $isAwin ? $A : $B;

    http_response_code(201);
    echo json_encode([
        "success" => true,
        "match_id" => $connexion->lastInsertId(),
        "result" => $result,
        "winner" => [
            "user_id" => $winner_user_id,
            "username" => $winner_username,
            "creature_id" => (int)$winner_creature['id'],
            "creature_name" => $winner_creature['name']
        ],
        "players" => [
            [
                "user_id" => (int)$me['id'],
                "username" => $me['username'],
                "creature" => ["id" => (int)$A['id'], "name" => $A['name']]
            ],
            [
                "user_id" => $oppId,
                "username" => $opp['username'],
                "creature" => ["id" => (int)$B['id'], "name" => $B['name']]
            ]
        ]
    ]);
    return;
}
