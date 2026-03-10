<?php
$page_title = "Conversas com Clientes";
require_once 'header.php';
?>

<style>
    .conversations-layout { display: flex; height: calc(100vh - 120px); }
    .conversation-list-sidebar { width: 300px; border-right: 1px solid var(--border-color); overflow-y: auto; }
    .conversation-list { list-style: none; padding: 0; margin: 0; }
    .conversation-item { display: flex; padding: 15px; cursor: pointer; transition: background-color 0.2s; border-bottom: 1px solid var(--border-color); }
    .conversation-item:hover, .conversation-item.active { background-color: var(--bg-color); }
    .conversation-item .avatar { width: 40px; height: 40px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 15px; flex-shrink: 0; }
    .conversation-item .details { flex: 1; overflow: hidden; }
    .conversation-item .details .number { font-weight: 600; }
    .conversation-item .details .last-message { white-space: nowrap; text-overflow: ellipsis; overflow: hidden; color: var(--text-secondary); font-size: 14px; }
    .conversation-item .timestamp { font-size: 12px; color: var(--text-secondary); margin-left: 10px; }

    .chat-view { flex: 1; display: flex; flex-direction: column; }
    .chat-view-header { padding: 15px 20px; border-bottom: 1px solid var(--border-color); font-weight: 600; }
    .chat-view-messages { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 10px; }
    .chat-view-placeholder { display: flex; align-items: center; justify-content: center; height: 100%; color: var(--text-secondary); }

    /* Estilos de mensagem para as conversas */
    .message.admin_manual { align-self: flex-end; background: var(--primary); color: white; border-bottom-right-radius: 2px; }
    .message.agent { align-self: flex-end; background: var(--card-bg); color: var(--text-primary); border: 1px solid var(--border-color); border-bottom-right-radius: 2px; }
    .message.user { align-self: flex-start; background: #e0e0e0; color: #333; border: 1px solid #ccc; border-bottom-left-radius: 2px; }
</style>

<div class="card" style="padding: 0; overflow: hidden;">
    <div class="conversations-layout">
        <div class="conversation-list-sidebar">
            <ul id="conversation-list" class="conversation-list"></ul>
        </div>
        <div class="chat-view">
            <div id="chat-view-header" class="chat-view-header" style="display: none;"></div>
            <div id="chat-view-messages" class="chat-view-messages">
                <div class="chat-view-placeholder">Selecione uma conversa para ver as mensagens.</div>
            </div>
            <div id="chat-input-container" class="chat-input-area" style="display: none;">
                <textarea id="manual-chat-input" placeholder="Escreva uma mensagem para intervir na conversa... (Enter para enviar, Shift+Enter para nova linha)" onkeydown="handleManualKey(event)"></textarea>
                <button onclick="sendManualMessage()">Enviar Mensagem</button>
            </div>
        </div>
    </div>
</div>

<script>
    function handleManualKey(event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            sendManualMessage();
        }
    }
    let currentSelectedNumber = null;

    async function loadConversations() {
        const conversations = await fetchData('api.php?action=get_conversations');
        const list = document.getElementById('conversation-list');
        list.innerHTML = '';

        if (conversations.length === 0) {
            list.innerHTML = '<li style="padding: 20px; text-align: center; color: var(--text-secondary);">Nenhuma conversa encontrada.</li>';
            return;
        }

        conversations.forEach(convo => {
            const initial = convo.sender_number.substring(0, 2);
            const time = new Date(convo.timestamp).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            const lastMessage = (convo.sender_role === 'agent' ? 'Agente: ' : (convo.sender_role === 'admin_manual' ? 'Admin: ' : 'Cliente: ')) + convo.message;
            const activeClass = currentSelectedNumber === convo.sender_number ? 'active' : '';

            list.innerHTML += `
                <li class="conversation-item ${activeClass}" onclick="selectConversation('${convo.sender_number}')">
                    <div class="avatar">${initial}</div>
                    <div class="details">
                        <div class="number">${convo.sender_number}</div>
                        <div class="last-message">${lastMessage}</div>
                    </div>
                    <div class="timestamp">${time}</div>
                </li>
            `;
        });
    }

    function selectConversation(number) {
        currentSelectedNumber = number;
        loadConversationHistory(number);
        // Atualizar visual da lista
        document.querySelectorAll('.conversation-item').forEach(item => {
            if (item.querySelector('.number').innerText === number) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    }

    async function loadConversationHistory(number) {
        if (!number) return;
        
        const header = document.getElementById('chat-view-header');
        const messagesContainer = document.getElementById('chat-view-messages');
        
        header.innerText = `Conversa com ${number}`;
        header.style.display = 'block';

        const history = await fetchData(`api.php?action=get_conversation_history&number=${number}`);
        messagesContainer.innerHTML = '';

        history.forEach(log => {
            const role = log.sender_role;
            const time = new Date(log.timestamp).toLocaleString('pt-BR');
            let messageClass = 'user';
            let senderName = 'Cliente';

            if (role === 'agent') {
                messageClass = 'agent';
                senderName = 'Claus';
            } else if (role === 'admin_manual') {
                messageClass = 'admin_manual';
                senderName = 'Admin';
            }

            // Transforma a assinatura em negrito (ex: *Claus:*)
            const formattedMessage = log.message.replace(/\*([^\*]+):\*/g, '<strong>$1:</strong>');

            messagesContainer.innerHTML += `
                <div class="message ${messageClass}">
                    ${formattedMessage}
                    <span class="message-info">${senderName} • ${time}</span>
                </div>
            `;
        });
        
        // Sempre rolar para o final
        scrollToBottom();

        // Mostrar a caixa de envio
        document.getElementById('chat-input-container').style.display = 'flex';
        document.getElementById('manual-chat-input').dataset.number = number;
    }

    function scrollToBottom() {
        const messagesContainer = document.getElementById('chat-view-messages');
        // Rolar imediatamente
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        
        // Rolar novamente após um pequeno delay
        setTimeout(() => {
            messagesContainer.scrollTo({
                top: messagesContainer.scrollHeight,
                behavior: 'smooth'
            });
        }, 100);

        // Garantir que role se houver imagens
        const images = messagesContainer.querySelectorAll('img');
        images.forEach(img => {
            if (!img.complete) {
                img.addEventListener('load', () => {
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                });
            }
        });
    }

    async function sendManualMessage() {
        const input = document.getElementById('manual-chat-input');
        const message = input.value.trim();
        const number = input.dataset.number;

        if (!message || !number) return;

        input.disabled = true;

        try {
            const res = await postData('api.php?action=send_manual_message', { number, message });
            if (res.status === 'success') {
                input.value = '';
                // Recarregar o histórico para mostrar a nova mensagem
                loadConversationHistory(number);
            } else {
                alert('Erro ao enviar: ' + res.message);
            }
        } catch (e) {
            alert('Falha na comunicação com a API.');
        } finally {
            input.disabled = false;
            input.focus();
        }
    }

    window.addEventListener('load', () => {
        loadConversations();
        setInterval(() => {
            loadConversations();
            if (currentSelectedNumber) {
                loadConversationHistory(currentSelectedNumber);
            }
        }, 15000); // Atualiza a cada 15 segundos
    });
</script>

<?php require_once 'footer.php'; ?>
