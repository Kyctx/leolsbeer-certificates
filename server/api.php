<?php
// API журнала сертификатов LEOLSBEER.
// Пароль передаётся параметром ?key=..., потому что часть хостингов (Reg.ru)
// срезает заголовок Authorization.
declare(strict_types=1);

$cfg = require __DIR__ . '/config.php';

header('Access-Control-Allow-Origin: ' . $cfg['origin']);
header('Vary: Origin');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

function out($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!hash_equals((string)$cfg['key'], (string)($_GET['key'] ?? ''))) {
    out(['error' => 'unauthorized'], 401);
}

try {
    $pdo = new PDO(
        "mysql:host={$cfg['db_host']};dbname={$cfg['db_name']};charset=utf8mb4",
        $cfg['db_user'], $cfg['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (Throwable $e) {
    out(['error' => 'db_unavailable'], 500);
}

function row(array $r): array {
    return [
        'num'        => (int)$r['num'],
        'amount'     => (int)$r['amount'],
        'name'       => $r['name'],
        'format'     => $r['format'],
        'wish'       => $r['wish'] ?? '',
        'createdAt'  => $r['created_at'] !== null ? (int)$r['created_at'] : null,
        'redeemedAt' => $r['redeemed_at'] !== null ? (int)$r['redeemed_at'] : null,
    ];
}

$action = $_GET['action'] ?? '';
$body = json_decode(file_get_contents('php://input') ?: 'null', true);

switch ($action) {
    case 'list':
        $limit = max(1, min(500, (int)($_GET['limit'] ?? 50)));
        $st = $pdo->query("SELECT * FROM certs ORDER BY num DESC LIMIT $limit");
        out(['items' => array_map('row', $st->fetchAll())]);

    case 'get':
        $st = $pdo->prepare('SELECT * FROM certs WHERE num = ?');
        $st->execute([(int)($_GET['num'] ?? 0)]);
        $r = $st->fetch();
        out(['item' => $r ? row($r) : null]);

    case 'save':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_array($body)) out(['error' => 'bad_request'], 400);
        $num = (int)($body['num'] ?? 0);
        $amount = (int)($body['amount'] ?? 0);
        $name = trim((string)($body['name'] ?? ''));
        $format = ($body['format'] ?? 'h') === 'v' ? 'v' : 'h';
        $wish = mb_substr((string)($body['wish'] ?? ''), 0, 2000);
        if ($num <= 0 || $amount <= 0 || $name === '') out(['error' => 'bad_request'], 400);
        $st = $pdo->prepare(
            'INSERT INTO certs (num, amount, name, format, wish, created_at, redeemed_at)
             VALUES (?, ?, ?, ?, ?, ?, NULL)
             ON DUPLICATE KEY UPDATE amount = VALUES(amount), name = VALUES(name),
               format = VALUES(format), wish = VALUES(wish), created_at = VALUES(created_at)'
        );
        $st->execute([$num, $amount, mb_substr($name, 0, 200), $format, $wish, (int)round(microtime(true) * 1000)]);
        out(['ok' => true]);

    case 'update':
        // Меняется только отметка о погашении
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_array($body) || !array_key_exists('redeemedAt', $body)) {
            out(['error' => 'bad_request'], 400);
        }
        $val = $body['redeemedAt'] === null ? null : (int)round(microtime(true) * 1000);
        $st = $pdo->prepare('UPDATE certs SET redeemed_at = ? WHERE num = ?');
        $st->execute([$val, (int)($_GET['num'] ?? 0)]);
        out(['ok' => true]);

    default:
        out(['error' => 'unknown_action'], 400);
}
