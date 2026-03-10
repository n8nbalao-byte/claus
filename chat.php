<?php
$page_title = "Chat com o Agente";
require_once 'header.php';
?>

<div id="chat" style="flex: 1; display: flex; flex-direction: column;">
    <div class="chat-container">
        <div style="padding: 10px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: flex-end;">
            <button onclick="clearChatScreen()" style="background: none; border: 1px solid var(--border-color); color: var(--text-secondary); font-size: 12px; padding: 5px 10px;">Limpar Tela</button>
        </div>
        <div id="chat-messages" class="chat-messages">
            <div style="text-align: center; color: var(--text-secondary); margin-top: 20px;">Carregando conversa...</div>
        </div>
        <div class="chat-input-area">
            <textarea id="chat-input" placeholder="Digite uma instrução ou mensagem para o Claus... (Enter para enviar, Shift+Enter para nova linha)" onkeydown="handleChatKey(event)"></textarea>
            <button onclick="sendMessage()">Enviar</button>
        </div>
    </div>
</div>

<script>
    function handleChatKey(event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            sendMessage();
        }
    }

    async function loadChat() {
        const logs = await fetchData('api.php?action=get_admin_chat&limit=50');
        const chatMessages = document.getElementById('chat-messages');
        
        // Guardar a posição atual e verificar se o usuário está no fundo
        const isAtBottom = chatMessages.scrollHeight - chatMessages.scrollTop <= chatMessages.clientHeight + 50;
        
        // Filtrar apenas admin e agent e inverter para ordem cronológica
        const chatLogs = logs.filter(log => log.sender_role === 'admin' || log.sender_role === 'agent').reverse();
        
        chatMessages.innerHTML = '';
        chatLogs.forEach(log => {
            const isAgent = log.sender_role === 'agent';
            const time = new Date(log.timestamp).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            
            // Renderizar negritos formatados pela IA (*Texto:*)
            const formattedMessage = log.message.replace(/\*([^\*]+):\*/g, '<strong>$1:</strong>');

            chatMessages.innerHTML += `
                <div class="message ${isAgent ? 'agent' : 'admin'}">
                    ${formattedMessage}
                    <span class="message-info">${isAgent ? 'Claus' : 'Você'} • ${time}</span>
                </div>
            `;
        });

        // Forçar a rolagem se for a primeira carga ou se o usuário já estava no fundo
        if (!chatMessages.hasAttribute('data-initial-load') || isAtBottom) {
            scrollToBottom();
            chatMessages.setAttribute('data-initial-load', 'true');
        }
    }

    function scrollToBottom() {
        const chatMessages = document.getElementById('chat-messages');
        if (!chatMessages) return;
        
        // Rolar imediatamente para o final absoluto
        chatMessages.scrollTop = chatMessages.scrollHeight;
        
        // Reforçar após um pequeno tempo (necessário em alguns navegadores)
        setTimeout(() => {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }, 100);
    }

    function clearChatScreen() {
        document.getElementById('chat-messages').innerHTML = '<div style="text-align: center; color: var(--text-secondary); padding: 20px;">A tela foi limpa. O histórico permanece salvo.</div>';
    }

    async function sendMessage() {
        const input = document.getElementById('chat-input');
        const button = document.querySelector('.chat-input-area button');
        const message = input.value.trim();
        if (!message) return;
        
        const originalButtonText = button.innerText;
        input.value = '';
        input.disabled = true;
        button.disabled = true;
        button.innerText = '...';
        
        try {
            const res = await postData('api.php?action=send_chat_message', { message });
            if (res.status === 'success') {
                // Forçar recarga imediata e rolagem
                await loadChat();
                scrollToBottom();
            } else {
                alert('Erro: ' + res.message);
                input.value = message; 
            }
        } catch (e) {
            alert('Erro ao enviar mensagem para o agente.');
            input.value = message;
        } finally {
            input.disabled = false;
            button.disabled = false;
            button.innerText = originalButtonText;
            input.focus();
        }
    }

    window.addEventListener('load', () => {
        loadChat();
        setInterval(loadChat, 5000);
    });
</script>

<?php require 'footer.php'; ?>
