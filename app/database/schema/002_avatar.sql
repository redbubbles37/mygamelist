-- Imagem de cada usuário na tela de entrada: arquivo enviado ("uploads/avatars/xxx.webp") ou ícone pronto ("preset:🎮").
ALTER TABLE users ADD COLUMN avatar TEXT NULL;
