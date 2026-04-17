# Intranet Corporativa - Empresa Automotriz
## Sistema PHP + MySQL para XAMPP en Windows Server

### Requisitos
- XAMPP con PHP 7.4+ y MySQL 5.7+
- Extensiones PHP: PDO, PDO_MySQL, GD

### Instalación

1. **Copiar archivos**
   - Copia toda la carpeta `intranet_php` a `C:\xampp\htdocs\`
   - La ruta final debe ser: `C:\xampp\htdocs\intranet_php\`

2. **Crear la base de datos**
   - Abre phpMyAdmin: http://localhost/phpmyadmin
   - Importa el archivo `database.sql` o ejecuta su contenido
   - Esto creará la base de datos `intranet_db` con todas las tablas

3. **Configurar la conexión**
   - Edita `includes/config.php`
   - Modifica las credenciales de MySQL si es necesario:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', ''); // Tu contraseña de MySQL
     define('DB_NAME', 'intranet_db');
     ```
   - Ajusta `SITE_URL` a tu URL real

4. **Permisos de carpetas**
   - Asegúrate que la carpeta `assets/uploads/` tenga permisos de escritura
   - Subcarpetas: slider, gallery, videos, files, employees, articles, portals

5. **Acceder a la intranet**
   - Página principal: http://localhost/intranet_php/
   - Panel de administración: http://localhost/intranet_php/admin/

### Credenciales por defecto
- **Usuario:** admin
- **Contraseña:** admin123

### Estructura del proyecto
```
intranet_php/
├── admin/                  # Panel de administración
│   ├── index.php          # Dashboard
│   ├── login.php          # Login
│   ├── slider.php         # Gestión slider
│   ├── eventos.php        # Gestión eventos
│   ├── cumpleanos.php     # Gestión cumpleaños
│   ├── galeria.php        # Gestión galería
│   ├── videos.php         # Gestión videos
│   ├── articulos.php      # Gestión artículos
│   ├── archivos.php       # Gestión archivos
│   ├── portales.php       # Gestión portales
│   ├── countdown.php      # Gestión cuenta regresiva
│   ├── avisos.php         # Gestión avisos
│   └── usuarios.php       # Gestión usuarios
├── api/
│   └── get_files.php      # API archivos
├── assets/
│   ├── css/
│   │   ├── style.css      # Estilos frontend
│   │   └── admin.css      # Estilos admin
│   ├── js/
│   │   └── main.js        # JavaScript
│   ├── img/               # Imágenes estáticas
│   └── uploads/           # Archivos subidos
│       ├── slider/
│       ├── gallery/
│       ├── videos/
│       ├── files/
│       ├── employees/
│       ├── articles/
│       └── portals/
├── includes/
│   ├── config.php         # Configuración
│   └── functions.php      # Funciones auxiliares
├── index.php              # Página principal
├── articulo.php           # Ver artículo
├── download.php           # Descarga archivos
├── database.sql           # Script BD
└── README.md              # Este archivo
```

### Módulos incluidos
1. **Página Principal**
   - Slider de noticias con imágenes
   - Banner de avisos
   - Cuenta regresiva para eventos
   - Calendario de eventos con scroll
   - Cumpleaños de empleados con felicitación
   - Cuadrícula de 12 aplicaciones
   - Archivos por departamento
   - Galería de fotos
   - Slider de videos
   - Últimas 5 noticias
   - Sección "Nuestra Compañía"
   - Portales de clientes

2. **Panel de Administración**
   - Dashboard con estadísticas
   - CRUD completo para todos los módulos
   - Gestión de usuarios administradores
   - Subida de archivos local

### Departamentos configurados
- IT, RH, Compras, Ventas, Logística, Proyectos, Pintura, Calidad

### 12 Aplicaciones configuradas
Las URLs de las aplicaciones son editables desde el panel de administración.
Por defecto están configuradas las 12 URLs que proporcionaste.

### Colores corporativos
- Negro (#1a1a1a) - Principal
- Gris (#2d2d2d, #4a4a4a) - Secundarios
- Rojo (#e53935) - Acento principal
- Azul (#1976d2) - Acento
- Verde (#43a047) - Acento

### Soporte
Para cambiar el logo, reemplaza el archivo `assets/img/logo.png` con tu logo.
Para cambiar el fondo del header, reemplaza `assets/img/fondo1.png`.
