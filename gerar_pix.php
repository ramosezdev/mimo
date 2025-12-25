<?php

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (isset($_POST['valor'])) {
    $valorReais = floatval($_POST['valor']);

    if ($valorReais < 0.50) {
        echo json_encode(["status" => "erro", "mensagem" => "Valor mínimo é R$0,50."]);
        exit;
    }

    $valorCentavos = intval($valorReais * 100);

    $apiUrl = 'https://api.pushinpay.com.br/api/pix/cashIn';
    $token = '58115|3TH8jF0kAz1ma2naImotr9IEdUR6I96SV5nhvqAv694df693';

    $data = [
        "value" => $valorCentavos
    ];

    $ch = curl_init($apiUrl);
    
    if (!$ch) {
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao inicializar cURL."]);
        exit;
    }
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Accept: application/json",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        echo json_encode([
            "status" => "erro", 
            "mensagem" => "Erro de conexão: " . ($curlError ?: "Desconhecido")
        ]);
        exit;
    }

    if ($httpCode === 200) {
        $resposta = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode([
                "status" => "erro", 
                "mensagem" => "Resposta da API inválida.",
                "debug" => substr($response, 0, 200)
            ]);
            exit;
        }
        
        if (isset($resposta['qr_code']) && isset($resposta['qr_code_base64'])) {
            echo json_encode([
                "status" => "ok",
                "qrcode" => $resposta['qr_code'],
                "qrcode_base64" => $resposta['qr_code_base64']
            ]);
        } else {
            echo json_encode([
                "status" => "erro", 
                "mensagem" => "API retornou dados incompletos.",
                "debug" => array_keys($resposta)
            ]);
        }
    } else {
        $mensagemErro = "Erro ao gerar o PIX. Código: $httpCode";
        
        $resposta = json_decode($response, true);
        if ($resposta && isset($resposta['message'])) {
            $mensagemErro .= " - " . $resposta['message'];
        }
        
        echo json_encode([
            "status" => "erro", 
            "mensagem" => $mensagemErro,
            "debug" => substr($response, 0, 200)
        ]);
    }
} else {
    echo json_encode(["status" => "erro", "mensagem" => "Parâmetro inválido."]);
}
