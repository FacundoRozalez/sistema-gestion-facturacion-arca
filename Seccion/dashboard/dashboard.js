document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('ventasMensuales');
    const ctx = canvas.getContext('2d');
    const ventas = JSON.parse(canvas.dataset.ventas);

    const width = canvas.width;
    const height = canvas.height;
    const padding = 40;

    // Asegurarse que todos los totales sean números
    ventas.forEach(v => v.total = Number(v.total) || 0);

    // Calcular máximos
    const maxVentas = Math.max(...ventas.map(v => v.total), 1);

    const n = ventas.length;
    const availableWidth = width - 2 * padding;
    const barSpacing = n > 1 ? availableWidth / n : availableWidth;
    const maxBarWidth = 50;
    const barWidth = Math.min(barSpacing * 0.6, maxBarWidth);

    // Limpiar canvas
    ctx.clearRect(0, 0, width, height);

    // Fuente
    ctx.font = '12px Arial';
    ctx.textBaseline = 'middle';

    // Ejes
    ctx.beginPath();
    ctx.moveTo(padding, padding);
    ctx.lineTo(padding, height - padding);
    ctx.lineTo(width - padding, height - padding);
    ctx.strokeStyle = '#333';
    ctx.lineWidth = 2;
    ctx.stroke();

    // Dibujar barras
    ventas.forEach((v, i) => {
        const total = v.total;
        const barHeight = ((height - 2 * padding) * total) / maxVentas;
        const x = padding + i * barSpacing + (barSpacing - barWidth) / 2;
        const y = height - padding - barHeight;

        // Gradiente
        const grad = ctx.createLinearGradient(x, y, x, height - padding);
        grad.addColorStop(0, '#2ecc71');
        grad.addColorStop(1, '#27ae60');

        ctx.fillStyle = grad;

        // Barra con bordes redondeados
        const radius = 5;
        ctx.beginPath();
        ctx.moveTo(x, height - padding);
        ctx.lineTo(x, y + radius);
        ctx.quadraticCurveTo(x, y, x + radius, y);
        ctx.lineTo(x + barWidth - radius, y);
        ctx.quadraticCurveTo(x + barWidth, y, x + barWidth, y + radius);
        ctx.lineTo(x + barWidth, height - padding);
        ctx.closePath();
        ctx.fill();

        // Texto encima de la barra
        ctx.fillStyle = '#000';
        ctx.textAlign = 'center';
        ctx.fillText(total.toFixed(2), x + barWidth / 2, y - 10);

        // Mes debajo de la barra
        ctx.fillText(v.mes, x + barWidth / 2, height - padding + 15);
    });
});
