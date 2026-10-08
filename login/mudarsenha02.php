<?php
    class Auth {
        private $db;

        public function __construct($conn) {
            $this->db = $conn;
        }

        public function verificar($emailSubmit) {

            $sql = "SELECT email,nome FROM user WHERE email = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$emailSubmit]);

            $dados = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$dados) {
                return false;
            }
            else{
                return $dados;
            }
        }
    }
    function gerarCodigo($tamanho = 5) {
        $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $codigo = '';

        for ($i = 0; $i < $tamanho; $i++) {
            $codigo .= $caracteres[random_int(0, strlen($caracteres) - 1)];
        }

        return $codigo;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: mudarsenha.php');
        exit;
    }
    require_once 'cryp2graph2.php';
    require_once '../config.php';
    require_once '../email.php';
    session_start();

    $email = trim($_POST['emailSign'] ?? '');

    if ($email === '') {
        header("Location: login.php"); // deu algum erro brutal e volta pro login
        exit;
    }

    $conn = getDB();

    $auth = new Auth($conn);
    $user = $auth->verificar($email);

    if ($user) { // Tudo certo, continuar para mudarsenha03.php
        $_SESSION["permitido_reset"] = 0;
        $codigo = gerarCodigo();
        $mensagem = '<div style="max-width:600px; margin:40px auto; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 15px rgba(0,0,0,0.08);">
            <div style="background:#2563eb; padding:30px; text-align:center;">
                <h1 style="color:#ffffff; margin:0; font-size:26px;">
                    Alteração de senha
                </h1>
            </div>
            <div style="padding:40px 35px; color:#333333;">
                <h2 style="margin-top:0; color:#1f2937;">
                    Olá!
                </h2>
                <p style="font-size:16px; line-height:1.6;">
                    Recebemos uma solicitação para alterar a senha da sua conta.
                </p>
                <p style="font-size:16px; line-height:1.6;">
                    Para continuar, utilize o código de verificação abaixo:
                </p>
                <div style="text-align:center; margin:30px 0;">
                    <div style="
                        display:inline-block;
                        padding:18px 35px;
                        background:#f0f4ff;
                        border:2px dashed #2563eb;
                        border-radius:10px;
                        color:#2563eb;
                        font-size:32px;
                        font-weight:bold;
                        letter-spacing:8px;
                    ">
                        ' . htmlspecialchars($codigo) . '
                    </div>
                </div>
                <p style="font-size:14px; color:#6b7280; line-height:1.6;">
                    Este código é válido por alguns minutos. Por segurança,
                    não compartilhe este código com ninguém.
                </p>
                <p style="font-size:15px; line-height:1.6;">
                    Se você não solicitou a alteração de senha, pode ignorar
                    este e-mail. Sua senha atual continuará a mesma.
                </p>
            </div>

            <div style="background:#f9fafb; padding:25px 35px; text-align:center;">
                <p style="margin:0; color:#9ca3af; font-size:13px;">
                    Este é um e-mail automático. Por favor, não responda.
                </p>

                <p style="margin:8px 0 0; color:#9ca3af; font-size:12px;">
                    &copy; ' . date('Y') . ' - Todos os direitos reservados.
                </p>
            </div>
        </div>';
        mandarEmail($user['nome'],$user['email'],"Código de mudança de senha",$mensagem);
        $sql = "INSERT INTO userresetcode(email, codigo) VALUES (?,?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$email,$codigo]);
        $_SESSION['permitido_reset'] = true;
        $_SESSION['email_reset'] = $email;
        header("Location: mudarsenha03.php");
        exit;
    }
    else { // Não existe email no BD
        header("Location: mudarsenha.php?erro=1");
        exit;
    }
?>