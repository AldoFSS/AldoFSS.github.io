"""Prueba de integración local para la cola de barra. Requiere XAMPP y migraciones aplicadas."""
import hashlib
import http.cookiejar
import json
import subprocess
import time
import urllib.error
import urllib.parse
import urllib.request
import uuid

MYSQL = 'C:/xampp/mysql/bin/mysql.exe'
PHP = 'C:/xampp/php/php.exe'
PORT = 8767
BASE = f'http://127.0.0.1:{PORT}/PaginaWeb_v3/PaginaWeb/'
TOKEN = 'kds_test_' + uuid.uuid4().hex[:12]
PASSWORD = 'PruebaKds123!'


def sql(query):
    return subprocess.run(
        [MYSQL, '-u', 'root', '--default-character-set=utf8mb4', '-N', '-B', 'cafeteria'],
        input=query.encode('utf-8'), stdout=subprocess.PIPE, stderr=subprocess.PIPE,
        check=True, timeout=15
    ).stdout.decode('utf-8').strip()


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


def client():
    return urllib.request.build_opener(
        urllib.request.ProxyHandler({}),
        urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()),
        NoRedirect()
    )


def request(opener, path, body=None, json_body=False, origin=None):
    headers = {'Accept': 'application/json'}
    data = None
    if body is not None:
        headers['Origin'] = origin or f'http://127.0.0.1:{PORT}'
        if json_body:
            headers['Content-Type'] = 'application/json'
            data = json.dumps(body).encode()
        else:
            data = urllib.parse.urlencode(body).encode()
    req = urllib.request.Request(BASE + path, data=data, headers=headers)
    try:
        with opener.open(req, timeout=10) as response:
            return response.status, response.read().decode()
    except urllib.error.HTTPError as response:
        return response.code, response.read().decode()


server = subprocess.Popen(
    [PHP, '-S', f'127.0.0.1:{PORT}', '-t', 'C:/xampp/htdocs'],
    stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL
)
user_id = None
barista_id = None
category_id = None
product_id = None
order_id = None
try:
    for _ in range(50):
        try:
            with urllib.request.urlopen(f'http://127.0.0.1:{PORT}/', timeout=1):
                break
        except (OSError, urllib.error.URLError):
            time.sleep(.1)
    else:
        raise RuntimeError('No inició el servidor PHP local.')

    password_hash = hashlib.md5(PASSWORD.encode()).hexdigest()
    sql(
        "INSERT INTO usuarios (nombre_usuario, contraseña_usuario, Cargo) VALUES "
        f"('{TOKEN}', '{password_hash}', 'usuario'), "
        f"('{TOKEN}_barista', '{password_hash}', 'barista')"
    )
    user_id = sql(f"SELECT id FROM usuarios WHERE nombre_usuario='{TOKEN}'")
    barista_id = sql(f"SELECT id FROM usuarios WHERE nombre_usuario='{TOKEN}_barista'")
    sql(f"INSERT INTO menucategoria (Categoria) VALUES ('{TOKEN}')")
    category_id = sql(f"SELECT ID_Cat FROM menucategoria WHERE Categoria='{TOKEN}'")
    sql(
        "INSERT INTO menuproductos (Product_Nombre, Descripcion, Costo, Imagen, id_categoria) "
        f"VALUES ('{TOKEN}', 'Prueba', 10.00, '', {category_id})"
    )
    product_id = sql(f"SELECT id FROM menuproductos WHERE Product_Nombre='{TOKEN}'")

    user, barista = client(), client()
    for opener, username in ((user, TOKEN), (barista, TOKEN + '_barista')):
        code, _ = request(opener, 'PHP/FuncionesUsuario.php', {
            'accion': 'BuscarUsuario', 'nombre_usuario': username, 'contraseña_usuario': PASSWORD
        })
        assert code == 303, f'No inició sesión {username}: HTTP {code}'
    code, body = request(barista, 'barra-pedidos.php')
    assert code == 200 and 'id="kds-orders"' in body
    code, body = request(barista, 'menu-qr.php')
    assert code == 200 and 'node_modules/qrcodejs/qrcode.min.js' in body
    assert 'http://127.0.0.1:8767/PaginaWeb_v3/PaginaWeb/menu.php' in body.replace('\\/', '/')
    assert request(user, 'menu-qr.php')[0] == 403
    print('OK: barra y QR accesibles para personal autorizado; QR apunta al menú')

    payload = {'cart': [{'id': product_id, 'quantity': 1}], 'requestId': uuid.uuid4().hex}
    code, body = request(user, 'PHP/FuncionesCompra.php', payload, json_body=True)
    assert code == 200, body
    order_id = str(json.loads(body)['data']['orderId'])
    assert sql(f"SELECT Estado_KDS FROM ordenes WHERE ID_Ordenes={order_id}") == 'pendiente'
    print('OK: una compra crea una comanda pendiente')

    code, _ = request(user, 'PHP/ColaBarra.php')
    assert code == 403, f'Un cliente no debe ver la cola: HTTP {code}'
    code, body = request(barista, 'PHP/ColaBarra.php')
    queue = json.loads(body)
    assert code == 200 and any(order['id'] == int(order_id) for order in queue['orders'])
    order = next(item for item in queue['orders'] if item['id'] == int(order_id))
    assert order['items'] == [{'name': TOKEN, 'quantity': 1}]
    print('OK: barista ve el pedido y el nombre canónico del producto')

    code, _ = request(barista, 'PHP/ColaBarra.php', {
        'orderId': order_id, 'status': 'preparando'
    }, json_body=True, origin='http://otro-sitio.example')
    assert code == 403, f'Un origen ajeno no debe actualizar comandas: HTTP {code}'
    code, body = request(barista, 'PHP/ColaBarra.php', {
        'orderId': order_id, 'status': 'preparando'
    }, json_body=True)
    assert code == 200 and json.loads(body)['status'] == 'preparando'
    code, _ = request(barista, 'PHP/ColaBarra.php', {
        'orderId': order_id, 'status': 'preparando'
    }, json_body=True)
    assert code == 409, f'No debe repetirse una transición obsoleta: HTTP {code}'
    print('OK: transición autorizada y conflicto de actualización obsoleta')

    for status in ('listo', 'entregado'):
        code, body = request(barista, 'PHP/ColaBarra.php', {
            'orderId': order_id, 'status': status
        }, json_body=True)
        assert code == 200, body
    code, body = request(barista, 'PHP/ColaBarra.php')
    assert code == 200 and all(item['id'] != int(order_id) for item in json.loads(body)['orders'])
    print('OK: comanda entregada sale de la cola activa')
finally:
    if order_id:
        sql(f'DELETE FROM detallesorden WHERE ID_Orden={order_id}')
        sql(f'DELETE FROM ordenes WHERE ID_Ordenes={order_id}')
    if user_id:
        sql(f"DELETE FROM solicitudes_compra WHERE ID_Usuario IN ({user_id}, {barista_id})")
    if product_id:
        sql(f'DELETE FROM menuproductos WHERE id={product_id}')
    if category_id:
        sql(f'DELETE FROM menucategoria WHERE ID_Cat={category_id}')
    if user_id:
        sql(f"DELETE FROM usuarios WHERE id IN ({user_id}, {barista_id})")
    server.terminate()
    server.wait(timeout=10)
