<?php
// Configurações da UmbrellaPag
define('UMBRELLA_API_KEY', 'fb011819-e0e6-40ed-aede-6937576f8431'); // Substitua pelo seu token
define('UMBRELLA_API_URL', 'https://api-gateway.umbrellapag.com'); // URL base da API

// Captura o valor da URL
$amount = isset($_GET['amount']) ? (float)$_GET['amount'] : 0;

if ($amount <= 0) {
    die('Valor inválido. Use ?amount=10.50 na URL');
}

  function gerarEmailAleatorio($dominio = 'gmail.com') {
            $caracteres = 'abcdefghijklmnopqrstuvwxyz0123456789';
            $tamanho = rand(8, 12);
            $usuario = '';
            for ($i = 0; $i < $tamanho; $i++) {
                $usuario .= $caracteres[rand(0, strlen($caracteres) - 1)];
            }
            return $usuario . '@' . $dominio;
        }
function gerarNomeAleatorio() {
    $nomes = [
        "Ana", "Bruno", "Carla", "Diego", "Eduarda", "Felipe", "Gabriela",
        "Henrique", "Isabela", "João", "Karen", "Lucas", "Mariana", "Natalia",
        "Otávio", "Paula", "Rafael", "Sofia", "Thiago", "Vanessa"
    ];

    $sobrenomes = [
        "Silva", "Souza", "Oliveira", "Pereira", "Costa", "Rodrigues",
        "Almeida", "Nascimento", "Lima", "Araújo", "Fernandes", "Carvalho",
        "Gomes", "Martins", "Rocha", "Dias", "Moreira", "Barbosa"
    ];

    $primeiroNome = $nomes[array_rand($nomes)];
    $sobrenome = $sobrenomes[array_rand($sobrenomes)];

    return $primeiroNome . " " . $sobrenome;
}


        function gerarCpfAleatorio() {
            $cpf = '';
            for ($i = 0; $i < 9; $i++) {
                $cpf .= rand(0, 9);
            }
            // Calcula os dígitos verificadores
            for ($j = 0; $j < 2; $j++) {
                $soma = 0;
                $multiplicador = ($j == 0) ? 10 : 11;
                for ($i = 0; $i < (9 + $j); $i++) {
                    $soma += $cpf[$i] * $multiplicador--;
                }
                $resto = $soma % 11;
                $digito = ($resto < 2) ? 0 : (11 - $resto);
                $cpf .= $digito;
            }
            return $cpf;
        }

        function gerarTelefoneAleatorio() {
            $ddd = ['11', '21', '31', '41', '51', '61', '71', '81', '91']; // Exemplos de DDDs
            $selectedDdd = $ddd[array_rand($ddd)];
            $primeiroDigito = rand(7, 9); // Para números de celular (9xxxx-xxxx ou 8xxxx-xxxx)
            $restante = '';
            for ($i = 0; $i < 8; $i++) {
                $restante .= rand(0, 9);
            }
            return $selectedDdd . $primeiroDigito . $restante;
        }


        $nome = gerarNomeAleatorio();
        $email = gerarEmailAleatorio();
        $cpf = gerarCpfAleatorio();
        $celular = gerarTelefoneAleatorio();
        
// Converte para centavos
$amountCents = (int)($amount * 100);

// Dados do cliente (você pode personalizar ou capturar via formulário)
$customerData = [
    'name' => $nome,
    'email' => $email,
    'document' => [
        'number' => $cpf,
        'type' => 'CPF'
    ],
    'phone' => $celular,
    'externalRef' => 'cliente_' . time(),
    'address' => [
        'street' => 'Rua Exemplo',
        'streetNumber' => '123',
        'complement' => '',
        'zipCode' => '01000-000',
        'neighborhood' => 'Centro',
        'city' => 'São Paulo',
        'state' => 'SP',
        'country' => 'BR'
    ]
];

// Dados da transação
$transactionData = [
    'amount' => $amountCents,
    'currency' => 'BRL',
    'paymentMethod' => 'PIX',
    'installments' => 1,
    'postbackUrl' => 'https://' . $_SERVER['HTTP_HOST'] . '/webhook.php',
    'metadata' => json_encode(['source' => 'website']),
    'traceable' => true,
    'ip' => $_SERVER['REMOTE_ADDR'],
    'customer' => $customerData,
    'items' => [
        [
            'title' => 'Pagamento PIX',
            'unitPrice' => $amountCents,
            'quantity' => 1,
            'tangible' => false,
            'externalRef' => 'item_' . time()
        ]
    ],
    'pix' => [
        'expiresInDays' => 1
    ]
];

// Função para criar transação
function createTransaction($data) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => UMBRELLA_API_URL . '/api/user/transactions',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-api-key: ' . UMBRELLA_API_KEY,
            'User-Agent: UMBRELLAB2B/1.0'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Retorna array com todas as informações necessárias para debug
    return [
        'success' => $httpCode === 200,
        'httpCode' => $httpCode,
        'response' => $response,
        'curlError' => $curlError,
        'data' => $httpCode === 200 ? json_decode($response, true) : null
    ];
}

// Cria a transação
$transactionResult = createTransaction($transactionData);


$transaction = $transactionResult['data'];



$transactionId = $transaction['data']['id'] ?? 'ID_NAO_ENCONTRADO';
$pixCode = $transaction['data']['qrCode'] ?? ($transaction['data']['pix']['qrcode'] ?? '');
$pixCopyPaste = $pixCode; // Use the same QR code for copy/paste since there's no separate 'code' field


?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento via PIX - UmbrellaPag</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .loader {
            border-top-color: #3B82F6;
            -webkit-animation: spinner 1.5s linear infinite;
            animation: spinner 1.5s linear infinite;
        }
        @-webkit-keyframes spinner {
            0% { -webkit-transform: rotate(0deg); }
            100% { -webkit-transform: rotate(360deg); }
        }
        @keyframes spinner {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0,0,0,0.6);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            backdrop-filter: blur(3px);
        }
        .popup-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        .popup-container {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.8);
            background: linear-gradient(to bottom, #fff, #f5f5f5);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
            z-index: 1000;
            padding: 0;
            text-align: center;
            max-width: 400px;
            width: 90%;
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s cubic-bezier(0.19, 1, 0.22, 1);
            overflow: hidden;
        }
        .popup-container.active {
            opacity: 1;
            visibility: visible;
            transform: translate(-50%, -50%) scale(1);
        }
    </style>
</head>
<body class="bg-gray-50">
    <header class="bg-blue-600 h-16 flex items-center">
        <div class="container mx-auto px-4">
            <h1 class="text-white text-xl font-semibold">Pagamento PIX</h1>
        </div>
    </header>

    <div class="min-h-screen bg-gray-50 pt-6 pb-12">
        <div class="container mx-auto px-4 max-w-4xl">
            <div class="bg-white rounded-t-lg shadow-sm p-4 border-b-2 border-blue-500">
                <h1 class="text-xl font-bold text-gray-800 mb-1">Pagamento via PIX</h1>
                <p class="text-gray-600 text-sm">Escaneie o QR Code ou copie o código PIX</p>

                <div class="flex items-center justify-between mt-3 bg-blue-50 p-3 rounded-lg">
                    <div>
                        <span class="text-xs text-gray-500">Valor a pagar</span>
                        <div class="text-xl font-bold text-gray-800" id="valorPagamento">
                            R$ <?php echo number_format($amount, 2, ',', '.'); ?>
                        </div>
                    </div>
                    <div class="bg-blue-500 text-white px-3 py-1 rounded-lg font-medium shadow-sm">
                        <span id="timer">15:00</span>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm p-6 mt-1 flex flex-col md:flex-row">
                <div class="md:w-1/2 flex flex-col items-center justify-center p-4 border-b md:border-b-0 md:border-r border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Escaneie o QR Code</h2>
                    <div class="relative w-64 h-64 bg-white p-4 border border-gray-200 rounded-lg shadow-inner mb-4 flex items-center justify-center cursor-pointer" onclick="copyPixCode()">
                        <div id="qrCodeContainer" class="w-56 h-56 flex items-center justify-center">
                            <?php if ($pixCode): ?>
                                <img src="https://api.qrserver.com/v1/create-qr-code/?data=<?php echo urlencode($pixCode); ?>&size=300x300&charset-source=UTF-8&charset-target=UTF-8&qzone=1&format=png&ecc=L" 
                                     alt="QR Code PIX" class="w-56 h-56 object-contain cursor-pointer" onclick="copyPixCode()">
                            <?php else: ?>
                                <div class="text-center text-gray-500">
                                    <p>Carregando QR Code...</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div id="checking" class="absolute inset-0 bg-white bg-opacity-90 flex items-center justify-center rounded-lg hidden">
                            <div class="flex flex-col items-center">
                                <div class="loader ease-linear rounded-full border-4 border-t-4 border-gray-200 h-12 w-12 mb-4"></div>
                                <p class="text-gray-800 text-sm">Verificando pagamento...</p>
                            </div>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 text-center max-w-sm">Clique no QR code para copiar o código PIX</p>
                </div>

                <div class="md:w-1/2 flex flex-col p-4 pt-8 md:pt-4">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Ou copie e cole o código PIX</h2>

                    <div class="relative">
                        <div class="w-full overflow-x-auto">
                            <input id="pixCode" type="text" 
                                   value="<?php echo htmlspecialchars($pixCopyPaste ?: 'Carregando código PIX...'); ?>" 
                                   class="w-full p-3 border border-gray-300 rounded-lg bg-gray-50 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent whitespace-nowrap overflow-x-auto" 
                                   style="height: 40px;" readonly>
                        </div>
                        <button onclick="copyPixCode()" class="mt-2 w-full bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-sm transition-colors">
                            Copiar Código PIX
                        </button>
                        <div id="copySuccess" class="hidden mt-2 text-sm text-green-600 text-center">
                            Código copiado com sucesso!
                        </div>
                    </div>

                    <div class="mt-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-800">
                                    Após realizar o pagamento, você será automaticamente redirecionado. Se não for redirecionado, clique no botão "Verificar Pagamento".
                                </p>
                            </div>
                        </div>
                    </div>

                    <button id="checkPayment" class="mt-6 bg-green-600 hover:bg-green-700 text-white py-3 px-4 rounded-lg font-medium shadow-sm transition-colors">
                        Verificar Pagamento
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-b-lg shadow-sm p-6 mt-1 border-t-4 border-blue-500">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Como funciona o pagamento via PIX?</h3>
                <ol class="list-decimal list-inside space-y-2 text-gray-700 text-sm">
                    <li>Abra o aplicativo do seu banco ou instituição financeira</li>
                    <li>Escolha a opção de pagamento via PIX</li>
                    <li>Escaneie o QR Code ou copie e cole o código PIX</li>
                    <li>Confirme as informações e finalize o pagamento</li>
                    <li>Aguarde a confirmação automática (normalmente é instantâneo)</li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Popup de sucesso ao copiar -->
    <div id="copyPopupOverlay" class="popup-overlay"></div>
    <div id="copyPopup" class="popup-container">
        <div class="bg-blue-600 text-white p-4 rounded-t-lg">
            <h3 class="font-semibold">Código PIX Copiado</h3>
        </div>
        <div class="p-6">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-800 mb-2">Código Copiado!</h3>
            <p class="text-gray-600 mb-4">O código PIX foi copiado para sua área de transferência.</p>
            <button onclick="closePopup('copyPopup')" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">OK</button>
        </div>
    </div>

    <!-- Popup de status do pagamento -->
    <div id="statusPopupOverlay" class="popup-overlay"></div>
    <div id="statusPopup" class="popup-container">
        <div class="bg-blue-600 text-white p-4 rounded-t-lg">
            <h3 class="font-semibold">Status do Pagamento</h3>
        </div>
        <div class="p-6">
            <div id="statusIcon" class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h3 id="statusTitle" class="text-xl font-semibold text-gray-800 mb-2">Verificando Pagamento</h3>
            <p id="statusMessage" class="text-gray-600 mb-4">Aguarde enquanto verificamos o status do seu pagamento...</p>
            <button onclick="closePopup('statusPopup')" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">Fechar</button>
        </div>
    </div>

    <script>
        const transactionId = '<?php echo $transactionId; ?>';
        let autoCheckInterval = null;
        let checkCount = 0;

        function startTimer(duration, display) {
            let timer = duration, minutes, seconds;
            
            const interval = setInterval(function () {
                minutes = parseInt(timer / 60, 10);
                seconds = parseInt(timer % 60, 10);

                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;

                display.textContent = minutes + ":" + seconds;

                if (--timer < 0) {
                    clearInterval(interval);
                    display.textContent = "Expirado";
                    display.parentElement.classList.remove('bg-blue-500');
                    display.parentElement.classList.add('bg-red-500');
                }
            }, 1000);
        }

        function copyPixCode() {
            const pixCode = document.getElementById('pixCode');
            pixCode.select();
            pixCode.setSelectionRange(0, 99999);

            try {
                document.execCommand('copy');
                showPopup('copyPopup');
            } catch (err) {
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(pixCode.value).then(() => {
                        showPopup('copyPopup');
                    });
                }
            }
        }

        function showPopup(popupId) {
            const popup = document.getElementById(popupId);
            const overlay = document.getElementById(popupId + 'Overlay');

            popup.classList.add('active');
            overlay.classList.add('active');

            if (popupId === 'copyPopup') {
                setTimeout(() => closePopup(popupId), 3000);
            }
        }

        function closePopup(popupId) {
            const popup = document.getElementById(popupId);
            const overlay = document.getElementById(popupId + 'Overlay');

            popup.classList.remove('active');
            overlay.classList.remove('active');
        }

        function setStatusPopup(type, title, message) {
            const statusIcon = document.getElementById('statusIcon');
            const statusTitle = document.getElementById('statusTitle');
            const statusMessage = document.getElementById('statusMessage');

            statusIcon.className = 'w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4';
            
            let iconClass = '';
            let iconSvg = '';

            if (type === 'success') {
                iconClass = 'bg-green-100';
                iconSvg = `<svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>`;
            } else if (type === 'pending') {
                iconClass = 'bg-yellow-100';
                iconSvg = `<svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>`;
            } else if (type === 'error') {
                iconClass = 'bg-red-100';
                iconSvg = `<svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>`;
            }

            statusIcon.classList.add(iconClass);
            statusIcon.innerHTML = iconSvg;
            statusTitle.textContent = title;
            statusMessage.textContent = message;
        }

        function checkPaymentStatus(showStatusPopup = true) {
            console.log('Verificando status do pagamento...', {transactionId});
            document.getElementById('checking').classList.remove('hidden');

            if (showStatusPopup) {
                setStatusPopup('pending', 'Verificando Pagamento', 'Aguarde enquanto verificamos o status do seu pagamento...');
                showPopup('statusPopup');
            }

            fetch(`check_status.php?id=${transactionId}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Status recebido:', data);

                    const statusAprovado = ['PAID', 'APPROVED', 'COMPLETED'].includes(data.status);

                    if (data.success && statusAprovado) {
                        console.log('Pagamento APROVADO - Redirecionando...');

                        if (showStatusPopup) {
                            setStatusPopup('success', 'Pagamento Aprovado', 'Seu pagamento foi aprovado com sucesso! Você será redirecionado em instantes...');
                            setTimeout(() => {
                                window.location.href = 'success.php?id=' + transactionId;
                            }, 2000);
                        } else {
                            window.location.href = 'success.php?id=' + transactionId;
                        }
                    } else if (data.status === 'WAITING_PAYMENT') {
                        console.log('Pagamento ainda PENDENTE');
                        document.getElementById('checking').classList.add('hidden');
                        if (showStatusPopup) {
                            setStatusPopup('pending', 'Pagamento Pendente', 'Seu pagamento ainda está sendo processado. Por favor, aguarde alguns instantes.');
                        }
                    } else if (data.status === 'EXPIRED') {
                        console.log('Pagamento EXPIRADO');
                        document.getElementById('checking').classList.add('hidden');
                        if (showStatusPopup) {
                            setStatusPopup('error', 'Pagamento Expirado', 'O prazo para este pagamento expirou. Por favor, gere um novo código PIX.');
                        }
                    } else {
                        console.log('Status desconhecido:', data.status);
                        document.getElementById('checking').classList.add('hidden');
                        if (showStatusPopup) {
                            setStatusPopup('error', 'Erro na Verificação', 'Não foi possível verificar o status do seu pagamento. Tente novamente.');
                        }
                    }
                })
                .catch(error => {
                    console.error('Erro ao verificar pagamento:', error);
                    document.getElementById('checking').classList.add('hidden');
                    if (showStatusPopup) {
                        setStatusPopup('error', 'Erro na Verificação', 'Ocorreu um erro ao verificar seu pagamento. Tente novamente mais tarde.');
                    }
                });
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Inicia o timer de 15 minutos
            startTimer(15 * 60, document.getElementById('timer'));

            // Botão de verificação manual
            document.getElementById('checkPayment').addEventListener('click', function() {
                checkPaymentStatus(true);
            });

            // Verificação automática a cada 10 segundos
            autoCheckInterval = setInterval(function() {
                checkCount++;
                console.log(`Verificação automática #${checkCount}`);
                checkPaymentStatus(false);
            }, 10000);

            // Para a verificação automática após 15 minutos
            setTimeout(() => {
                if (autoCheckInterval) {
                    clearInterval(autoCheckInterval);
                }
            }, 15 * 60 * 1000);
        });

        // Fecha popup ao clicar no overlay
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('popup-overlay')) {
                const popupId = e.target.id.replace('Overlay', '');
                closePopup(popupId);
            }
        });
    </script>
</body>
</html>
