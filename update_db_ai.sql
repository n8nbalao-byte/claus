-- Atualização para suportar configurações de IA no banco de dados
-- Insira ou atualize as chaves padrão se não existirem

INSERT IGNORE INTO agent_config (config_key, config_value) VALUES 
('openai_apikey', ''),
('openai_model', 'gpt-4o-mini');
