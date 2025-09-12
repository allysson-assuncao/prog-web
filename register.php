<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

$storageDir = __DIR__ . DIRECTORY_SEPARATOR . 'data';
$csvPath = $storageDir . DIRECTORY_SEPARATOR . 'students.csv';

if (!is_dir($storageDir)) {
    @mkdir($storageDir, 0777, true);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function renderPage(string $title, string $body): void {
    echo '<!DOCTYPE html>';
    echo '<html lang="pt-BR">';
    echo '<head>';
    echo '  <meta charset="UTF-8"/>';
    echo '  <meta name="viewport" content="width=device-width, initial-scale=1"/>';
    echo "  <title>" . h($title) . "</title>";
    echo '  <style>';
    echo '    :root { --bg:#f6f8fb; --card-bg:#ffffff; --text:#111827; --muted:#6b7280; --border:#e5e7eb; --primary:#0b5ed7; --primary-600:#0a53be; --ring: rgba(11,94,215,.25);}';
    echo '    *{box-sizing:border-box} html,body{height:100%} body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Inter,Arial,sans-serif;color:var(--text);background:radial-gradient(1200px 600px at 10% -10%, #e8efff 0%, transparent 60%),radial-gradient(1200px 600px at 110% 110%, #e8fff3 0%, transparent 60%),var(--bg);display:grid;place-items:center;padding:24px}';
    echo '    .card{width:min(720px,94vw);background:var(--card-bg);border:1px solid var(--border);border-radius:14px;box-shadow:0 10px 30px rgba(0,0,0,.06);overflow:hidden} .header{padding:22px;border-bottom:1px solid var(--border);background:linear-gradient(180deg, rgba(11,94,215,.06), rgba(11,94,215,0))}';
    echo '    .title{margin:0;font-size:1.35rem;font-weight:700;letter-spacing:.2px;display:flex;align-items:center;gap:10px} .title .logo{width:28px;height:28px;display:grid;place-items:center;background:var(--primary);color:#fff;border-radius:8px;font-weight:700;box-shadow:0 6px 16px rgba(11,94,215,.35)}';
    echo '    .content{padding:22px;display:grid;gap:12px} .msg{padding:12px 14px;border:1px solid var(--border);border-radius:10px;background:#fff}';
    echo '    .ok{border-color:#86efac;background:#ecfdf5} .error{border-color:#fca5a5;background:#fef2f2}';
    echo '    .actions{display:flex;gap:10px;margin-top:6px} .btn{appearance:none;border:1px solid var(--border);background:#fff;color:var(--text);height:40px;padding:0 14px;border-radius:10px;font-weight:600;cursor:pointer} .btn.primary{background:var(--primary);border-color:var(--primary);color:#fff}';
    echo '    table{width:100%;border-collapse:collapse;border:1px solid var(--border)} th,td{padding:10px;border-bottom:1px solid var(--border);text-align:left} th{background:#f8fafc}';
    echo '    @media (prefers-color-scheme: dark){:root{--bg:#0b1220;--card-bg:#0f1629;--text:#e5e7eb;--muted:#9aa4b2;--border:#1f2a44;--ring:rgba(11,94,215,.35)} body{background:radial-gradient(1200px 600px at 10% -10%, rgba(11,94,215,.15) 0%, transparent 60%),radial-gradient(1200px 600px at 110% 110%, rgba(16,185,129,.12) 0%, transparent 60%),var(--bg)} .btn{background:#111a32;color:var(--text)} .msg{background:#0f1629}}';
    echo '  </style>';
    echo '</head><body>';
    echo '  <main class="card" role="main">';
    echo '    <div class="header"><h1 class="title"><span class="logo" aria-hidden="true">🎓</span>Cadastro de Aluno</h1></div>';
    echo '    <section class="content">' . $body . '</section>';
    echo '  </main>';
    echo '</body></html>';
}

function formatPhone(string $raw): string {
    $digits = preg_replace('/\D+/', '', $raw);
    if (strlen($digits) < 10) {
        return $raw; // fallback
    }
    $dd = substr($digits, 0, 2);
    if (strlen($digits) === 11) {
        $p1 = substr($digits, 2, 5);
        $p2 = substr($digits, 7, 4);
    } else {
        $p1 = substr($digits, 2, 4);
        $p2 = substr($digits, 6, 4);
    }
    return sprintf('(%s) %s-%s', $dd, $p1, $p2);
}

if ($method === 'GET' && isset($_GET['list'])) {
    $rows = [];
    if (is_file($csvPath)) {
        $f = fopen($csvPath, 'r');
        if ($f) {
            // Skip header if present
            $first = true;
            while (($data = fgetcsv($f)) !== false) {
                if ($first && isset($data[0]) && $data[0] === 'nome') { $first = false; continue; }
                $first = false;
                if (count($data) >= 4) {
                    $rows[] = $data;
                }
            }
            fclose($f);
        }
    }

    ob_start();
    if (empty($rows)) {
        echo '<div class="msg">Nenhum cadastro encontrado.</div>';
    } else {
        echo '<div class="msg ok">' . count($rows) . ' cadastro(s) encontrado(s).</div>';
        echo '<div style="overflow:auto">';
        echo '<table aria-label="Lista de alunos cadastrados">';
        echo '<thead><tr><th>Nome</th><th>Endereço</th><th>Telefone</th><th>Data/Hora</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            [$n, $e, $t, $d] = $r;
            echo '<tr>';
            echo '<td>' . h($n) . '</td>';
            echo '<td>' . h($e) . '</td>';
            echo '<td>' . h($t) . '</td>';
            echo '<td>' . h($d) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }
    echo '<div class="actions"><a class="btn" href="index.html">Voltar</a></div>';
    $body = ob_get_clean();
    renderPage('Lista de Cadastros', $body);
    exit;
}

if ($method !== 'POST') {
    $body = '<div class="msg">Use o formulário para cadastrar um aluno.</div>' .
            '<div class="actions">' .
            '  <a class="btn" href="index.html">Voltar</a>' .
            '  <a class="btn primary" href="register.php?list=1">Listar cadastros</a>' .
            '</div>';
    renderPage('Cadastro de Aluno', $body);
    exit;
}

// Processa POST
$name    = trim((string)($_POST['name'] ?? ''));
$address = trim((string)($_POST['address'] ?? ''));
$phone   = trim((string)($_POST['phone'] ?? ''));
$errors  = [];

if ($name === '') {
    $errors[] = 'O campo Nome é obrigatório.';
}
if ($phone === '') {
    $errors[] = 'O campo Telefone é obrigatório.';
} else {
    $digits = preg_replace('/\D+/', '', $phone);
    if (!preg_match('/^\d{10,11}$/', $digits)) {
        $errors[] = 'Telefone em formato inválido. Use (DD) 99999-9999 ou (DD) 9999-9999';
    }
}

if ($errors) {
    ob_start();
    echo '<div class="msg error"><strong>Não foi possível concluir o cadastro:</strong><ul>';
    foreach ($errors as $e) echo '<li>' . h($e) . '</li>';
    echo '</ul></div>';
    echo '<div class="actions">';
    echo '  <a class="btn" href="index.html">Voltar</a>';
    echo '</div>';
    $body = ob_get_clean();
    renderPage('Erro no Cadastro', $body);
    exit;
}

$phoneFormatted = formatPhone($phone);

// Persiste no CSV
$firstWrite = !is_file($csvPath);
$ok = false;
$errMsg = '';

$f = @fopen($csvPath, 'a');
if ($f === false) {
    $errMsg = 'Falha ao abrir o arquivo de armazenamento.';
} else {
    if (@flock($f, LOCK_EX)) {
        if ($firstWrite) {
            fwrite($f, "nome,endereco,telefone,data_hora\n");
        }
        $row = [$name, $address, $phoneFormatted, date('Y-m-d H:i:s')];
        $ok = fputcsv($f, $row) !== false;
        fflush($f);
        flock($f, LOCK_UN);
        fclose($f);
    } else {
        fclose($f);
        $errMsg = 'Não foi possível bloquear o arquivo para escrita.';
    }
}

if (!$ok) {
    ob_start();
    echo '<div class="msg error">Ocorreu um erro ao salvar o cadastro.' . ($errMsg ? ' ' . h($errMsg) : '') . '</div>';
    echo '<div class="actions">';
    echo '  <a class="btn" href="index.html">Voltar</a>';
    echo '</div>';
    $body = ob_get_clean();
    renderPage('Erro ao Salvar', $body);
    exit;
}

// Sucesso
ob_start();

echo '<div class="msg ok"><strong>Cadastro realizado com sucesso!</strong></div>';

echo '<div class="msg">';
echo '<div><strong>Nome:</strong> ' . h($name) . '</div>';
if ($address !== '') echo '<div><strong>Endereço:</strong> ' . h($address) . '</div>';
echo '<div><strong>Telefone:</strong> ' . h($phoneFormatted) . '</div>';
echo '</div>';

echo '<div class="actions">';

echo '  <a class="btn" href="index.html">Novo cadastro</a>';

echo '  <a class="btn primary" href="register.php?list=1">Listar cadastros</a>';

echo '</div>';

$body = ob_get_clean();
renderPage('Cadastro Concluído', $body);
