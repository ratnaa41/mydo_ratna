<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

session_start();
header('Content-Type: application/json; charset=utf-8');

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$connection = $_ENV['DB_CONNECTION'] ?? 'mysql';
$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$port = $_ENV['DB_PORT'] ?? '3306';
$database = $_ENV['DB_DATABASE'] ?? '';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';

if ($connection !== 'mysql') {
    respond(['error' => 'unsupported database driver']);
}

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (Throwable $e) {
    respond(['error' => 'database connection failed', 'message' => $e->getMessage()]);
}

function respond($data) {
    echo json_encode($data);
    exit;
}

function getInputPayload() {
    $payload = json_decode(file_get_contents('php://input'), true);
    return is_array($payload) ? $payload : [];
}

function query($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function fetchOne($sql, $params = []) {
    return query($sql, $params)->fetch();
}

function fetchAll($sql, $params = []) {
    return query($sql, $params)->fetchAll();
}

function getCurrentUser() {
    if (empty($_SESSION['hamim_user_id'])) {
        return null;
    }
    return fetchOne('SELECT id, name, email, phone, join_date FROM users WHERE id = ?', [$_SESSION['hamim_user_id']]);
}

function requireAuth() {
    $user = getCurrentUser();
    if (!$user) {
        respond(['error' => 'unauthenticated']);
    }
    return $user;
}

function sanitizeUser($user) {
    if (!$user) {
        return null;
    }
    unset($user['password']);
    return $user;
}

$action = $_GET['action'] ?? null;
$payload = getInputPayload();

switch ($action) {
    case 'register':
        $name = trim($payload['name'] ?? '');
        $email = trim(strtolower($payload['email'] ?? ''));
        $password = $payload['password'] ?? '';
        $phone = trim($payload['phone'] ?? '');

        if ($name === '' || $email === '' || $password === '') {
            respond(['error' => 'name, email, password required']);
        }
        if (strlen($password) < 6) {
            respond(['error' => 'password must be at least 6 characters']);
        }
        $existing = fetchOne('SELECT id FROM users WHERE email = ?', [$email]);
        if ($existing) {
            respond(['error' => 'email already exists']);
        }

        query(
            'INSERT INTO users (name, email, password, phone, join_date, created_at) VALUES (?, ?, ?, ?, NOW(), NOW())',
            [$name, $email, password_hash($password, PASSWORD_DEFAULT), $phone]
        );

        respond(['success' => true]);
        break;

    case 'login':
        $email = trim(strtolower($payload['email'] ?? ''));
        $password = $payload['password'] ?? '';

        $user = fetchOne('SELECT * FROM users WHERE email = ?', [$email]);
        if (!$user || !password_verify($password, $user['password'])) {
            respond(['error' => 'invalid email or password']);
        }

        $_SESSION['hamim_user_id'] = $user['id'];
        respond(['user' => sanitizeUser($user)]);
        break;

    case 'logout':
        unset($_SESSION['hamim_user_id']);
        respond(['success' => true]);
        break;

    case 'checkAuth':
        $user = getCurrentUser();
        respond(['authenticated' => $user !== null, 'user' => $user]);
        break;

    case 'updateProfile':
        $user = requireAuth();
        $name = trim($payload['name'] ?? '');
        $email = trim(strtolower($payload['email'] ?? ''));
        $phone = trim($payload['phone'] ?? '');
        $password = $payload['password'] ?? '';

        if ($name === '' || $email === '') {
            respond(['error' => 'name and email required']);
        }

        $conflict = fetchOne('SELECT id FROM users WHERE email = ? AND id != ?', [$email, $user['id']]);
        if ($conflict) {
            respond(['error' => 'email already in use']);
        }

        if ($password !== '') {
            query('UPDATE users SET name = ?, email = ?, phone = ?, password = ? WHERE id = ?', [$name, $email, password_hash($password, PASSWORD_DEFAULT), $phone, $user['id']]);
        } else {
            query('UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?', [$name, $email, $phone, $user['id']]);
        }

        respond(['success' => true]);
        break;

    case 'getTasks':
        $user = requireAuth();
        $tasks = fetchAll('SELECT * FROM tasks WHERE user_id = ? ORDER BY id ASC', [$user['id']]);
        respond($tasks);
        break;

    case 'addTask':
        $user = requireAuth();
        $title = trim($payload['title'] ?? '');
        $priority = $payload['priority'] ?? 'medium';
        $category = $payload['category'] ?? 'personal';
        $notes = trim($payload['notes'] ?? '');

        if ($title === '') {
            respond(['error' => 'task title required']);
        }

        query(
            'INSERT INTO tasks (user_id, title, priority, category, notes, completed, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())',
            [$user['id'], $title, $priority, $category, $notes]
        );

        respond(['success' => true]);
        break;

    case 'toggleTask':
        $user = requireAuth();
        $id = intval($payload['id'] ?? 0);
        $task = fetchOne('SELECT * FROM tasks WHERE id = ? AND user_id = ?', [$id, $user['id']]);
        if (!$task) {
            respond(['error' => 'task not found']);
        }

        $completed = !boolval($payload['completed']);
        $completedAt = $completed ? 'NOW()' : 'NULL';
        query(
            "UPDATE tasks SET completed = ?, completed_at = {$completedAt} WHERE id = ? AND user_id = ?",
            [$completed ? 1 : 0, $id, $user['id']]
        );

        respond(['success' => true]);
        break;

    case 'deleteTask':
        $user = requireAuth();
        $id = intval($payload['id'] ?? 0);
        query('DELETE FROM tasks WHERE id = ? AND user_id = ?', [$id, $user['id']]);
        respond(['success' => true]);
        break;

    case 'getTransactions':
        $user = requireAuth();
        $transactions = fetchAll('SELECT * FROM transactions WHERE user_id = ? ORDER BY id ASC', [$user['id']]);
        respond($transactions);
        break;

    case 'addTransaction':
        $user = requireAuth();
        $amount = floatval($payload['amount'] ?? 0);
        $title = trim($payload['title'] ?? '');
        $category = trim($payload['category'] ?? 'uncategorized');
        $date = $payload['date'] ?? date('Y-m-d');
        $notes = trim($payload['notes'] ?? '');
        $type = $payload['type'] ?? 'income';

        if ($amount <= 0 || $title === '') {
            respond(['error' => 'amount and title required']);
        }

        query(
            'INSERT INTO transactions (user_id, type, amount, title, category, transaction_date, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
            [$user['id'], $type, $amount, $title, $category, $date, $notes]
        );

        respond(['success' => true]);
        break;

    case 'deleteTransaction':
        $user = requireAuth();
        $id = intval($payload['id'] ?? 0);
        query('DELETE FROM transactions WHERE id = ? AND user_id = ?', [$id, $user['id']]);
        respond(['success' => true]);
        break;

    case 'getNotes':
        $user = requireAuth();
        $notes = fetchAll('SELECT * FROM notes WHERE user_id = ? ORDER BY id ASC', [$user['id']]);
        respond($notes);
        break;

    case 'addNote':
        $user = requireAuth();
        $title = trim($payload['title'] ?? '');
        $content = trim($payload['content'] ?? '');

        if ($title === '') {
            respond(['error' => 'note title required']);
        }

        query(
            'INSERT INTO notes (user_id, title, content, created_at) VALUES (?, ?, ?, NOW())',
            [$user['id'], $title, $content]
        );

        respond(['success' => true]);
        break;

    case 'deleteNote':
        $user = requireAuth();
        $id = intval($payload['id'] ?? 0);
        query('DELETE FROM notes WHERE id = ? AND user_id = ?', [$id, $user['id']]);
        respond(['success' => true]);
        break;

    case 'getStats':
        $user = requireAuth();
        $totalTasks = fetchOne('SELECT COUNT(*) AS count FROM tasks WHERE user_id = ?', [$user['id']]);
        $totalNotes = fetchOne('SELECT COUNT(*) AS count FROM notes WHERE user_id = ?', [$user['id']]);
        $totals = fetchOne("SELECT
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) AS income,
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS expense
            FROM transactions WHERE user_id = ?",
            [$user['id']]
        );

        respond([
            'totalTasks' => intval($totalTasks['count'] ?? 0),
            'totalIncome' => number_format(floatval($totals['income'] ?? 0), 2, '.', ''),
            'totalExpense' => number_format(floatval($totals['expense'] ?? 0), 2, '.', ''),
            'totalNotes' => intval($totalNotes['count'] ?? 0),
        ]);
        break;

    default:
        respond(['error' => 'invalid action']);
}
