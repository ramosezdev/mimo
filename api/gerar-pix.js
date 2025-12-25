export default async function handler(req, res) {
    // Configurar CORS
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

    // Tratar OPTIONS (preflight)
    if (req.method === 'OPTIONS') {
        return res.status(200).end();
    }

    if (req.method !== 'POST') {
        return res.status(405).json({ 
            status: 'erro', 
            mensagem: 'Método não permitido' 
        });
    }

    try {
        const { valor, mensagem } = req.body;

        if (!valor) {
            return res.status(400).json({ 
                status: 'erro', 
                mensagem: 'Parâmetro inválido.' 
            });
        }

        const valorReais = parseFloat(valor);

        if (valorReais < 0.50) {
            return res.status(400).json({ 
                status: 'erro', 
                mensagem: 'Valor mínimo é R$0,50.' 
            });
        }

        const valorCentavos = Math.floor(valorReais * 100);

        const apiUrl = 'https://api.pushinpay.com.br/api/pix/cashIn';
        const token = '58115|3TH8jF0kAz1ma2naImotr9IEdUR6I96SV5nhvqAv694df693';

        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                value: valorCentavos
            })
        });

        const httpCode = response.status;
        const data = await response.json();

        if (httpCode === 200) {
            if (data.qr_code && data.qr_code_base64) {
                return res.status(200).json({
                    status: 'ok',
                    qrcode: data.qr_code,
                    qrcode_base64: data.qr_code_base64
                });
            } else {
                return res.status(500).json({
                    status: 'erro',
                    mensagem: 'API retornou dados incompletos.',
                    debug: Object.keys(data)
                });
            }
        } else {
            let mensagemErro = `Erro ao gerar o PIX. Código: ${httpCode}`;
            
            if (data.message) {
                mensagemErro += ` - ${data.message}`;
            }
            
            return res.status(httpCode).json({
                status: 'erro',
                mensagem: mensagemErro,
                debug: data
            });
        }

    } catch (error) {
        console.error('Erro:', error);
        return res.status(500).json({
            status: 'erro',
            mensagem: 'Erro ao processar requisição: ' + error.message
        });
    }
}
