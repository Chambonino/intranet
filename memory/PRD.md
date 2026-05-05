# PRD — Intranet Corporativa PHP/MySQL

## 📌 Original Problem Statement
Intranet corporativa en PHP + MySQL para Windows Server con XAMPP. Tema oscuro. Archivos cargados localmente (no URLs). Módulos:
- Página principal con slider de noticias
- Calendario de eventos
- Cumpleaños
- Cuadrícula de 12 aplicaciones más usadas
- Archivos por departamento
- Slider de fotos y videos
- Artículos
- Nuestra compañía
- Portales de clientes
- Cuenta regresiva
- Avisos
- Dashboard de administración completo

**Adiciones posteriores:**
- Directorio telefónico desde Active Directory (LDAP)
- Lista de Materiales (BOM) desde SQL Server
- Constructor de organigramas drag & drop ilimitados con guardado por departamento, foto, puesto, exportación a imagen y vista pública

## 🛠️ Tech Stack
- **Backend**: PHP 7/8 + MySQL (PDO)
- **Frontend**: Vanilla JS + CSS3 (tema oscuro)
- **Entorno**: Windows Server + XAMPP (Apache + PHP + MySQL)
- **Integraciones**: php_ldap (Active Directory), php_sqlsrv (MS SQL Server), html2canvas, Summernote
- **Entrega**: Carpeta `/app/intranet_php/` empaquetada como `/app/intranet_corporativa_php.zip` para descargar y desplegar en XAMPP

## 📂 Architecture
```
/app/intranet_php/
├── admin/                # Panel admin (login, CRUD de cada módulo)
├── api/                  # endpoints (upload_image.php, get_files.php)
├── assets/               # css, js, img, uploads
├── includes/             # config.php, functions.php
├── index.php             # Home pública
├── directorio.php        # Directorio LDAP
├── bom.php               # BOM SQL Server
├── calendario.php        # Calendario público
├── noticias.php          # Listado paginado
├── organigramas.php      # NUEVO - Visor público de organigramas drag & drop
├── database.sql          # Esquema base
├── update_db_v4.sql      # Tablas: organigrama_nodos + organigramas_custom
└── install.php           # Seed de admin
```

## ✅ What's been implemented (CHANGELOG)

### 2026-04 (Sesión inicial)
- Scaffolding + login + tema oscuro
- Slider de noticias, calendario, cumpleaños, cuadrícula de apps
- Galería fotos/videos, artículos con Summernote, avisos, cuenta regresiva
- Subida local de archivos (sin URLs externas)
- Dashboard admin completo

### 2026-04 a 2026-05
- Directorio LDAP/Active Directory (`directorio.php`) con vCard, QR, búsqueda, filtro `(&(Company=...)(sn=*)(Useraccountcontrol=512))`
- BOM con `php_sqlsrv` y exportación Excel (`bom.php`)
- Eventos con modal y archivos adjuntos
- Header con imagen personalizada de máquinas de inyección

### 2026-05-05 (Esta sesión - Organigramas Drag & Drop)
- ✅ `update_db_v4.sql` corregido: ahora crea las 2 tablas (`organigrama_nodos` para vista jerárquica + `organigramas_custom` para drag & drop)
- ✅ `admin/organigrama_drag.php`: editor drag & drop completo
  - Múltiples organigramas por departamento
  - Modal para nodos (nombre, puesto, color, foto)
  - Conexiones SVG padre-hijo
  - **Zoom con Ctrl + rueda del ratón** (botones +/− y reset 100%)
  - **Auto-layout jerárquico** (botón "Auto-organizar") que distribuye nodos según parent
  - Exportar a PNG (html2canvas)
- ✅ `admin/organigrama_builder.php`: versión jerárquica clásica padre-hijo (alternativa)
- ✅ `admin/organigrama.php`: versión imagen estática (mantenida)
- ✅ `organigramas.php` (NUEVA): página pública de solo lectura
  - Lista todos los organigramas activos como tarjetas
  - Visor con zoom, exportación a PNG
  - Enlace en cuadrícula de apps de `index.php`
- ✅ Sidebars de admin actualizados en 8 archivos (3 modos del organigrama)
- ✅ Lint PHP OK en los 10 archivos modificados/creados
- ✅ ZIP regenerado: `/app/intranet_corporativa_php.zip` (1.7 MB)

## 🗃️ Key DB Schema (`update_db_v4.sql`)
- `organigrama_nodos` — jerarquía padre-hijo (id, nombre, puesto, departamento, foto, parent_id, orden, color, activo)
- `organigramas_custom` — drag & drop (id, titulo, departamento, datos_json LONGTEXT, activo, fecha_creacion, fecha_actualizacion)

## 🚧 Roadmap / Backlog (P0 → P2)

### P1 — Mejoras opcionales
- Permitir mover/escalar el canvas con drag del fondo (pan)
- Búsqueda dentro del organigrama (resaltar nodo por nombre)
- Permitir asociar empleados existentes (`empleados_cumpleanos`) directamente para auto-rellenar foto/nombre

### P2 — Backlog
- Notificaciones push de eventos
- Vista móvil mejorada del calendario
- Roles granulares (no solo admin/usuario)

## 🔑 Credenciales / Integraciones
- **Admin de la intranet**: ver `/app/memory/test_credentials.md`
- **LDAP**: credenciales del usuario en `directorio.php` (configurar IP del DC, baseDN, usuario, password)
- **SQL Server**: credenciales del usuario en `bom.php`
- **No se utilizan APIs externas (LLM, Stripe, etc.)**

## 📋 Despliegue (instrucciones para el usuario)
1. Descargar `/app/intranet_corporativa_php.zip` desde el botón de descarga en Emergent
2. Extraer en `C:\xampp\htdocs\intranet`
3. Crear BD MySQL e importar en orden: `database.sql` → `update_db.sql` → `update_db_v2.sql` → `update_db_v3.sql` → `update_db_v4.sql`
4. Editar `includes/config.php` con credenciales de MySQL
5. Acceder a `http://servidor/intranet/install.php` para crear el admin
6. Habilitar extensiones `php_ldap` y `php_sqlsrv` en `php.ini` si se usan los módulos correspondientes

## 📝 Notas técnicas para el siguiente agente
- Esta app **NO** corre en el contenedor (no React, no FastAPI, no MongoDB). Se entrega como ZIP.
- Para probar localmente, el agente puede usar `php -l` para lint pero **NO** puede levantar el server. El usuario despliega en su XAMPP.
- Si hay nuevos cambios, ejecutar siempre: `cd /app && rm -f intranet_corporativa_php.zip && zip -rq intranet_corporativa_php.zip intranet_php/`
- **Idioma del usuario: español**.
