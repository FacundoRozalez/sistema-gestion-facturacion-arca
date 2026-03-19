#!/bin/bash
#==============================================================================
# Script para actualizar la cadena de certificados de AFIP
#==============================================================================

echo "=== ACTUALIZANDO CADENA DE CERTIFICADOS AFIP ==="

# Backup de la cadena actual
if [ -f "cadena-completa.pem" ]; then
    cp cadena-completa.pem cadena-completa.pem.backup
    echo "✓ Backup creado: cadena-completa.pem.backup"
fi

# Descargar certificado actual de AFIP
echo "Descargando certificado actual de AFIP..."
echo | openssl s_client -connect wsaa.afip.gov.ar:443 -showcerts 2>/dev/null | \
openssl x509 -outform PEM > afip_server_cert.pem

if [ -s "afip_server_cert.pem" ]; then
    echo "✓ Certificado del servidor AFIP descargado"
else
    echo "✗ Error descargando certificado del servidor"
    exit 1
fi

# Descargar cadena completa
echo "Descargando cadena completa..."
echo | openssl s_client -connect wsaa.afip.gov.ar:443 -showcerts 2>/dev/null | \
sed -ne '/-BEGIN CERTIFICATE-/,/-END CERTIFICATE-/p' > cadena_nueva.pem

if [ -s "cadena_nueva.pem" ]; then
    echo "✓ Cadena completa descargada"
    
    # Contar certificados
    CERT_COUNT=$(grep -c "BEGIN CERTIFICATE" cadena_nueva.pem)
    echo "✓ Nueva cadena contiene $CERT_COUNT certificados"
    
    # Reemplazar cadena actual
    mv cadena_nueva.pem cadena-completa.pem
    echo "✓ Cadena de certificados actualizada"
    
    # Verificar la nueva cadena
    echo "Verificando nueva cadena..."
    openssl verify -CAfile cadena-completa.pem afip_server_cert.pem
    
else
    echo "✗ Error descargando cadena completa"
    exit 1
fi

# Limpiar archivos temporales
rm -f afip_server_cert.pem

echo "=== ACTUALIZACIÓN COMPLETADA ==="
echo "Para revertir en caso de problemas:"
echo "mv cadena-completa.pem.backup cadena-completa.pem"