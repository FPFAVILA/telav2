<?php
// Arquivo: process_address.php
// Objetivo: Receber os dados de endereço via POST (AJAX) e salvá-los na sessão do PHP.

// 1. Inicia a sessão
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Define o cabeçalho para retornar uma resposta JSON
header('Content-Type: application/json');

// 1a. Verifica se a sessão foi verificada pelo filtro de dispositivo
if (empty($_SESSION['_device_verified'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado.']);
    exit;
}

// 2. Verifica se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Capturando e limpando os dados. Note que usamos os nomes (name="") definidos no HTML.
    $address_data = [
        'contact_name'      => filter_input(INPUT_POST, 'contactName', FILTER_SANITIZE_STRING),
        'contact_phone'     => filter_input(INPUT_POST, 'contactPhone', FILTER_SANITIZE_NUMBER_INT),
        'contact_email'     => filter_input(INPUT_POST, 'contactEmail', FILTER_SANITIZE_EMAIL),
        'cep'               => filter_input(INPUT_POST, 'cepInput', FILTER_SANITIZE_STRING),
        'uf'                => filter_input(INPUT_POST, 'ufInput', FILTER_SANITIZE_STRING),
        'city'              => filter_input(INPUT_POST, 'cityInput', FILTER_SANITIZE_STRING),
        'neighborhood'      => filter_input(INPUT_POST, 'neighborhoodInput', FILTER_SANITIZE_STRING),
        'street'            => filter_input(INPUT_POST, 'streetInput', FILTER_SANITIZE_STRING),
        'number'            => filter_input(INPUT_POST, 'address_number', FILTER_SANITIZE_STRING),
        'complement'        => filter_input(INPUT_POST, 'address_complement', FILTER_SANITIZE_STRING),
        // O checkbox envia 'on' se marcado; verificamos se existe
        'is_default'        => isset($_POST['defaultToggle']) ? true : false,
    ];

    // Validação básica (verificando se campos cruciais estão preenchidos)
    if (empty($address_data['contact_name']) || empty($address_data['contact_phone']) || empty($address_data['cep'])) {
         echo json_encode(['success' => false, 'message' => 'Campos obrigatórios estão vazios.']);
         exit;
    }

    // 3. Salva os dados de endereço na sessão
    // Usamos um array para o endereço atual (pode ser expandido para salvar múltiplos)
    $_SESSION['current_address'] = $address_data;

    // 4. Retorna sucesso
    echo json_encode(['success' => true, 'message' => 'Endereço salvo com sucesso.']);
    exit;

}

// Retorna erro se o método não for POST
echo json_encode(['success' => false, 'message' => 'Requisição inválida.']);
?>