<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit;
}
require_once __DIR__ . '/../login/verify-user.php';
$userroles = verificarUsuario($_SESSION['user']);
if ($userroles['codTypeRoles'] == 0) {
    header("Location: ../userScreen/home-user.php");
    exit;
}

require_once '../config.php';
try {
    $pdo = getDB();
    $sqlBusca = "SELECT * FROM denuncias WHERE status='Em Analise'";
    $stmtBusca = $pdo->prepare($sqlBusca);
    $stmtBusca->execute();

    $denuncias = $stmtBusca->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $_SESSION['erro'] = $e->getMessage();
}

try {
    $pdo = getDB();

    // 1. Buscar posts em análise
    $sqlBusca = "
        SELECT *
        FROM postagens_forum
        WHERE avaliacao_adm = 'Em Analise'
    ";

    $stmtBusca = $pdo->prepare($sqlBusca);
    $stmtBusca->execute();

    $posts = $stmtBusca->fetchAll(PDO::FETCH_ASSOC);

    // 2. Buscar imagens dos posts encontrados
    if (!empty($posts)) {
        $ids = array_column($posts, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sqlImgs = "
            SELECT *
            FROM imgs_post
            WHERE id_post IN ($placeholders)
        ";

        $stmtImgs = $pdo->prepare($sqlImgs);
        $stmtImgs->execute($ids);

        $imagens = $stmtImgs->fetchAll(PDO::FETCH_ASSOC);

        // 3. Organizar as imagens por ID do post
        $imgsPorPost = [];

        foreach ($imagens as $img) {
            $imgsPorPost[$img['id_post']][] = $img;
        }

        // 4. Adicionar as imagens dentro de cada post
        foreach ($posts as &$post) {
            $post['imagens'] = $imgsPorPost[$post['id']] ?? [];
        }

        unset($post);
    }

} catch (Exception $e) {
    $_SESSION['erro'] = $e->getMessage();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style.css" />
    <link rel="stylesheet" href="styleadm.css" />
    <script src="../scripts/abrirDenuncia.js" type="module" defer></script>
    <script src="../scripts/abrirPost.js" defer></script>
    <title>Document</title>
</head>

<body>
    <header id="main-header">
        <?php include __DIR__ . "/../navBar.php"; ?>
    </header>
    <div id="buttonCima">
        <li>
            <a href="AdicionaCard/adicionaCard.php" class="button"><span>Tópicos</span></a>
        </li>
    </div>
    <div id="denuncias">
        <?php if (empty($denuncias)): ?>

            <p>Nenhuma denúncia encontrada.</p>

        <?php else: ?>

            <?php foreach ($denuncias as $denuncia): ?>

                <div class="denuncia">
                    <h2>Denúncia #<?= htmlspecialchars($denuncia['id']) ?></h2>

                    <p>
                        <strong>Denunciado:</strong>
                        <?= htmlspecialchars($denuncia['nome_usuario_alvo']) ?>
                    </p>

                    <p>
                        <strong>Categoria:</strong>
                        <?= htmlspecialchars($denuncia['categoria_denuncia']) ?>
                    </p>

                    <p>
                        <strong>Motivo:</strong>
                        <?= htmlspecialchars($denuncia['motivo']) ?>
                    </p>

                    <p>
                        <strong>Status:</strong>
                        <?= htmlspecialchars($denuncia['status']) ?>
                    </p>
                    <button 
                        class="button analisar-denuncia"
                        data-id="<?= htmlspecialchars($denuncia['id']) ?>">
                        Analisar
                    </button>
                </div>
                <br>
            <?php endforeach; ?>

        <?php endif; ?>
    </div>

    <div id="modalDenuncia" class="modal">
        <div class="modal-conteudo">
            <div class="modal-header">
                <h2>Analisar denúncia #<span id="modal-id"></span></h2>
                <button id="fecharModal" class="fechar-modal">
                    &times;
                </button>
            </div>

            <div class="modal-body">
                <div class="info-denuncia">
                    <div class="campo">
                        <strong>Denunciante</strong>
                        <p id="modal-denunciante"></p>
                    </div>

                    <div class="campo">
                        <strong>Usuário denunciado</strong>
                        <p id="modal-denunciado"></p>
                    </div>

                    <div class="campo">
                        <strong>Categoria</strong>
                        <p id="modal-categoria"></p>
                    </div>

                    <div class="campo">
                        <strong>Motivo</strong>
                        <p id="modal-motivo"></p>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button id="recusarDenuncia" class="btn-recusar">
                    Recusar denúncia
                </button>

                <button id="resolverDenuncia" class="btn-resolver">
                    Resolver denúncia
                </button>
            </div>
            <div id="modalResolucao" class="modal">
                <div class="modal-conteudo">

                    <div class="modal-header">
                        <h2>Resolver denúncia</h2>

                        <button id="fecharResolucao" class="fechar-modal">
                            &times;
                        </button>
                    </div>

                    <div class="modal-body">

                        <p>Escolha o que deseja fazer com o usuário denunciado:</p>

                        <div class="opcoes-resolucao">

                            <label class="opcao-resolucao">
                                <input 
                                    type="radio" 
                                    name="acaoResolucao" 
                                    value="aviso_email"
                                >

                                <div>
                                    <strong>✉️ Enviar aviso por e-mail</strong>
                                    <p>
                                        Envia um aviso ao usuário sobre a denúncia.
                                    </p>
                                </div>
                            </label>


                            <label class="opcao-resolucao">
                                <input 
                                    type="radio" 
                                    name="acaoResolucao" 
                                    value="banir_7_dias"
                                >

                                <div>
                                    <strong>🔨 Banir por 7 dias</strong>
                                    <p>
                                        O usuário ficará impedido de acessar a conta por 7 dias.
                                    </p>
                                </div>
                            </label>


                            <label class="opcao-resolucao">
                                <input 
                                    type="radio" 
                                    name="acaoResolucao" 
                                    value="banir_permanente"
                                >

                                <div>
                                    <strong>🔨 Banir permanentemente</strong>
                                    <p>
                                        O usuário perderá permanentemente o acesso à conta.
                                    </p>
                                </div>
                            </label>

                        </div>


                        <div class="campo">
                            <strong>Observação do administrador</strong>
                            <textarea 
                                id="observacaoResolucao"
                                placeholder="Explique o motivo da decisão..."
                                rows="5"
                            ></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button id="cancelarResolucao" class="btn-recusar">
                            Cancelar
                        </button>
                        <button id="confirmarResolucao" class="btn-resolver">
                            Confirmar resolução
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="posts">
        <?php if (empty($posts)): ?>
            <p>Nenhum post encontrada.</p>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div class="post">
                    <h2>Post #<?= htmlspecialchars($post['id']) ?></h2>

                    <p>
                        <strong>Usuário:</strong>
                        <?= htmlspecialchars($post['nome_usuario_post']) ?>
                    </p>

                    <p>
                        <strong>Título:</strong>
                        <?= htmlspecialchars($post['titulo_post']) ?>
                    </p>

                    <p>
                        <strong>Tópico:</strong>
                        <?= htmlspecialchars($post['topic_post']) ?>
                    </p>

                    <p>
                        <strong>Descrição:</strong>
                        <?= htmlspecialchars($post['desc_post']) ?>
                    </p>
                    <button
                        type="button"
                        class="button analisar-post"
                        data-id="<?= htmlspecialchars($post['id'], ENT_QUOTES, 'UTF-8') ?>"
                        data-usuario="<?= htmlspecialchars($post['nome_usuario_post'], ENT_QUOTES, 'UTF-8') ?>"
                        data-topic="<?= htmlspecialchars($post['topic_post'], ENT_QUOTES, 'UTF-8') ?>"
                        data-titulo="<?= htmlspecialchars($post['titulo_post'], ENT_QUOTES, 'UTF-8') ?>"
                        data-desc="<?= htmlspecialchars($post['desc_post'], ENT_QUOTES, 'UTF-8') ?>"
                    >
                        Analisar
                    </button>
                </div>
                <br>
            <?php endforeach; ?>

        <?php endif; ?>
    </div>
    <div id="modalPost" class="modal">
        <div class="modal-conteudo">
            <div class="modal-header">
                <h2>Analisar post #<span id="modal-id"></span></h2>
                <button id="fecharModal" class="fechar-modal">
                    &times;
                </button>
            </div>

            <div class="modal-body">
                <div class="info-post">
                    <div class="campo">
                        <strong>Usuário</strong>
                        <p id="modal-post"></p>
                    </div>

                    <div class="campo">
                        <strong>Tópico</strong>
                        <p id="modal-topic"></p>
                    </div>

                    <div class="campo">
                        <strong>Título</strong>
                        <p id="modal-titulo"></p>
                    </div>

                    <div class="campo">
                        <strong>Descrição</strong>
                        <p id="modal-desc"></p>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button id="recusarPost" class="btn-recusar">
                    Recusar post
                </button>

                <button id="resolverPost" class="btn-resolver">
                    Resolver post
                </button>
            </div>
        </div>
    </div>
</body>

</html>