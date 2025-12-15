# VeriFactu VTS API (Prototipo)

Sistema de Facturación Antifraude (VeriFactu) desarrollado en PHP nativo utilizando una arquitectura **MVC** (Modelo-Vista-Controlador). Implementa un libro registro inmutable basado en encadenamiento de hashes (Blockchain-like) y firmas digitales.

## 🚀 Características

### Backend (API)
*   **Arquitectura MVC:** Separación clara entre Modelos (`models/`), Vistas (`frontend/`) y Controladores (`controllers/`).
*   **Inmutabilidad:** Cada factura contiene el hash de la anterior, garantizando la integridad de la cadena.
*   **Seguridad:**
    *   Protección contra CSRF (Cross-Site Request Forgery).
    *   Validación estricta de inputs (Regex para NIF, tipos de datos).
    *   Sanitización automática contra XSS.
*   **Configuración:** Gestión de secretos mediante archivo `.env`.

### Frontend (Interfaz Web)
*   **Dashboard:** Visualización en tiempo real del libro registro.
*   **Gestión:** Creación de facturas Ordinarias y Rectificativas.
*   **Exportación:**
    *   Descarga de datos firmados en JSON.
    *   Impresión profesional a PDF mediante estilos CSS dedicados.
*   **Validación Visual:** Feedback inmediato en formularios (ej. validación NIF).

## 🛠️ Instalación y Uso

1.  **Requisitos:** PHP 7.4 o superior y extensión SQLite3.
2.  **Configuración:**
    Copia el archivo de ejemplo y configura tus claves:
    ```bash
    copy .env.example .env
    ```
3.  **Ejecutar Servidor:**
    Inicia el servidor de desarrollo de PHP en la raíz del proyecto:
    ```bash
    php -S 0.0.0.0:8001
    ```
4.  **Acceso:**
    *   **Web:** Abre [http://localhost:8001/frontend/index.php](http://localhost:8001/frontend/index.php)
    *   **API:** Endpoint base en `http://localhost:8001/index.php`

## 📚 Endpoints API

| Método | Acción | Descripción |
| :--- | :--- | :--- |
| `GET` | `list` | Lista todas las facturas registradas. |
| `GET` | `get` | Obtiene el detalle de una factura (`?id=X`). |
| `POST` | `register` | Registra una nueva factura (JSON body + CSRF). |
| `GET` | `download` | Descarga el JSON firmado de una factura. |

## 🏗️ Estructura del Proyecto

```
/config         # Conexión DB y Carga de entorno (.env)
/controllers    # Lógica de control y seguridad
/models         # Acceso a datos (SQLite)
/services       # Lógica de negocio (Hashing, Cadena)
/utils          # Validadores y Seguridad
/frontend       # Interfaz de usuario (PHP/HTML/CSS)
/tests          # Tests de integración y unitarios
index.php       # Router principal (Front Controller)
```
