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
            <div class="input-container">
                <textarea id="chat-input" placeholder="Digite uma instrução ou mensagem para o Claus... (Enter para enviar, Shift+Enter para nova linha)" onkeydown="handleChatKey(event)"></textarea>
                <button id="voice-btn" onclick="toggleVoiceRecording()" title="Falar instrução (reconhecimento automático)" style="background: none; border: none; color: var(--text-secondary); font-size: 18px; cursor: pointer; margin-right: 8px;">🎤</button>
            </div>
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

    // Variáveis para gravação de áudio e síntese de voz
    let mediaRecorder = null;
    let audioChunks = [];
    let isRecording = false;
    let recognition = null;
    let isListening = false;

    async function toggleVoiceRecording() {
        const voiceBtn = document.getElementById('voice-btn');
        
        if (isRecording) {
            // Parar gravação
            mediaRecorder.stop();
            voiceBtn.innerHTML = '🎤';
            voiceBtn.classList.remove('recording');
            voiceBtn.title = 'Gravar áudio';
            isRecording = false;
        } else {
            // Verificar se há suporte à Speech Recognition
            if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
                startSpeechRecognition();
            } else {
                // Fallback para gravação de áudio
                startAudioRecording();
            }
        }
    }

    function startSpeechRecognition() {
        const voiceBtn = document.getElementById('voice-btn');
        
        // Inicializar reconhecimento de voz
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        recognition = new SpeechRecognition();
        
        recognition.lang = 'pt-BR'; // Português do Brasil
        recognition.continuous = false;
        recognition.interimResults = false;
        recognition.maxAlternatives = 1;
        
        recognition.onstart = function() {
            isListening = true;
            voiceBtn.innerHTML = '🎙️';
            voiceBtn.classList.add('recording');
            voiceBtn.title = 'Ouvindo... Clique para parar';
            console.log('Reconhecimento de voz iniciado');
        };
        
        recognition.onresult = function(event) {
            const transcript = event.results[0][0].transcript;
            console.log('Transcrição:', transcript);
            
            // Inserir texto no campo de entrada
            const input = document.getElementById('chat-input');
            input.value = transcript;
            
            // Enviar automaticamente
            sendMessage();
        };
        
        recognition.onerror = function(event) {
            console.error('Erro no reconhecimento de voz:', event.error);
            alert('Erro no reconhecimento de voz: ' + event.error);
            stopSpeechRecognition();
        };
        
        recognition.onend = function() {
            stopSpeechRecognition();
        };
        
        try {
            recognition.start();
        } catch (error) {
            console.error('Erro ao iniciar reconhecimento:', error);
            alert('Erro ao iniciar reconhecimento de voz');
            stopSpeechRecognition();
        }
    }

    function stopSpeechRecognition() {
        if (recognition && isListening) {
            recognition.stop();
        }
        isListening = false;
        const voiceBtn = document.getElementById('voice-btn');
        voiceBtn.innerHTML = '🎤';
        voiceBtn.classList.remove('recording');
        voiceBtn.title = 'Falar instrução';
    }

    async function startAudioRecording() {
        const voiceBtn = document.getElementById('voice-btn');
        
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            mediaRecorder = new MediaRecorder(stream);
            audioChunks = [];
            
            mediaRecorder.ondataavailable = (event) => {
                audioChunks.push(event.data);
            };
            
            mediaRecorder.onstop = async () => {
                const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
                await sendAudioMessage(audioBlob);
                
                // Parar todos os tracks do stream
                stream.getTracks().forEach(track => track.stop());
            };
            
            mediaRecorder.start();
            voiceBtn.innerHTML = '⏹️';
            voiceBtn.classList.add('recording');
            voiceBtn.title = 'Parar gravação';
            isRecording = true;
            
        } catch (error) {
            console.error('Erro ao acessar microfone:', error);
            alert('Erro ao acessar microfone. Verifique as permissões.');
        }
    }

    async function sendAudioMessage(audioBlob) {
        const formData = new FormData();
        formData.append('audio', audioBlob, 'audio.wav');
        
        try {
            const response = await fetch('api.php?action=send_voice_message', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            if (result.status === 'success') {
                await loadChat();
                scrollToBottom();
            } else {
                alert('Erro ao enviar áudio: ' + result.message);
            }
        } catch (error) {
            console.error('Erro ao enviar áudio:', error);
            alert('Erro ao enviar áudio para o agente.');
        }
    }

    // Função para síntese de voz (falar respostas)
    function speakText(text) {
        if ('speechSynthesis' in window) {
            // Cancelar qualquer fala anterior
            speechSynthesis.cancel();
            
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'pt-BR'; // Português do Brasil
            utterance.rate = 0.9; // Velocidade um pouco mais lenta
            utterance.pitch = 1; // Tom normal
            
            // Tentar usar uma voz em português se disponível
            const voices = speechSynthesis.getVoices();
            const portugueseVoice = voices.find(voice => voice.lang.startsWith('pt'));
            if (portugueseVoice) {
                utterance.voice = portugueseVoice;
            }
            
            speechSynthesis.speak(utterance);
        } else {
            console.warn('Síntese de voz não suportada neste navegador');
        }
    }

    // Modificar loadChat para adicionar botão de ouvir nas mensagens do agente
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

            // Adicionar botão de ouvir para mensagens do agente
            let speakButton = '';
            if (isAgent && 'speechSynthesis' in window) {
                speakButton = `<button onclick="speakText('${formattedMessage.replace(/'/g, "\\'").replace(/"/g, '\\"')}')" class="speak-btn" title="Ouvir resposta">🔊</button>`;
            }
            
            chatMessages.innerHTML += `
                <div class="message ${isAgent ? 'agent' : 'admin'}">
                    <div class="message-content">
                        ${formattedMessage}
                        ${speakButton}
                    </div>
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
</script>

<?php require 'footer.php'; ?>
