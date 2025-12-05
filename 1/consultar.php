<?php
header('Content-Type: application/json');

if (!isset($_GET['cpf'])) {
    echo json_encode(["status" => 400, "erro" => "CPF não enviado"]);
    exit;
}

$cpf = preg_replace('/[^0-9]/', '', $_GET['cpf']);

if (strlen($cpf) != 11) {
    echo json_encode(["status" => 400, "erro" => "CPF inválido"]);
    exit;
}

$url = "https://fluxos.kodexpert.com.br/webhook/e3358323-f6eb-42e5-8a54-7513d794b2c4/kodexpert/cpf/$cpf";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $response) {
    $data = json_decode($response, true);

    // Retorno é OBJETO
    if (is_array($data) && !empty($data) && isset($data['NOME'])) {
        echo json_encode([
            "status" => 200,
            "cpf" => $cpf, // Adicionamos manualmente porque não vem do webhook
            "nome" => $data['NOME'] ?? '',
            "nascimento" => $data['NASC'] ?? '',
            "sexo" => $data['SEXO'] ?? '',
            "nome_mae" => $data['NOME_MAE'] ?? ''
        ]);
    } else {
        echo json_encode(["status" => 404, "erro" => "Dados não encontrados para este CPF"]);
    }
} else {
    echo json_encode(["status" => $httpCode, "erro" => "Erro na comunicação com a API externa"]);
}
