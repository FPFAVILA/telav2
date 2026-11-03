<?php
$senha_clara = "admin123";
// Gera o hash seguro usando o algoritmo bcrypt
$hash_segura = password_hash($senha_clara, PASSWORD_DEFAULT);

echo "A senha em texto claro é: " . $senha_clara . "<br>";
echo "O HASH SEGURO para COPIAR é: <span style='font-weight: bold; color: #FE2C55; font-size: 1.1em; word-break: break-all;'>" . $hash_segura . "</span><br><br>";
echo "Copie a string em destaque acima e cole-a no campo 'password_hash' do seu banco de dados.";
?>