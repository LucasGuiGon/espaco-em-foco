<?php

function verificarUsuario($email)
{
    require_once __DIR__ . '/../config.php';
    try {
        $pdo = getDB();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare('SELECT codTypeRoles FROM userroles WHERE emailRoles = :email');
        $stmt->execute(['email' => $email]);
        $userRole = $stmt->fetch();
        return $userRole;
    } catch (PDOException $e) {
        echo 'Erro: ' . $e->getMessage();
    }
}
require_once __DIR__ . '/../config.php';
try {
    $pdo = getDB();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare('SELECT data_fim FROM `banimentos` WHERE email = :email');
    $stmt->execute(['email' => $_SESSION['user']]);
    $userBan = $stmt->fetch();
    if($userBan){
        if (!session_start()) { session_start(); }
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        if ($userBan['tipo_banimento'] === 'permanente') {
            header("Location: login.php?ban=permanente");
        } else {
            header(
                "Location: login.php?ban=" .
                urlencode($userBan['data_fim'])
            );
        }
        exit;
    }
} catch (PDOException $e) {
    echo 'Erro: ' . $e->getMessage();
}
