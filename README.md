# Hooma Legal

Plugin para la gestión integral, centralizada e inalterable de textos legales y consentimiento de usuarios en WordPress.

## Características principales

- **Datos de empresa globales**: Centraliza los datos del titular de la web (razón social, NIF/CIF, dirección, contacto, etc.) y los inserta automáticamente en los documentos mediante marcadores dinámicos como `{{company_name}}` o `{{email}}`.
- **Versionado automático inteligente**: Detecta cambios en las variables globales. Cuando un cambio afecta al contenido de uno o varios documentos legales, el plugin genera automáticamente una nueva versión de los documentos afectados.
- **Control de versiones manual**: Permite crear nuevas versiones de los documentos legales de forma manual cuando sea necesario, incluso si no se han producido cambios automáticos.
- **Historial de versiones e instantáneas**: Guarda una copia estática de cada documento legal en formato HTML (con variables y bloques ya renderizados) cada vez que se crea una nueva versión.
- **API de consentimiento de usuarios**: Registro de auditoría inalterable que asocia cada aceptación de usuario con una versión exacta del documento legal. Incluye soporte para campos personalizados (`extra_data`) en formato JSON.
- **Integraciones nativas**: Integración con FAZ Cookie Manager para la sincronización y el versionado automático de documentos, y con Contact Form 7 para vincular el consentimiento del usuario a la versión exacta de los documentos legales aceptados.

---

## Configuración y uso

### 1. Ajustes globales

Accede a **Ajustes Globales** bajo el menú de Documentos Legales para configurar:

- Nombre de la empresa, nombre comercial, NIF/CIF y dirección.
- Información de contacto (correo y teléfono).
- Datos del Delegado de Protección de Datos (DPO) y jurisdicción judicial.

### 2. Marcadores dinámicos disponibles

- `{{company_name}}`: Nombre o razón social.
- `{{brand_name}}`: Nombre comercial.
- `{{vat_type}}`: Tipo de identificador fiscal (NIF, CIF, DNI, etc.).
- `{{vat}}`: Número de identificador fiscal.
- `{{address}}`: Dirección física completa.
- `{{postal_code}}`: Código postal.
- `{{city}}`: Ciudad.
- `{{province}}`: Provincia.
- `{{country}}`: País.
- `{{email}}`: Correo de contacto.
- `{{phone}}`: Teléfono de contacto.
- `{{website}}`: URL del sitio web.
- `{{dpo}}`: Nombre del DPO.
- `{{data_controller}}`: Responsable del tratamiento.
- `{{jurisdiction}}`: Jurisdicción legal.
- `{{court}}`: Tribunales de competencia.

---

## API de consentimiento (para desarrolladores)

### 1. Función global en PHP

Para registrar el consentimiento desde plugins, pasarelas de pago o ganchos de formularios personalizados:

```php
hooma_legal_log_consent( array(
    'document'         => 'politica-de-privacidad',   // Slug o ID del post (posttype: hooma_legal_doc)
    'identifier_type'  => 'email',            // Tipo de identificador (email, dni, user_id, session_id)
    'identifier_value' => 'usuario@correo.com',
    'source'           => 'contact_form_7',   // Origen obligatorio del envío (WooCommerce, CF7, etc.)
    'user_name'        => 'Juan Pérez',
    'phone'            => '+34 600 000 000',
    'extra_data'       => array(
        'subject'      => 'Contacto comercial',
        'accepted_box' => 'Aceptado'
    )
) );
```

### 2. REST API (POST)

El endpoint REST permite realizar peticiones asíncronas desde front‑ends o aplicaciones externas:

- **Ruta**: `/wp-json/hooma-legal/v1/consent`
- **Método**: `POST`
- **Cabecera**: `Content-Type: application/json`
- **Cuerpo (JSON)**:

```json
{
  "document": "politica-de-privacidad",
  "identifier_type": "dni",
  "identifier_value": "12345678X",
  "source": "registro_ajax",
  "nombre": "Juan",
  "apellidos": "Pérez"
}
```

---

## Requisitos de base de datos

El plugin genera y mantiene dos tablas personalizadas con prefijo de WordPress:

- `wp_hooma_legal_versions`: Almacena el título, contenido estático renderizado, versión, autor y fecha de creación de cada instantánea.
- `wp_hooma_legal_consent_logs`: Almacena las aceptaciones vinculadas a la versión exacta (`version_id`), con datos del usuario, IP, origen y metadatos complementarios.
