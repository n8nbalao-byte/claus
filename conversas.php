<?php
$page_title = "Conversas com Clientes";
require_once 'header.php';
?>

<style>
    .conversations-layout { display: flex; height: calc(100vh - 200px); min-height: 500px; background: #f0f2f5; }
    .conversation-list-sidebar { width: 350px; background: white; border-right: 1px solid #e0e0e0; display: flex; flex-direction: column; }
    .conversation-list-header { padding: 16px 20px; border-bottom: 1px solid #e0e0e0; background: #f8f9fa; }
    .conversation-list-header h3 { margin: 0; font-size: 18px; color: #41525d; }
    .conversation-list { flex: 1; overflow-y: auto; list-style: none; padding: 0; margin: 0; }
    .conversation-item { display: flex; padding: 12px 20px; cursor: pointer; transition: background-color 0.2s; border-bottom: 1px solid #f0f0f0; }
    .conversation-item:hover, .conversation-item.active { background-color: #f5f5f5; }
    .conversation-item .avatar { width: 49px; height: 49px; background: #ddd; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #666; margin-right: 15px; flex-shrink: 0; }
    .conversation-item .details { flex: 1; overflow: hidden; }
    .conversation-item .details .number { font-weight: 600; font-size: 16px; color: #111; margin-bottom: 2px; }
    .conversation-item .details .last-message { white-space: nowrap; text-overflow: ellipsis; overflow: hidden; color: #666; font-size: 14px; }
    .conversation-item .timestamp { font-size: 12px; color: #999; margin-left: 10px; align-self: flex-start; }

    .chat-view { flex: 1; display: flex; flex-direction: column; background: #e5ddd5; background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text fill="%23f0f0f0" font-size="20" y="50%">💬</text></svg>'); }
    .chat-view-header { padding: 16px 20px; background: #f0f2f5; border-bottom: 1px solid #e0e0e0; display: flex; align-items: center; }
    .chat-view-header .avatar { width: 40px; height: 40px; background: #ddd; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #666; margin-right: 15px; }
    .chat-view-header .contact-info { flex: 1; }
    .chat-view-header .contact-name { font-weight: 600; font-size: 16px; color: #111; margin: 0; }
    .chat-view-header .contact-status { font-size: 12px; color: #666; margin: 0; }
    .chat-view-messages { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 8px; background: #e5ddd5; }
    .chat-view-placeholder { display: flex; align-items: center; justify-content: center; height: 100%; color: #999; font-size: 18px; }

    .chat-input-area { padding: 12px 20px; background: #f0f2f5; border-top: 1px solid #e0e0e0; display: flex; gap: 8px; align-items: flex-end; }
    .chat-input-area .input-container { flex: 1; background: white; border-radius: 20px; display: flex; align-items: flex-end; padding: 8px 12px; border: 1px solid #ddd; }
    .chat-input-area textarea { flex: 1; border: none; outline: none; resize: none; min-height: 20px; max-height: 100px; font-family: inherit; font-size: 14px; line-height: 1.4; background: transparent; }
    .chat-input-area .send-btn { width: 40px; height: 40px; background: #25d366; color: white; border: none; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 18px; }
    .chat-input-area .send-btn:hover { background: #20c157; }

    /* Estilos de mensagem */
    .message { max-width: 65%; padding: 8px 12px; border-radius: 8px; position: relative; word-wrap: break-word; font-size: 14px; line-height: 1.4; }
    .message.user { align-self: flex-start; background: white; color: #111; border-bottom-left-radius: 2px; margin-right: auto; }
    .message.agent { align-self: flex-end; background: #dcf8c6; color: #111; border-bottom-right-radius: 2px; margin-left: auto; }
    .message.admin_manual { align-self: flex-end; background: #fff3cd; color: #111; border-bottom-right-radius: 2px; margin-left: auto; }
    .message-info { display: block; font-size: 11px; color: #999; margin-top: 4px; text-align: right; }
    .message.user .message-info { text-align: left; }
</style>

<div class="card" style="padding: 0; overflow: hidden;">
    <div class="conversations-layout">
        <div class="conversation-list-sidebar">
            <div class="conversation-list-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0;">Conversas</h3>
                    <button onclick="clearAllChats()" style="background-color: #d32f2f; color: white; border: none; padding: 6px 10px; border-radius: 4px; font-size: 12px; cursor: pointer;">🗑 Limpar</button>
                </div>
            </div>
            <ul id="conversation-list" class="conversation-list"></ul>
        </div>
        <div class="chat-view">
            <div id="chat-view-header" class="chat-view-header" style="display: none;">
                <div class="avatar">👤</div>
                <div class="contact-info">
                    <p class="contact-name" id="contact-name"></p>
                    <p class="contact-status">Online</p>
                </div>
            </div>
            <div id="chat-view-messages" class="chat-view-messages">
                <div class="chat-view-placeholder">Selecione uma conversa para ver as mensagens.</div>
            </div>
            <div id="chat-input-container" class="chat-input-area" style="display: none;">
                <div class="input-container">
                    <textarea id="manual-chat-input" placeholder="Digite uma mensagem..." onkeydown="handleManualKey(event)"></textarea>
                </div>
                <button class="send-btn" onclick="sendManualMessage()">➤</button>
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
            const initial = convo.sender_number.substring(0, 2).toUpperCase();
            const time = new Date(convo.timestamp).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            const lastMessage = (convo.sender_role === 'agent' ? 'Você: ' : (convo.sender_role === 'admin_manual' ? 'Admin: ' : '')) + convo.message;
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
        
        // Atualizar cabeçalho
        document.getElementById('contact-name').textContent = number;
        header.style.display = 'flex';

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

                // Garantir assinatura *Claus:* no início
                let msgText = log.message;
                if (role === 'agent' && !/^\*Claus:\*/i.test(msgText)) {
                    msgText = '*Claus:* ' + msgText;
                }
                // Transforma a assinatura em negrito (ex: *Claus:*)
                const formattedMessage = msgText.replace(/\*([^\*]+):\*/g, '<strong>$1:</strong>');

                messagesContainer.innerHTML += `
                    <div class="message ${messageClass}">
                        ${formattedMessage}
                        <span class="message-info">${time}</span>
                    </div>
                `;
            });
            
            // Sempre rolar para o final
            scrollToBottom();

            // Mostrar a caixa de envio
            document.getElementById('chat-input-container').style.display = 'flex';
            document.getElementById('manual-chat-input').dataset.number = number;
            document.getElementById('manual-chat-input').focus();
    }

    function scrollToBottom() {
        const messagesContainer = document.getElementById('chat-view-messages');
        setTimeout(() => {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }, 100);
    }

    async function sendManualMessage() {
        const input = document.getElementById('manual-chat-input');
        const button = document.querySelector('.send-btn');
        const message = input.value.trim();
        const number = input.dataset.number;

        if (!message || !number) return;

        button.disabled = true;
        button.textContent = '⏳';

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
            button.disabled = false;
            button.textContent = '➤';
            input.focus();
        }
    }

    async function clearAllChats() {
        if (!confirm('⚠️ Isto vai deletar TODOS os chats. Tem certeza?')) return;
        if (!confirm('Essa ação é irreversível. Confirmar novamente?')) return;
        const res = await fetch('api.php?action=clear_all_chats');
        const data = await res.json();
        alert(data.status === 'success' ? `${data.deleted} mensagens deletadas.` : 'Falha ao limpar.');
        loadConversations();
        currentSelectedNumber = null;
        document.getElementById('chat-view-messages').innerHTML = '<div class="chat-view-placeholder">Selecione uma conversa para ver as mensagens.</div>';
        document.getElementById('chat-input-container').style.display = 'none';
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
