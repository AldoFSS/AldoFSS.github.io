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

Para habilitar las reservas de coworking, aplicar
`BD/migracion_reservas_espacios.sql`, `BD/migracion_notificaciones_push.sql` y
`BD/migracion_pago_demo_recordatorios.sql` en ese orden. Si la primera migracion ya se habia
aplicado, volver a ejecutarla agrega ocho espacios de ejemplo sin duplicarlos. El plano
incluye espacios de ejemplo: ajustar
nombres, conexiones y atributos accesibles al local real. El catalogo de ejemplo incluye 14 mesas,
seis con espacio para silla de ruedas. El plano tambien muestra areas conceptuales de preparacion,
caja y sanitario accesible; validar su distribucion y adecuaciones antes de presentarlo como un local
real. El checkout permite aprobar o rechazar
un pago de demostración; no solicita ni guarda datos de tarjeta y no realiza cargos. Al aprobar,
la reserva queda pendiente de confirmación y se guarda un registro de demostración junto con
un recordatorio programado para una hora antes de la hora de inicio. No se emiten credenciales Wi-Fi.

El recordatorio requiere que el proceso PHP se ejecute periódicamente. En InfinityFree se configura
mediante el workflow de GitHub Actions descrito en la sección **Notificaciones push**; cada ejecución
comprueba cupones y reservas. Para desarrollo local, se puede ejecutar el script con
`C:\xampp\php\php.exe` y las mismas variables `DB_*` y `VAPID_*` de PHP/Apache.

El proceso examina reservas pendientes y confirmadas; omite las canceladas o cuyo inicio ya pasó.
Los avisos requieren una suscripción Web Push activa y las credenciales VAPID configuradas; que el
servicio push acepte el mensaje no garantiza que el sistema operativo o el navegador lo muestre.

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

La traducción de catálogo disponible en producción es estática (español, inglés y francés).
La aplicación detecta `navigator.languages` y conserva la selección, pero no ofrece los 30 idiomas
de la propuesta ni llama a OpenAI. El generador opcional con Ollama es una herramienta local de
revisión, no un servicio de traducción en tiempo real. InfinityFree no pudo conectarse al servicio
intermediario en las pruebas anteriores y actualmente no hay una clave OpenAI configurada en el
servidor; no se deben publicar claves en JavaScript ni prometer traducción automática sin resolver
esas condiciones.

## Comandas de barra y QR del menú

Aplicar una sola vez `BD/migracion_comandas_barra.sql` después de respaldar la base de datos.
Los pedidos previos quedan marcados como entregados; cada nueva compra autenticada se crea como
pendiente. El personal de barra puede abrir `barra-pedidos.php`: consulta la cola cada cinco
segundos sin recargar la página y permite avanzar cada pedido por pendiente, preparación, listo y
entregado. El nombre que aparece es el nombre original del catálogo en español; todavía no se
traducen personalizaciones como “oat milk, extra hot” ni se relacionan solicitudes extranjeras con
insumos. Este sondeo es compatible con PHP/MySQL compartido, pero requiere conexión y no es una
notificación instantánea por WebSocket.

El personal de barra y administración puede abrir `menu-qr.php`, imprimir el QR y colocarlo en las
mesas. El código dirige al menú en el navegador; no se necesita descargar la aplicación desde una
tienda. La URL pública debe usar HTTPS para habilitar la instalación PWA y que los navegadores
permitan las funciones de instalación.

Para validar localmente la cola, después de aplicar las migraciones necesarias, ejecutar
`python tests/verificar_kds.py`. La prueba crea datos temporales y los elimina al finalizar.

### Alcance restante de la propuesta 4

- La detección automática y el selector manual funcionan para los tres idiomas que tienen
  traducciones guardadas (español, inglés y francés), no para más de 30.
- El QR, la instalación PWA y la visualización offline del menú no descargan una app de una tienda.
  La disponibilidad offline depende de haber abierto el menú con conexión en ese dispositivo.
- El KDS recibe las compras registradas y actualiza estados por sondeo, pero no interpreta
  instrucciones de preparación escritas por el cliente ni mapea cambios de leche/temperatura a
  insumos.
- El catálogo incluye Café de Olla; no incluye Metate Mocha ni Pan de Muerto. No se agregan como
  productos vendibles sin aprobación de catálogo, ingredientes e inventario.
- La herramienta local con Ollama genera borradores de inglés y francés para revisión, pero no es
  un conector OpenAI en tiempo real. Sin credenciales y salida HTTPS permitida desde el hosting,
  los puntos de OpenAI y traducción dinámica no se pueden validar en InfinityFree.
- Cero errores de barra y $22,080 MXN adicionales al mes son resultados que requieren medición
  operativa y datos de ventas; no se pueden afirmar como garantizados por la implementación.

### Actualizar traducciones con un modelo local

Las traducciones del menú pueden regenerarse sin contratar una API de IA. En la computadora local,
instalar Ollama y descargar, por ejemplo, `qwen2.5:3b` (`ollama pull qwen2.5:3b`). Iniciar Ollama,
activar la base local `cafeteria` y ejecutar desde la raíz del proyecto:

```powershell
php tools/menu_traducciones_ia.php generar
```

El comando envía al Ollama local los nombres, descripciones y categorías del catálogo; genera una
revisión JSON de inglés y francés en `%USERPROFILE%\.cafeliz\menu-traducciones-revision.json` y
no modifica la base. Revisar el archivo, corregir manualmente los textos si hace falta y aplicar
la versión aprobada con:

```powershell
php tools/menu_traducciones_ia.php aplicar "$env:USERPROFILE\.cafeliz\menu-traducciones-revision.json"
```

La aplicación actualiza solo nombres, descripciones y categorías traducidos, conserva la interfaz
y las etiquetas de cupones, y rechaza revisiones si el catálogo cambió después de generarlas.
El proceso solo acepta un endpoint Ollama en localhost; no usa OpenAI, no necesita claves y no
contacta servicios remotos. No inventa ingredientes ni alérgenos: primero habría que añadir esos
datos al catálogo si se quieren traducir. Para publicar los cambios, exportar únicamente las filas
`en` y `fr` de `menu_traducciones_estaticas` desde phpMyAdmin local e importarlas en la base de
InfinityFree. No exportar ni importar toda la base pública.

Para habilitar el inventario predictivo, aplicar `BD/migracion_inventario_predictivo.sql` y
configurar los insumos, existencias mínimas, cantidades de receta y fechas de caducidad/apertura
reales. Las ventas descuentan las recetas configuradas dentro de la transacción del pedido y
registran movimientos. La demanda base usa el promedio de las cuatro últimas ocurrencias del
mismo día de la semana.

Para añadir el contexto de clima y eventos, aplicar después
`BD/migracion_pronostico_contextual.sql`. El panel consulta el pronóstico diario de Open-Meteo
por HTTPS, sin clave, y conserva el último resultado durante 30 minutos. Si la API falla, informa
el estado y usa el pronóstico guardado de hoy, si existe; sin un dato de hoy no aplica un ajuste
meteorológico. El servidor necesita cURL y acceso HTTPS saliente a `api.open-meteo.com`.
El servicio se atribuye en el panel; revisar los términos de Open-Meteo antes de redistribuir los
datos.

La ubicación inicial es el centro aproximado de Tulancingo de Bravo, Hidalgo
(20.080550, -98.368310), no la dirección física del negocio. Un administrador puede sustituirla
por las coordenadas del local. Los eventos se registran manualmente con coordenadas, horario y
un porcentaje de impacto esperado; solo los que coinciden con el día y quedan a 2 km o menos
del punto configurado se muestran como considerados. Los porcentajes de clima y eventos son
reglas académicas editables, no estimaciones aprendidas ni resultados validados: por defecto,
las bebidas frías reciben +10% si la máxima alcanza 25 °C y las calientes +10% si es de 18 °C
o menos. La migración marca inicialmente como fríos los productos de la categoría `Bebidas`;
el administrador puede cambiar la sensibilidad de cada producto y ajustar umbrales y factores.
La lluvia se muestra pero no modifica la demanda. Los impactos de eventos se suman, y la suma
combinada con clima se limita a +200%. La hoja sigue siendo una referencia y no crea órdenes de compra ni se
genera automáticamente a las 5:30 AM; no hay una tarea programada nueva en esta integración.

Para habilitar la fidelización verde, aplicar `BD/migracion_fidelidad_verde.sql` y,
después de `BD/migracion_precios_historicos.sql`, `BD/migracion_cupones_verdes.sql`.
El administrador
puede asignar el cargo `barista` desde la gestión de usuarios. Los clientes generan un QR
temporal de un solo uso, que el barista escanea después de confirmar el uso de una taza
reutilizable. Cada registro suma 10 puntos y cuenta como un vaso evitado; cada ocho vasos
genera un cupón para una bebida de la categoría `Bebidas`, válido por 14 días. El cliente puede
seleccionar el cupón y una bebida de su pedido en el checkout; el servidor calcula el descuento,
lo guarda con la orden y consume el cupón en una sola transacción. La billetera conserva el
historial de cupones disponibles, canjeados y vencidos. Las equivalencias configuradas por vaso
son 80 g de CO₂, 15 g de residuos y 250 ml de agua. El progreso personal se muestra con ocho
iconos de vasos. Si el cliente activó Push, el escaneo del barista confirma el registro y un proceso
programado le recuerda usar el cupón cuando esté por vencer.
El QR se genera en el navegador con la dependencia local `qrcodejs`; el escaneo con cámara
requiere HTTPS (excepto localhost) y navegadores compatibles con `BarcodeDetector`. También
se puede introducir el contenido de un QR manualmente o con un lector externo.

Los avisos de cupones se procesan con el mismo workflow de GitHub Actions cada cinco minutos. Se
intentan durante los tres días anteriores al vencimiento, hasta cinco veces con intervalos de cinco
minutos. Requieren que el cliente tenga activadas las notificaciones Web Push.

Pruebas locales reproducibles: python -u tests/verificar_backend.py (MySQL de XAMPP activo).
La autenticación HMAC del workflow también se puede comprobar sin MySQL con
`php tests/verificar_autenticacion_tareas.php`.

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
El service worker guarda el shell, hojas de estilo, scripts, fuentes, logos, iconos e imágenes de
productos de la app. Usa Cache First para recursos con versión en la URL y Stale While Revalidate
para los recursos estáticos sin versión. Las navegaciones usan Network First. Se guardan únicamente
las vistas públicas GET de `index.php`, `menu.php`, `login.php`, `registro.php` y `reservas.php`
cuando se abren en línea sin iniciar sesión y sin parámetros; el service worker también precarga
vistas seguras para menú, reservas, inventario y huella verde. Al recargar sin conexión, se muestra
la copia segura de la misma ruta cuando está disponible. No hay una página offline genérica.
Las vistas administrativas y personalizadas, así como datos de sesión, APIs y respuestas POST, no se
guardan. Si una ruta no tiene una copia pública segura o una vista offline preparada, el navegador
no puede mostrar la página del servidor sin conexión.
Las traducciones recibidas se guardan localmente por idioma durante 30 días.

Al abrir reservas sin conexión se ofrece guardar localmente fecha, hora, pase y accesibilidad como
borrador. Una copia pública previa de reservas se muestra como solo lectura; la disponibilidad, el
plano de mesas y el checkout se ocultan al mostrar la copia guardada; al reconectar, se debe consultar
de nuevo la disponibilidad. El borrador no aparta una mesa.

Las rutas de inventario y huella verde cargan una vista offline segura del módulo, con las mismas
secciones principales y estilos, pero sin existencias, datos personales, cupones ni códigos QR.
En inventario solo se conserva una nota local, no existencias ni movimientos. Las copias guardadas
del menú permiten consultar los productos, pero no agregar artículos ni enviar pedidos. Reservas
mantiene la estructura de la página y, si había una copia pública previa, puede mostrar horarios y
mesas guardados, que pueden estar desactualizados; las acciones de selección, pago y envío permanecen
bloqueadas. QR, métricas personales, cupones, pedidos, traducciones no guardadas, disponibilidad
actual, administración y respuestas POST requieren conexión y nunca se sincronizan automáticamente.
Se respeta `Cache-Control: no-store`.

## Notificaciones push
La implementación usa Web Push estándar con VAPID. Aplicar una vez
`BD/migracion_notificaciones_push.sql` en la base `cafeteria`. Configurar en PHP:
`VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY` y `VAPID_SUBJECT`. La clave privada debe permanecer como
secreto del servidor; nunca se incluye en JavaScript ni se publica. La clave pública se entrega al
navegador para crear la suscripción. `minishlink/web-push` ya está declarado en Composer; desplegar
`vendor` como de costumbre.

El usuario inicia sesión, acepta el permiso mediante el botón y activa las notificaciones en cada
dispositivo. La suscripción Web Push se guarda asociada a la cuenta. El Service Worker de CaféLiz
recibe y muestra las notificaciones en segundo plano, incluso cuando la aplicación no está abierta.
Después de desplegar esta versión, desactiva y vuelve a activar las notificaciones en cada
dispositivo para recrear la suscripción con la clave VAPID de CaféLiz. Si ya aplicaste la migración
aditiva de FCM, no borres tablas ni columnas: los campos extra no afectan a Web Push. No vuelvas a
aplicar esa migración ni registres nuevos tokens FCM.

La zona horaria opcional `APP_TIMEZONE` usa `America/Mexico_City` por defecto y se aplica tanto a
PHP como a la sesión MySQL de reservas y recordatorios.

### Automatizar recordatorios en InfinityFree

InfinityFree no ejecuta cron tradicional. El workflow `.github/workflows/recordatorios.yml` de este
repositorio intenta llamar cada cinco minutos a los procesadores PHP mediante HTTPS. Para activarlo:

1. Configurar `RECORDATORIOS_HMAC_SECRET` como variable privada de PHP en InfinityFree y como
   **Actions secret** del repositorio de GitHub. Usa el mismo valor aleatorio de al menos 32
   caracteres en ambos sitios. Para generar uno en una máquina confiable, ejecuta:
   `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`.
2. Desplegar el código y workflow, y ejecutar manualmente **Procesar recordatorios CaféLiz** desde
   Actions. Repetir la prueba en la URL publicada y revisar el resultado de ambos procesadores.
3. Confirmar que el workflow recibe HTTP 200 para ambos endpoints. Si InfinityFree responde con una
   página anti-bots, 403 u otro bloqueo, esta alternativa **no está operativa** y los recordatorios
   no se pueden garantizar en ese hosting; no se debe interpretar el workflow creado como prueba
   de entrega.

El workflow envía una firma HMAC con marca temporal; el secreto no se incluye en la URL ni en el
código del repositorio. Los endpoints no aceptan llamadas GET ni solicitudes sin firma válida. La
frecuencia gratuita de GitHub Actions es de mejor esfuerzo: la ejecución puede retrasarse y no
garantiza precisión de reloj. Los recordatorios de reserva y cupón conservan sus colas y reintentos
actuales; los demás avisos Web Push se intentan cuando ocurre el evento en CaféLiz. Se requiere
HTTPS. En iOS, el soporte depende de la versión y de instalar la PWA en la pantalla de inicio.

El service worker comprueba actualizaciones al iniciar y al volver a abrir la app con conexión.
Cuando detecta una versión nueva muestra el botón de actualización; al aceptarla activa el worker y
recarga la página para usar el código nuevo.
