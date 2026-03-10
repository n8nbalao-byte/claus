-- Atualizar modelo Groq padrão (o antigo foi descontinuado)
INSERT INTO agent_config (config_key, config_value) VALUES 
('groq_model', 'llama-3.3-70b-versatile')
ON DUPLICATE KEY UPDATE config_value = VALUES(config_value);
