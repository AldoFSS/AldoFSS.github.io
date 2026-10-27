const {spawn, execFileSync} = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const assert = require('node:assert/strict');
const root = process.cwd();
const temp = fs.mkdtempSync(path.join(os.tmpdir(), 'cafeliz-browser-'));
const mysql = 'C:/xampp/mysql/bin/mysql.exe';
const user = 'ui_test_' + Date.now();
const password = 'PruebaInterfaz123';
const base = 'http://127.0.0.1:8766/PaginaWeb_v3/PaginaWeb/';
const sql = input => execFileSync(mysql, ['-u','root','--default-character-set=utf8mb4','-N','-B','cafeteria'], {input, encoding:'utf8', windowsHide:true}).trim();
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
const server = spawn('C:/xampp/php/php.exe', ['-S','127.0.0.1:8766','-t','C:/xampp/htdocs'], {stdio:'ignore', windowsHide:true});
const chrome = spawn('C:/Program Files/Google/Chrome/Application/chrome.exe', [
    '--headless=new','--disable-gpu','--no-first-run','--no-default-browser-check',
    '--remote-debugging-port=9333','--user-data-dir='+temp,'--window-size=1440,1100','about:blank'
], {stdio:'ignore', windowsHide:true});
let ws;
let created = false;
const output = path.join(root, 'tests', 'previews');
fs.mkdirSync(output, {recursive:true});
async function run() {
    let targets;
    for (let n=0;n<80;n++) {
        try { targets = await (await fetch('http://127.0.0.1:9333/json')).json(); if(targets.some(t=>t.type==='page')) break; } catch {}
        await sleep(100);
    }
    assert(targets, 'Chrome no inicio');
    ws = new WebSocket(targets.find(t=>t.type==='page').webSocketDebuggerUrl);
    await new Promise((resolve,reject)=>{ws.addEventListener('open',resolve,{once:true});ws.addEventListener('error',reject,{once:true});});
    let sequence = 0;
    const pending = new Map();
    ws.addEventListener('message', e => {
        const data=JSON.parse(e.data);
        if (!data.id) return;
        const p=pending.get(data.id);
        if (!p) return;
        pending.delete(data.id);
        data.error ? p.reject(new Error(JSON.stringify(data.error))) : p.resolve(data.result);
    });
    const cdp = (method,params={}) => new Promise((resolve,reject)=>{
        const id=++sequence; pending.set(id,{resolve,reject}); ws.send(JSON.stringify({id,method,params}));
    });
    const evaluate = async expression => {
        const result = await cdp('Runtime.evaluate',{expression,awaitPromise:true,returnByValue:true});
        if(result.exceptionDetails) throw new Error(JSON.stringify(result.exceptionDetails));
        return result.result.value;
    };
    const wait = async expression => {
        for(let n=0;n<100;n++) { if(await evaluate(expression)) return; await sleep(100); }
        throw new Error('Timeout: '+expression);
    };
    const screenshot = async name => {
        const result = await cdp('Page.captureScreenshot',{format:'png',captureBeyondViewport:false});
        fs.writeFileSync(path.join(output,name),Buffer.from(result.data,'base64'));
    };
    await cdp('Page.enable');
    await cdp('Network.enable');
    await cdp('Network.setBlockedURLs',{urls:['*cdn.jsdelivr.net*','*cdn.datatables.net*','*api.emailjs.com*']});
    await cdp('Emulation.setDeviceMetricsOverride',{width:1440,height:1100,deviceScaleFactor:1,mobile:false});
    await cdp('Page.navigate',{url:base+'menu.php'});
    await wait("document.readyState === 'complete' && !!document.querySelector('.menu-product')");
    sql("INSERT INTO usuarios (nombre_usuario, contraseña_usuario, Cargo) VALUES ('"+user+"',MD5('"+password+"'),'usuario')");
    created = true;
    const status = await evaluate("(async()=>{const r=await fetch('PHP/FuncionesUsuario.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({accion:'BuscarUsuario',nombre_usuario:"+JSON.stringify(user)+",contraseña_usuario:"+JSON.stringify(password)+"})});return r.status})()");
    assert.equal(status,200);
    await cdp('Page.navigate',{url:base+'menu.php'});
    await wait("document.readyState === 'complete' && !!document.querySelector('.agregar-carrito')");
    await evaluate("localStorage.removeItem('cart'); localStorage.removeItem('cafeliz-pending-order')");
    await cdp('Page.navigate',{url:base+'menu.php'});
    await wait("document.readyState === 'complete' && !!document.querySelector('.agregar-carrito')");
    await screenshot('menu-desktop.png');
    await evaluate("document.querySelector('.agregar-carrito i').click()");
    let cart = await evaluate("JSON.parse(localStorage.getItem('cart'))");
    assert.equal(cart.length,1);
    assert.equal(cart[0].quantity,1);
    assert(await evaluate("document.querySelector('.cart-toast').classList.contains('is-visible')"));
    assert(await evaluate("document.querySelector('.agregar-carrito').classList.contains('is-added')"));
    console.log('OK: clic en icono agrega y muestra confirmacion animada');
    await evaluate("document.querySelector('.agregar-carrito span').click()");
    cart=await evaluate("JSON.parse(localStorage.getItem('cart'))");
    assert.equal(cart.length,1);
    assert.equal(cart[0].quantity,2);
    assert(await evaluate("!document.querySelector('.swal2-container')"));
    console.log('OK: agregar el mismo producto aumenta cantidad sin alerta');
    await evaluate("document.querySelectorAll('.agregar-carrito')[1].click()");
    cart=await evaluate("JSON.parse(localStorage.getItem('cart'))");
    assert.equal(cart.length,2);
    assert.notEqual(cart[0].id,cart[1].id);
    console.log('OK: otro producto se agrega como una fila diferente');
    await evaluate("document.getElementById('open_cart').click()");
    await sleep(400);
    assert(await evaluate("document.getElementById('sidecart').classList.contains('open') && !document.getElementById('sidecart').inert"));
    await screenshot('carrito-desktop.png');
    await evaluate("document.querySelector('#sidecart .increase').click()");
    cart=await evaluate("JSON.parse(localStorage.getItem('cart'))");
    assert.equal(cart[0].quantity,3);
    await evaluate("document.querySelector('#sidecart .decrease').click()");
    cart=await evaluate("JSON.parse(localStorage.getItem('cart'))");
    assert.equal(cart[0].quantity,2);
    const expected=cart.reduce((sum,item)=>sum+Math.round(item.price*100)*item.quantity,0);
    const displayed=await evaluate("document.getElementById('subtotal_price').textContent");
    assert.equal(Number(displayed.replace(/,/g,'')),expected/100);
    console.log('OK: controles de cantidad y total del carrito');
    await cdp('Input.dispatchKeyEvent',{type:'keyDown',key:'Escape',code:'Escape',windowsVirtualKeyCode:27});
    await cdp('Input.dispatchKeyEvent',{type:'keyUp',key:'Escape',code:'Escape',windowsVirtualKeyCode:27});
    assert(await evaluate("!document.getElementById('sidecart').classList.contains('open') && document.getElementById('sidecart').inert"));
    console.log('OK: Escape cierra el carrito y desactiva sus controles');
    await cdp('Emulation.setDeviceMetricsOverride',{width:390,height:844,deviceScaleFactor:1,mobile:true});
    await sleep(400);
    assert(await evaluate("document.documentElement.scrollWidth <= window.innerWidth"));
    await screenshot('menu-celular.png');
    await evaluate("document.getElementById('open_cart').click()");
    await sleep(400);
    assert(await evaluate("document.getElementById('sidecart').getBoundingClientRect().width <= window.innerWidth"));
    assert(await evaluate("document.getElementById('sales_cart').getBoundingClientRect().bottom <= window.innerHeight"));
    await screenshot('carrito-celular.png');
    console.log('OK: celular sin desbordamiento y accion de pedido visible');
    await evaluate("document.querySelector('#sidecart .remove_item i').click()");
    assert.equal((await evaluate("JSON.parse(localStorage.getItem('cart'))")).length,1);
    await evaluate("document.getElementById('empty_cart').click()");
    assert(await evaluate("!!document.querySelector('.cart-empty') && document.getElementById('sales_cart').disabled"));
    console.log('OK: eliminar y estado de carrito vacio');
    await evaluate("document.getElementById('close_btn').click(); document.querySelector('.agregar-carrito').click()");
    await cdp('Page.navigate',{url:base+'menu.php'});
    await wait("document.readyState === 'complete' && document.querySelector('.cart_count span')?.textContent === '1'");
    assert.equal((await evaluate("JSON.parse(localStorage.getItem('cart'))"))[0].quantity,1);
    console.log('OK: carrito conservado al recargar');
    await evaluate("document.getElementById('open_cart').click(); document.getElementById('sales_cart').click()");
    await sleep(450);
    assert(await evaluate("document.getElementById('checkoutModal').classList.contains('show') && !document.getElementById('sidecart').classList.contains('open')"));
    console.log('OK: continuar abre una sola ventana de confirmacion');
    console.log('Capturas: tests/previews');
}
run().catch(error=>{console.error(error);process.exitCode=1;}).finally(async()=>{
    ws?.close();
    chrome.kill();
    server.kill();
    if(created) sql("DELETE FROM usuarios WHERE nombre_usuario='"+user+"'");
    // El perfil temporal queda fuera del proyecto; el proceso de Chrome puede tardar en liberar archivos.
    await sleep(400);
    try {
        const resolved = path.resolve(temp);
        if (path.dirname(resolved) === path.resolve(os.tmpdir()) && path.basename(resolved).startsWith('cafeliz-browser-')) {
            fs.rmSync(resolved,{recursive:true,force:true,maxRetries:3,retryDelay:100});
        }
    } catch {}
});
