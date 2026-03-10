-- Adicionar suporte para Groq (alternativa gratuita)
INSERT INTO agent_config (config_key, config_value) VALUES 
('ai_provider', 'groq'), -- openai ou groq
('groq_apikey', 'SUA_CHAVE_GROQ_AQUI'),
('groq_model', 'llama3-70b-8192')
ON DUPLICATE KEY UPDATE config_value = VALUES(config_value);
