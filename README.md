# sistema-gestion-facturacion-arca

# Sistema de Gestión y Facturación (ARCA/AFIP) — Full Stack

Plataforma comercial integral diseñada para la gestión de inventarios, control de ventas y automatización de facturación electrónica mediante la integración con los web services de ARCA (ex-AFIP).

## 🚀 Funcionalidades Principales

*   **Gestión de Stock:** Control de inventario en tiempo real con alertas de bajo stock.
*   **Módulo de Ventas:** Registro de transacciones comerciales con interfaz intuitiva.
*   **Facturación Electrónica:** Integración completa con la API de ARCA para la validación de datos fiscales.
*   **Códigos QR Dinámicos:** Generación automática de códigos QR para facturas según normativa vigente (PHPQRCode).
*   **Generación de Comprobantes:** Creación automática de facturas en formato PDF listas para enviar al cliente.

## 🛠️ Stack Tecnológico

*   **Backend:** PHP (Lógica de negocio y conexión API).
*   **Base de Datos:** MySQL (Gestión relacional de productos, ventas y usuarios).
*   **Frontend:** HTML5, CSS3, Bootstrap (Interfaz responsive y profesional).
*   **Librerías:** **PHPQRCode** (Generación de QR), FPDF/TCPDF (para los PDF) y cURL para integración con Web Services.

## 📦 Instalación y Configuración

1. **Clonar el repositorio:**
   ```bash
   git clone https://github.com


### ⚠️ Actualización de Rutas (Refactorización)
Si el proyecto contenía rutas fijas (ejemplo: `/cristianferreteria/`), se deben actualizar a la nueva estructura del proyecto:
*   **Nueva URL Local:** `http://localhost/sistema-gestion-facturacion-arca/`
*   **Directorio raíz:** Asegurarse de que las inclusiones de PHP (`include` o `require`) apunten a la carpeta `/Panel/` actual.

## 📞 Contacto y Soporte

Si estás interesado en implementar este sistema o querés conocer más detalles técnicos sobre el **consumo de los Web Services de ARCA (ex-AFIP)**, no dudes en contactarme. 

Estaré encantado de explicarte:
*   El proceso de autenticación (WSAA) y generación del Token (TRA).
*   La lógica de envío de comprobantes (WSFEX/WSFEV1).
*   La gestión de certificados `.crt` y `.key` en entornos de producción.