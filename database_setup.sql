-- Tabela para armazenar a configuração principal do agente (o prompt)
CREATE TABLE agent_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(255) UNIQUE NOT NULL,
    config_value TEXT NOT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inserir o prompt inicial. Nós vamos editá-lo pelo painel depois.
INSERT INTO agent_config (config_key, config_value) VALUES
('main_prompt', 'Você é Claus, um assistente de IA autônomo. Sua missão é gerenciar comunicações e executar tarefas via WhatsApp. Você opera em dois modos: Admin e User. Siga as regras estritamente.');

-- Tabela para armazenar os números de telefone dos administradores
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_number VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255),
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inserir seu número como o primeiro administrador
INSERT INTO admin_users (phone_number, name) VALUES ('+5519981470446', 'Admin Principal');

-- Tabela para a memória do agente (contatos)
CREATE TABLE contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone_number VARCHAR(50) UNIQUE,
    relationship VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela para registrar as atividades do agente (logs)
CREATE TABLE agent_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sender_number VARCHAR(50),
    sender_role VARCHAR(20),
    message TEXT,
    agent_action VARCHAR(255),
    status VARCHAR(50)
);
