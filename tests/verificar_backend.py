"""Pruebas locales para XAMPP. Requiere migraciones aplicadas y MySQL local.
Crea datos temporales propios y los elimina al terminar; no usar en produccion.
Ejecutar desde la raiz del proyecto: python -u tests/verificar_backend.py
"""
import subprocess
import socket
import time

server = subprocess.Popen(
    ['C:/xampp/php/php.exe', '-S', '127.0.0.1:8765', '-t', 'C:/xampp/htdocs'],
    stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
try:
    for attempt in range(40):
        try:
            with socket.create_connection(('127.0.0.1', 8765), timeout=0.2):
                break
        except OSError:
            time.sleep(0.1)
    import subprocess, json, uuid, urllib.request, urllib.error, urllib.parse, http.cookiejar, hashlib, re
    from pathlib import Path
    from decimal import Decimal
    from datetime import date, timedelta
    mysql = ['C:/xampp/mysql/bin/mysql.exe', '-u', 'root', '--default-character-set=utf8mb4', '-N', '-B', 'cafeteria']
    def sql(query):
        return subprocess.run(mysql, input=query.encode('utf-8'), stdout=subprocess.PIPE, stderr=subprocess.PIPE, check=True).stdout.decode('utf-8').strip()

    base = 'http://127.0.0.1:8765/PaginaWeb_v3/PaginaWeb/'
    class NoRedirect(urllib.request.HTTPRedirectHandler):
        def redirect_request(self, req, fp, code, msg, headers, newurl): return None
    def client():
        return urllib.request.build_opener(urllib.request.ProxyHandler({}), urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()), NoRedirect())
    def request(opener, path, data=None, json_body=False, origin='http://127.0.0.1:8765'):
        headers = {}
        body = None
        if data is not None:
            headers['Origin'] = origin
            if json_body:
                headers['Content-Type'] = 'application/json'
                body = json.dumps(data).encode()
            else:
                body = urllib.parse.urlencode(data).encode()
        req = urllib.request.Request(base + path, data=body, headers=headers)
        try:
            with opener.open(req, timeout=15) as response: return response.status, response.read().decode('utf-8'), response.headers
        except urllib.error.HTTPError as response:
            return response.code, response.read().decode('utf-8'), response.headers
    token = 'test_' + uuid.uuid4().hex[:16]
    password = 'PruebaSegura123!'
    hash_value = subprocess.run(['C:/xampp/php/php.exe'], input=("<?php echo password_hash('" + password + "', PASSWORD_DEFAULT);").encode(), stdout=subprocess.PIPE, check=True).stdout.decode()
    ids = []
    category = product = None
    inventory = None
    coworking_space = None
    coworking_reservation = None
    try:
        sql("INSERT INTO usuarios (nombre_usuario, contraseña_usuario, Cargo) VALUES ('"+token+"','"+hashlib.md5(password.encode()).hexdigest()+"','usuario'), ('"+token+"_admin','"+hash_value+"','administrador'), ('"+token+"_barista','"+hash_value+"','barista')")
        ids = sql("SELECT id FROM usuarios WHERE nombre_usuario IN ('"+token+"','"+token+"_admin','"+token+"_barista')").splitlines()
        uid = sql("SELECT id FROM usuarios WHERE nombre_usuario='"+token+"'")
        sql("INSERT INTO menucategoria (Categoria) VALUES ('"+token+"')")
        category = sql("SELECT ID_Cat FROM menucategoria WHERE Categoria='"+token+"'")
        sql("INSERT INTO menuproductos (Product_Nombre, Descripcion, Costo, Imagen, id_categoria) VALUES ('"+token+"','',12.34,'',"+category+")")
        product = sql("SELECT id FROM menuproductos WHERE Product_Nombre='"+token+"'")
        user, admin, barista, anonymous = client(), client(), client(), client()
        def check(condition, message):
            if not condition: raise AssertionError(message)
            print('OK: ' + message)
        for opener, name in [(user, token), (admin, token+'_admin'), (barista, token+'_barista')]:
            code, body, headers = request(opener, 'PHP/FuncionesUsuario.php', {'accion':'BuscarUsuario','nombre_usuario':name,'contrase\u00f1a_usuario':password})
            check(code == 303, 'login ' + ('administrador' if opener is admin else 'barista' if opener is barista else 'usuario heredado'))
        if sql("SHOW TABLES LIKE 'pagos_demo_reserva'") and sql("SHOW TABLES LIKE 'recordatorios_reserva'"):
            coworking_name = token + '_coworking'
            sql("INSERT INTO espacios_reserva (Nombre, Silla_Ruedas, Zurdo, Conexiones, Activo) VALUES ('"+coworking_name+"',0,0,'Contacto AC',1)")
            coworking_space = sql("SELECT ID_Espacio FROM espacios_reserva WHERE Nombre='"+coworking_name+"'")
            coworking_date = (date.today() + timedelta(days=2)).isoformat()
            reservation_data = {
                'espacio': coworking_space, 'fecha': coworking_date, 'hora': '10:00',
                'paquete': 'medio_dia', 'requisito': ''
            }
            code, _, headers = request(user, 'reservas.php', dict(reservation_data, accion='preparar_pago_demo'))
            check(code == 303 and headers.get('Location', '').endswith('pago_demo=1'),
                  'checkout simulado conserva la seleccion sin crear la reserva')
            checkout_code, checkout_body, _ = request(user, 'reservas.php?pago_demo=1')
            check(checkout_code == 200 and 'Aprobar pago simulado' in checkout_body
                  and 'Rechazar y volver' in checkout_body
                  and re.search(r"<input[^>]+name=['\"](?:tarjeta|card)", checkout_body, re.I) is None,
                  'checkout permite aprobar o rechazar sin pedir tarjeta')
            code, _, _ = request(user, 'reservas.php', {'accion':'rechazar_pago_demo'})
            check(code == 303 and sql("SELECT COUNT(*) FROM reservas_espacios WHERE ID_Usuario="+uid+" AND ID_Espacio="+coworking_space) == '0',
                  'rechazar el pago no crea una reserva')
            request(user, 'reservas.php', dict(reservation_data, accion='preparar_pago_demo'))
            code, _, headers = request(user, 'reservas.php', {'accion':'aprobar_pago_demo'})
            coworking_reservation = sql("SELECT ID_Reserva FROM reservas_espacios WHERE ID_Usuario="+uid+" AND ID_Espacio="+coworking_space+" AND Inicio='"+coworking_date+" 10:00:00'")
            check(code == 303 and headers.get('Location', '').endswith('reserva=demo_aprobado')
                  and coworking_reservation != ''
                  and sql("SELECT Estado FROM reservas_espacios WHERE ID_Reserva="+coworking_reservation) == 'pendiente',
                  'aprobar crea solicitud de reserva pendiente')
            success_code, success_body, success_headers = request(user, 'reservas.php')
            check(success_code == 200 and 'Pago de demostración aprobado, sin cobro real.' in success_body
                  and success_headers.get('X-PWA-Cache-View') == '1',
                  'reserva muestra confirmacion y permite guardar la vista offline')
            check(sql("SELECT CONCAT(Resultado,',',Monto) FROM pagos_demo_reserva WHERE ID_Reserva="+coworking_reservation) == 'aprobado,220.00',
                  'aprobacion guarda solo un pago de demostracion')
            check(sql("SELECT COUNT(*) FROM recordatorios_reserva r INNER JOIN reservas_espacios v ON v.ID_Reserva=r.ID_Reserva WHERE r.ID_Reserva="+coworking_reservation+" AND r.Estado='pendiente' AND r.Enviar_En=DATE_SUB(v.Inicio, INTERVAL 1 HOUR)") == '1',
                  'recordatorio queda programado una hora antes aunque la reserva este pendiente')
            sql("UPDATE reservas_espacios SET Estado='cancelada' WHERE ID_Reserva="+coworking_reservation)
            sql("UPDATE recordatorios_reserva SET Enviar_En=DATE_SUB(NOW(), INTERVAL 1 MINUTE) WHERE ID_Reserva="+coworking_reservation)
            reminder_id = sql("SELECT ID_Recordatorio FROM recordatorios_reserva WHERE ID_Reserva="+coworking_reservation)
            processor = Path(__file__).resolve().parents[1] / 'PHP' / 'procesar_recordatorios_reserva.php'
            processor_result = subprocess.run(
                ['C:/xampp/php/php.exe', str(processor), '--recordatorio='+reminder_id],
                stdout=subprocess.PIPE, stderr=subprocess.PIPE
            )
            check(processor_result.returncode == 0
                  and sql("SELECT Estado FROM recordatorios_reserva WHERE ID_Reserva="+coworking_reservation) == 'omitida',
                  'procesador omite recordatorio de reserva cancelada')
        else:
            print('OMITIDO: checkout y recordatorios requieren aplicar BD/migracion_pago_demo_recordatorios.sql')
        for path in ['listProductos.php', 'listCategorias.php', 'listUsuarios.php', 'listPedidos.php', 'listInventario.php']:
            code, body, headers = request(admin, path)
            check(code == 200 and 'admin-page' in body and 'Administración' in body
                  and 'Fatal error' not in body, 'pantalla administrativa ' + path)
            if path == 'listInventario.php':
                check(headers.get('X-PWA-Cache-View') == '1' and 'data-offline-view="true"' in body,
                      'inventario autoriza guardar una vista offline de solo lectura')
        green_code, green_body, green_headers = request(user, 'verde.php')
        check(green_code == 200 and green_headers.get('X-PWA-Cache-View') == '1'
              and 'data-offline-view="true"' in green_body,
              'pantalla personal verde requiere sesión y permite guardar la vista offline')
        personal_code, _, personal_headers = request(user, 'PHP/FidelidadVerde.php?accion=personal')
        community_code, _, community_headers = request(user, 'PHP/FidelidadVerde.php?accion=comunidad')
        check(personal_code == 200 and personal_headers.get('X-PWA-Cache-View') == '1'
              and community_code == 200 and community_headers.get('X-PWA-Cache-View') == '1',
              'métricas personales y comunitarias se guardan para la vista offline')
        check(request(user, 'verde-barra.php')[0] == 403, 'pantalla de barra restringida al personal')
        check(request(barista, 'verde-barra.php')[0] == 200, 'personal autorizado abre la pantalla de barra')
        check(sql("SELECT contraseña_usuario LIKE '$2%' FROM usuarios WHERE id="+uid) == '1', 'MD5 migrado a hash seguro')
        ingredient_name = token + '_stock'
        check(request(user, 'PHP/FuncionesInventario.php', {
            'accion':'crear_insumo','nombre':ingredient_name,'unidad':'g','stock':'10','minimo':'2'
        })[0] == 403, 'inventario requiere permisos de administrador')
        code, _, _ = request(admin, 'PHP/FuncionesInventario.php', {
            'accion':'crear_insumo','nombre':ingredient_name,'unidad':'g','stock':'10','minimo':'2'
        })
        check(code == 303, 'administrador registra insumo inicial')
        inventory = sql("SELECT ID_Insumo FROM inventario_insumos WHERE Nombre='"+ingredient_name+"'")
        code, _, _ = request(admin, 'PHP/FuncionesInventario.php', {
            'accion':'receta','id_producto':product,'id_insumo':inventory,'cantidad':'1.5'
        })
        check(code == 303, 'administrador define consumo de receta')
        today = date.today()
        for weeks_ago in range(1, 5):
            fecha = (today - timedelta(days=7 * weeks_ago)).isoformat()
            sql("INSERT INTO ordenes (ID_Usuario, Total, Fecha_Compra) VALUES ("+uid+", 0, '"+fecha+"')")
            historial = sql("SELECT MAX(ID_Ordenes) FROM ordenes WHERE ID_Usuario="+uid)
            sql("INSERT INTO detallesorden (ID_Orden, ID_Productos, Cantidad) VALUES ("+historial+","+product+",4)")
        payload = {'cart':[{'id':product,'quantity':2}], 'total':0.01, 'requestId':uuid.uuid4().hex}
        code, body, _ = request(anonymous, 'PHP/FuncionesCompra.php', payload, True)
        check(code == 401, 'compra sin sesion rechazada')
        code, body, _ = request(user, 'PHP/FuncionesCompra.php', payload, True, 'http://otro-sitio.example')
        check(code == 403, 'origen ajeno rechazado')
        code, body, _ = request(user, 'PHP/FuncionesCompra.php', payload, True)
        check(code == 200, 'pedido registrado: ' + (body[:250] if code != 200 else 'HTTP 200'))
        result = json.loads(body)
        check(result['data']['total'] == '24.68', 'total calculado en servidor; total manipulado ignorado')
        check(result['data']['productos'][0]['Nombre_Producto'] == token, 'comanda conserva el nombre original del catálogo en español')
        order = result['data']['orderId']
        check(sql('SELECT Stock FROM inventario_insumos WHERE ID_Insumo='+inventory) == '7.000', 'venta descuenta insumos de receta automáticamente')
        check(sql("SELECT COUNT(*) FROM movimientos_inventario WHERE ID_Orden="+str(order)+" AND Tipo='consumo'") == '1', 'venta registra movimiento de consumo')
        code, body, _ = request(admin, 'listInventario.php')
        check(code == 200 and ingredient_name in body and re.search(r'<td>6\.000</td>', body) is not None
              and re.search(r'<strong class="inventario-alerta">\s*1\.000 g\s*</strong>', body) is not None,
              'hoja pronostica demanda del mismo día de cuatro semanas')
        movement = {'accion':'movimiento','id_insumo':inventory,'tipo':'entrada','cantidad':'2','nota':'recepción de prueba'}
        check(request(admin,'PHP/FuncionesInventario.php',movement)[0] == 303, 'movimiento de entrada actualiza existencias')
        movement.update({'tipo':'merma','cantidad':'1'})
        check(request(admin,'PHP/FuncionesInventario.php',movement)[0] == 303, 'movimiento de merma actualiza existencias')
        check(sql('SELECT Stock FROM inventario_insumos WHERE ID_Insumo='+inventory) == '8.000', 'entradas y mermas quedan reflejadas en stock')
        code, body, _ = request(user, 'PHP/FuncionesCompra.php', payload, True)
        check(code == 200 and json.loads(body)['data']['orderId'] == order, 'reintento devuelve la misma orden')
        check(sql('SELECT COUNT(*) FROM ordenes WHERE ID_Usuario='+uid) == '5', 'el reintento no duplica el único pedido nuevo')
        changed = dict(payload, cart=[{'id':product,'quantity':3}])
        check(request(user, 'PHP/FuncionesCompra.php', changed, True)[0] == 422, 'clave reutilizada con otro carrito rechazada')
        invalid = {'requestId':uuid.uuid4().hex,'cart':[{'id':product,'quantity':1},{'id':2147483647,'quantity':1}]}
        check(request(user,'PHP/FuncionesCompra.php',invalid,True)[0] == 422, 'producto inexistente rechazado')
        check(sql('SELECT COUNT(*) FROM ordenes WHERE ID_Usuario='+uid) == '5', 'fallo no deja orden parcial')
        check(sql("SELECT COUNT(*) FROM solicitudes_compra WHERE ID_Usuario="+uid) == '1', 'rollback elimina solicitud fallida')
        check(request(user,'PHP/FuncionesProducto.php',{'accion':'eliminar','id':product})[0] == 403, 'usuario normal no modifica productos')
        check(request(anonymous,'listPedidos.php')[0] == 403, 'pedidos privados')
        check(request(admin,'PHP/FuncionesProducto.php',{'accion':'eliminar','id':product})[0] == 409, 'no se elimina un producto vendido')
        check(sql('SELECT COUNT(*) FROM detallesorden WHERE ID_Orden='+str(order)) == '1', 'historial conservado')
        sql('UPDATE menuproductos SET Costo=99.00 WHERE id='+product)
        check(sql('SELECT Precio_Unitario FROM detallesorden WHERE ID_Orden='+str(order)) == '12.34', 'precio historico conservado')
        code, body, _ = request(anonymous, 'PHP/FidelidadVerde.php?accion=comunidad')
        check(code == 200 and json.loads(body)['success'], 'marcador verde público disponible')
        check(request(anonymous, 'PHP/FidelidadVerde.php?accion=personal')[0] == 401, 'billetera verde requiere sesión')
        code, body, _ = request(user, 'PHP/FidelidadVerde.php', {'accion':'emitir_qr'}, True)
        expired_qr = json.loads(body)['qr']
        expired_hash = hashlib.sha256(expired_qr.split(':', 1)[1].lower().encode()).hexdigest()
        sql("UPDATE eco_qr_tokens SET Expira=DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 MINUTE) WHERE Huella='"+expired_hash+"'")
        check(request(barista, 'PHP/FidelidadVerde.php',
                      {'accion':'registrar_termo','qr':expired_qr}, True)[0] == 409, 'QR vencido rechazado')
        qr_codes = []
        for index in range(8):
            code, body, _ = request(user, 'PHP/FidelidadVerde.php', {'accion':'emitir_qr'}, True)
            check(code == 200 and json.loads(body)['vigenciaSegundos'] == 300, 'QR temporal generado')
            qr = json.loads(body)['qr']
            qr_codes.append(qr)
            if index == 0:
                check(request(user, 'PHP/FidelidadVerde.php', {'accion':'registrar_termo','qr':qr}, True)[0] == 403,
                      'solo el personal registra vasos reutilizables')
            code, body, _ = request(barista, 'PHP/FidelidadVerde.php',
                                    {'accion':'registrar_termo','qr':qr}, True)
            check(code == 200 and json.loads(body)['vasos'] == index + 1, 'escaneo de termo registrado una sola vez')
        check(json.loads(body)['cupon'] is not None, 'octavo vaso genera cupón')
        check(request(barista, 'PHP/FidelidadVerde.php',
                      {'accion':'registrar_termo','qr':qr_codes[0]}, True)[0] == 409, 'QR reutilizado rechazado')
        personal_code, personal_body, _ = request(user, 'PHP/FidelidadVerde.php?accion=personal')
        personal = json.loads(personal_body)
        check(personal_code == 200 and personal['vasos'] == 8 and personal['puntos'] == 80
              and personal['progreso'] == 0 and len(personal['cupones']) == 1,
              'billetera muestra impacto, puntos e insignia')
        check(sql("SELECT CONCAT(SUM(CO2_g),',',SUM(Residuos_g),',',SUM(Agua_ml)) FROM eco_eventos WHERE ID_Usuario="+uid)
              == '640,120,2000', 'equivalencias ecológicas aplicadas en servidor')
        coupon = personal['cupones'][0]['codigo']
        check(request(user, 'PHP/FidelidadVerde.php', {'accion':'canjear_cupon','codigo':coupon}, True)[0] == 403,
              'canje de cupón requiere personal de cafetería')
        check(request(barista, 'PHP/FidelidadVerde.php',
                      {'accion':'canjear_cupon','codigo':coupon}, True)[0] == 200, 'cupón canjeado')
        check(request(barista, 'PHP/FidelidadVerde.php',
                      {'accion':'canjear_cupon','codigo':coupon}, True)[0] == 409, 'cupón no puede canjearse dos veces')
        code, body, _ = request(admin,'listPedidos.php')
        check(code == 200 and 'Fatal error' not in body, 'pantalla administrativa carga')
        for path in ['menu.php','login.php','registro.php','index.php']:
            code, body, _ = request(anonymous,path)
            check(code == 200 and 'Fatal error' not in body and 'Warning:' not in body, 'pantalla ' + path)
        for language, button_text, category_text, product_text in [
                ('es','Buscar','Bebidas','Frappe'),
                ('en','Search','Drinks','Frappe'),
                ('fr','Rechercher','Boissons','Frappé')]:
            code, body, _ = request(anonymous,'PHP/TraducirMenu.php', {'language':language}, True)
            translation = json.loads(body)
            check(code == 200 and translation['success'] and
                  translation['translation']['ui']['searchButton'] == button_text and
                  translation['translation']['categories']['1'] == category_text and
                  translation['translation']['products']['24']['name'] == product_text,
                  'traducción estática ' + language)
        code, body, _ = request(anonymous,'PHP/TraducirMenu.php', {'language':'xx'}, True)
        check(code == 422 and json.loads(body)['success'] is False, 'idioma de traducción no admitido rechazado')
        sql("UPDATE usuarios SET Cargo='usuario' WHERE nombre_usuario='"+token+"_admin'")
        check(request(admin,'listPedidos.php')[0] == 403, 'permiso revocado rechaza la sesion anterior')
        print('Nota: .htaccess requiere comprobacion con Apache activo')
        print('Pruebas terminadas')
    finally:
        if coworking_space:
            if coworking_reservation:
                sql('DELETE FROM reservas_espacios WHERE ID_Reserva='+coworking_reservation)
            sql('DELETE FROM espacios_reserva WHERE ID_Espacio='+coworking_space)
        if ids:
            joined = ','.join(ids)
            sql('DELETE FROM eco_cupones WHERE ID_Usuario IN ('+joined+'); DELETE FROM eco_eventos WHERE ID_Usuario IN ('+joined+'); DELETE FROM eco_qr_tokens WHERE ID_Usuario IN ('+joined+')')
            sql('DELETE FROM movimientos_inventario WHERE ID_Orden IN (SELECT ID_Ordenes FROM ordenes WHERE ID_Usuario IN ('+joined+'))')
            sql('DELETE d FROM detallesorden d INNER JOIN ordenes o ON d.ID_Orden=o.ID_Ordenes WHERE o.ID_Usuario IN ('+joined+'); DELETE FROM solicitudes_compra WHERE ID_Usuario IN ('+joined+'); DELETE FROM ordenes WHERE ID_Usuario IN ('+joined+'); DELETE FROM usuarios WHERE id IN ('+joined+')')
        if inventory:
            sql('DELETE FROM recetas_insumos WHERE ID_Insumo='+inventory+'; DELETE FROM movimientos_inventario WHERE ID_Insumo='+inventory+'; DELETE FROM inventario_insumos WHERE ID_Insumo='+inventory)
        if product: sql('DELETE FROM menuproductos WHERE id='+product)
        if category: sql('DELETE FROM menucategoria WHERE ID_Cat='+category)
        print('Datos temporales eliminados')

finally:
    server.terminate()
    server.wait(timeout=10)
