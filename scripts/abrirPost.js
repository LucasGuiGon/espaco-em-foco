
document.addEventListener("DOMContentLoaded", () => {
    const modalPost = document.getElementById("modalPost");

    if (!modalPost) return;

    // Campos do modal de post
    const modalId = modalPost.querySelector("#modal-id");
    const modalUsuario = modalPost.querySelector("#modal-post");
    const modalTopic = modalPost.querySelector("#modal-topic");
    const modalTitulo = modalPost.querySelector("#modal-titulo");
    const modalDesc = modalPost.querySelector("#modal-desc");

    // Botões do modal de post
    const fecharModal = modalPost.querySelector("#fecharModal");
    const recusarPost = document.getElementById("recusarPost");
    const resolverPost = document.getElementById("resolverPost");

    // Abrir o modal ao clicar em Analisar
    document.querySelectorAll(".analisar-post").forEach((botao) => {
        botao.addEventListener("click", () => {
            // Preencher os dados do post selecionado
            modalId.textContent = botao.dataset.id;
            modalUsuario.textContent = botao.dataset.usuario;
            modalTopic.textContent = botao.dataset.topic;
            modalTitulo.textContent = botao.dataset.titulo;
            modalDesc.textContent = botao.dataset.desc;

            // Guardar o ID do post atualmente selecionado
            modalPost.dataset.postId = botao.dataset.id;

            // Abrir o modal
            modalPost.classList.add("aberto");
        });
    });

    // Fechar pelo botão X
    fecharModal?.addEventListener("click", () => {
        modalPost.classList.remove("aberto");
    });

    // Fechar clicando fora do conteúdo
    modalPost.addEventListener("click", (event) => {
        if (event.target === modalPost) {
            modalPost.classList.remove("aberto");
        }
    });

    // Fechar com a tecla ESC
    document.addEventListener("keydown", (event) => {
        if (
            event.key === "Escape" &&
            modalPost.classList.contains("aberto")
        ) {
            modalPost.classList.remove("aberto");
        }
    });

    
    
    async function atualizarStatusPost(acao) {
        const id = modalPost.dataset.postId;

        if (!id) {
            alert("Nenhum post selecionado.");
            return;
        }

        const botao = acao === "aceitar" ? resolverPost : recusarPost;
        const textoOriginal = botao.textContent;

        botao.disabled = true;
        botao.textContent = "Processando...";

        try {
            const resposta = await fetch("./atualizar-status-post.php", {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ id, acao })
            });

            const texto = await resposta.text();

            console.log("Status HTTP:", resposta.status);
            console.log("Resposta do PHP:", texto);

            let dados;

            try {
                dados = JSON.parse(texto);
            } catch {
                throw new Error(
                    "O servidor não retornou JSON válido. Confira o Console (F12)."
                );
            }

            if (!resposta.ok || !dados.sucesso) {
                throw new Error(
                    dados.mensagem || "Não foi possível atualizar o post."
                );
            }

            // Remove o post analisado da página
            const card = document.querySelector(
                `.analisar-post[data-id="${CSS.escape(String(id))}"]`
            )?.closest(".post");

            card?.remove();

            // Fecha o modal
            modalPost.classList.remove("aberto");

            alert(dados.mensagem);

            // Informa se não restarem posts
            const containerPosts = document.getElementById("posts");

            if (containerPosts && !containerPosts.querySelector(".post")) {
                containerPosts.innerHTML = "<p>Nenhum post em análise.</p>";
            }

        } catch (erro) {
            console.error("Erro ao atualizar post:", erro);
            alert(erro.message || "Erro de comunicação com o servidor.");
        } finally {
            botao.disabled = false;
            botao.textContent = textoOriginal;
        }
    }

    resolverPost.addEventListener("click", () => {
        atualizarStatusPost("aceitar");
    });

    recusarPost.addEventListener("click", () => {
        atualizarStatusPost("recusar");
    });


});
