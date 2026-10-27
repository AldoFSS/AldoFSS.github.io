# Preparación de Cafeliz

La aplicación conserva PHP + MySQL y un mismo origen para laptop y celular.
La PWA guarda recursos y un carrito local; los pedidos requieren respuesta del servidor.
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

## Pendiente
Integración y verificación de pagos, pruebas visuales en ambos dispositivos,
publicación HTTPS y programación de respaldos según el alojamiento elegido.

Aplicar también BD/migracion_precios_historicos.sql una sola vez.
Las compras nuevas guardan el precio unitario; las antiguas conservan el reporte anterior
basado en el precio actual, porque el sistema anterior no almacenaba precios históricos.
La validación de origen requiere conservar Host y el esquema HTTPS al usar un proxy.

Para habilitar las solicitudes de coworking, aplicar BD/migracion_reservas_espacios.sql.
El plano incluye espacios de ejemplo: ajustar nombres, conexiones y atributos accesibles
al local real. Las solicitudes quedan pendientes; esta función no cobra ni emite Wi-Fi.

Para habilitar el menú multilingüe, aplicar BD/migracion_menu_multilingue.sql y configurar
OPENAI_API_KEY como variable de entorno de Apache/PHP (nunca en JavaScript ni en el repositorio).
Opcionalmente, definir OPENAI_MODEL; por omisión se utiliza gpt-4o. El menú admite más de
30 idiomas, detecta la preferencia del navegador y guarda una traducción por idioma en caché.
Si el servicio no está configurado o falla, el menú original en español sigue disponible.
Los productos se envían a barra con su ID y su nombre original en español; no se envían datos
personales a OpenAI. Al actualizar productos o categorías se genera una nueva traducción.

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
