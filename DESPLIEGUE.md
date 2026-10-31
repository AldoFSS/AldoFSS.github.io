# Preparación de Cafeliz

La aplicación conserva PHP + MySQL y un mismo origen para laptop y celular.
La PWA guarda recursos, el inicio y una copia del menú tras abrirlos en línea; los pedidos requieren respuesta del servidor.
Registrar un pedido no confirma un pago de tarjeta. Falta integrar el proveedor de pagos.

## Base existente
Respaldar con mysqldump antes de ejecutar BD/migracion_ventas_seguras.sql.
La migración amplía la contraseña y crea solicitudes_compra para reintentos duraderos.
No importar BD/cafeteria.sql sobre la base actual: contiene DROP TABLE y columnas antiguas.
Los hashes MD5 existentes se migran a password_hash al iniciar sesión correctamente.
Revisar cuentas antiguas con el cargo "admin": el permiso administrativo válido es "administrador".

## Servidor
Configurar APP_ENV=production y DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD
en la configuración del proceso PHP/Apache, fuera de la carpeta pública.
En desarrollo se conserva la conexión local de XAMPP.
En producción se rechaza root y la contraseña vacía.
En producción, las páginas PHP redirigen HTTP a HTTPS. Si el hosting termina HTTPS en un proxy,
configurar TRUST_PROXY_HTTPS=1 únicamente cuando el proxy de confianza sobrescriba
X-Forwarded-Proto; Apache/PHP debe recibir el host público correcto.
Usar un usuario de ejecución con SELECT, INSERT, UPDATE, DELETE; ejecutar migraciones con otro usuario.
No exponer el puerto de MySQL a internet. Publicar mediante HTTPS.
Apache debe permitir los .htaccess de BD y PRODUCTOS; en otro servidor replicar estas restricciones.
Configurar display_errors=Off y log_errors=On en producción.
Respaldar base e imágenes automáticamente y probar la restauración.
No subir respaldos dentro de htdocs.

## Validación funcional
- Iniciar sesión con cuenta existente y crear cuenta nueva.
- Probar permisos con usuario normal y administrador.
- Confirmar que modificar el total desde el cliente no modifica el total del servidor.
- Reenviar el mismo requestId/carrito: debe devolver la misma orden.
- Enviar carrito inválido: no debe quedar orden ni detalle parcial.
- Un fallo de correo no debe permitir duplicar la orden registrada.
- Productos vendidos y categorías con productos no se eliminan.
- Probar menú, carrito, tablas y modales en laptop y celular.
- Abrir el inicio y el menú con conexión, desconectar la red y confirmar que se muestra la copia guardada; otras páginas muestran el aviso offline y no aceptan operaciones.

## Pendiente
Integración y verificación de pagos, pruebas visuales en ambos dispositivos,
publicación HTTPS y programación de respaldos según el alojamiento elegido.

Aplicar también BD/migracion_precios_historicos.sql una sola vez.
Las compras nuevas guardan el precio unitario; las antiguas conservan el reporte anterior
basado en el precio actual, porque el sistema anterior no almacenaba precios históricos.
La validación de origen requiere conservar Host y el esquema HTTPS al usar un proxy.

Para habilitar las reservas de coworking, aplicar en este orden
`BD/migracion_reservas_espacios.sql`, `BD/migracion_notificaciones_push.sql` y
`BD/migracion_pago_demo_recordatorios.sql`. El plano incluye espacios de ejemplo: ajustar
nombres, conexiones y atributos accesibles al local real. El checkout permite aprobar o rechazar
un pago de demostración; no solicita ni guarda datos de tarjeta y no realiza cargos. Al aprobar,
la reserva queda pendiente de confirmación y se guarda un registro de demostración junto con
un recordatorio programado para una hora antes de la hora de inicio. No se emiten credenciales Wi-Fi.

El recordatorio requiere que el proceso PHP se ejecute periódicamente. En Windows, crear una tarea
en el Programador de tareas que se repita cada minuto, con `C:\xampp\php\php.exe` como programa y
`C:\xampp\htdocs\PaginaWeb_v3\PaginaWeb\PHP\procesar_recordatorios_reserva.php` como argumento.
La tarea debe ejecutarse con las mismas variables `DB_*` y `VAPID_*` que usa PHP/Apache. En Linux,
configurar una entrada equivalente en cron:

```cron
* * * * * /usr/bin/php /ruta/a/PaginaWeb/PHP/procesar_recordatorios_reserva.php
```

El proceso examina reservas pendientes y confirmadas; omite las canceladas o cuyo inicio ya pasó.
Si el envío falla, reintenta cada cinco minutos hasta completar cinco intentos en total. Los avisos requieren una
suscripción Push válida y configuración VAPID; ejecutar la tarea no garantiza que el sistema
operativo o el navegador muestre el aviso.

Para habilitar el menú multilingüe:

1. Aplicar `BD/migracion_menu_traducciones_estaticas.sql` una vez en la base `cafeteria`.
2. Abrir el menú y elegir Español, English o Français. Los textos de interfaz de los tres idiomas,
   junto con el catálogo original en español y las traducciones inglesa y francesa, se consultan en
   MySQL; no se necesita clave, conexión a una IA ni facturación.
3. Las traducciones de los productos actuales están incluidas en la migración. Al agregar productos
   o categorías, agregar sus textos en inglés y francés dentro de `menu_traducciones_estaticas`.
   Los artículos sin traducción permanecen visibles con su texto original en español.

El español usa los nombres y descripciones originales del catálogo. El navegador guarda en caché
las traducciones inglesa y francesa para poder reutilizarlas sin conexión.

Para habilitar el inventario predictivo, aplicar BD/migracion_inventario_predictivo.sql y
configurar en listInventario.php los insumos, existencias mínimas, cantidades de receta y
fechas de caducidad/apertura reales. Las ventas descuentan las recetas configuradas dentro
de la transacción del pedido y registran movimientos. La hoja calcula demanda con el promedio
de las cuatro últimas ocurrencias del mismo día de la semana; no integra clima/eventos ni
crea órdenes de compra automáticas.

Para habilitar la fidelización verde, aplicar BD/migracion_fidelidad_verde.sql. El administrador
puede asignar el cargo `barista` desde la gestión de usuarios. Los clientes generan un QR
temporal de un solo uso, que el barista escanea después de confirmar el uso de una taza
reutilizable. Cada registro suma 10 puntos y cuenta como un vaso evitado; cada ocho vasos
genera un cupón de bebida gratis. Las equivalencias configuradas por vaso son 80 g de CO₂,
15 g de residuos y 250 ml de agua. Los cupones se validan una sola vez en la pantalla de barra.
El QR se genera en el navegador con la dependencia local `qrcodejs`; el escaneo con cámara
requiere HTTPS (excepto localhost) y navegadores compatibles con `BarcodeDetector`. También
se puede introducir el contenido de un QR manualmente o con un lector externo.

Pruebas locales reproducibles: python -u tests/verificar_backend.py (MySQL de XAMPP activo).

## Capacidades PWA
La aplicación integrada usa un manifest y un service worker para todos los módulos. Los iconos de
instalación se generan a partir de `IMG/cafeliz-app-logo.png`, con fondo blanco y variantes para
iconos normales y adaptables; el manifest también define blanco como color de fondo y de tema.
Incluye accesos directos al menú, reservas, huella verde e inventario, además del nombre de CaféLiz.
Chrome y otros navegadores compatibles pueden mostrar la opción de instalación; en Safari para
iPhone/iPad se instala desde Compartir > Añadir a pantalla de inicio. Las opciones concretas varían
según el navegador y el sistema operativo.

La app requiere HTTPS en producción para service workers, instalación, cámara y notificaciones.
`localhost` se considera seguro para desarrollo. Las páginas de cada módulo incluyen diseño
adaptable; el escáner usa `BarcodeDetector` cuando existe y conserva una alternativa de entrada
manual cuando el navegador no ofrece esa API.

## Modo sin conexión
El service worker guarda el shell, hojas de estilo, logos, iconos e imágenes principales de la app,
además de los recursos estáticos solicitados y las páginas públicas GET de `index.php` y `menu.php`
cuando se abren en línea sin iniciar sesión. Las variantes del menú
usan la copia general; las traducciones recibidas se guardan localmente por idioma durante 30 días.
Ese contenido es una instantánea y puede quedar desactualizado.

Al abrir reservas sin conexión se ofrece guardar localmente fecha, hora, pase y accesibilidad como
borrador. Al reconectar, el usuario debe abrir de nuevo el formulario, consultar disponibilidad y
enviar su solicitud; el borrador no aparta una mesa. En inventario solo se conserva una nota local,
no existencias ni movimientos. Huella verde muestra las instrucciones offline, pero QR, métricas y
cupones requieren conexión. Pedidos, traducciones no guardadas, disponibilidad, administración y
respuestas POST nunca se almacenan ni se sincronizan automáticamente. No se cachean páginas con
`no-store`; la vista personal y las páginas de sesión se eliminan del caché.

## Notificaciones push
Aplicar una vez `BD/migracion_notificaciones_push.sql` y desplegar la dependencia PHP con
`composer install --no-dev --optimize-autoloader`. Generar las claves VAPID en el servidor con:

```sh
php -r "require 'vendor/autoload.php'; var_export(Minishlink\\WebPush\\VAPID::createVapidKeys());"
```

Configurar `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY` y `VAPID_SUBJECT` como variables privadas de PHP;
no guardar la clave privada en el repositorio. El usuario debe iniciar sesión, aceptar permiso y
activar las notificaciones en el dispositivo. Se notifican nuevas solicitudes de reserva al cliente,
recordatorios de coworking una hora antes, cupones verdes al cliente y cruces del nivel mínimo de
inventario a administradores suscritos. Los recordatorios los envía el proceso programado descrito
arriba, no el service worker. Si el navegador no admite Web Push, la conexión no es HTTPS o falta
configuración/migración, la app informa que no están disponibles. El soporte de Push en iOS depende
de la versión y de que el sitio esté instalado en la pantalla de inicio.

El service worker comprueba actualizaciones al iniciar y al volver a abrir la app con conexión.
Cuando detecta una versión nueva muestra el botón de actualización; al aceptarla activa el worker y
recarga la página para usar el código nuevo.
