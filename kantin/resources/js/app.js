//
import QRCode from 'qrcode';

document.querySelectorAll('[data-qr]').forEach((container) => {
    const canvas = document.createElement('canvas');

    QRCode.toCanvas(canvas, container.dataset.qr, {
        width: 220,
        margin: 2,
        errorCorrectionLevel: 'M',
        color: {
            dark: '#194733',
            light: '#ffffff',
        },
    })
        .then(() => container.replaceChildren(canvas))
        .catch((error) => {
            console.error('Gagal membuat QR menu.', error);
            container.textContent = 'QR tidak dapat ditampilkan. Gunakan URL kantin.';
        });
});

document.querySelectorAll('.copy-qr-url').forEach((button) => {
    button.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(button.dataset.url);
            button.textContent = 'URL tersalin';
        } catch (error) {
            console.error('Gagal menyalin URL QR.', error);
            button.textContent = 'Salin manual URL di atas';
        }
    });
});
