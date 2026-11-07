function normalizarTextoInventario(texto) {
  return texto.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLocaleLowerCase("es");
}

const filtrosInventario = document.querySelector("[data-inventario-filtros]");
if (filtrosInventario) {
  const busqueda = filtrosInventario.querySelector("[data-inventario-busqueda]");
  const estado = filtrosInventario.querySelector("[data-inventario-estado]");
  const filas = [...document.querySelectorAll("[data-inventario-fila]")];
  const sinResultados = document.querySelector("[data-inventario-sin-resultados]");
  const conteo = filtrosInventario.querySelector("[data-inventario-conteo]");
  const limpiar = filtrosInventario.querySelector("[data-inventario-limpiar]");

  function filtrarInventario() {
    const termino = normalizarTextoInventario(busqueda.value.trim());
    const filtro = estado.value;
    let visibles = 0;

    filas.forEach((fila) => {
      const coincideTexto = normalizarTextoInventario(fila.textContent).includes(termino);
      const coincideEstado = filtro === "todos" ||
        (filtro === "bajo" && fila.dataset.stock === "bajo") ||
        (filtro === "proximo" && fila.dataset.caducidad === "proximo") ||
        (filtro === "caducado" && fila.dataset.caducidad === "caducado");
      const mostrar = coincideTexto && coincideEstado;
      fila.classList.toggle("inventario-fila-oculta", !mostrar);
      if (mostrar) visibles++;
    });

    sinResultados.hidden = visibles > 0;
    conteo.textContent = `Se muestran ${visibles} de ${filas.length} insumos.`;
  }

  busqueda.addEventListener("input", filtrarInventario);
  estado.addEventListener("change", filtrarInventario);
  limpiar.addEventListener("click", () => {
    busqueda.value = "";
    estado.value = "todos";
    filtrarInventario();
    busqueda.focus();
  });
  filtrarInventario();
}

const filtrosMovimientos = document.querySelector(".inventario-filtros-historial");
if (filtrosMovimientos) {
  const busqueda = filtrosMovimientos.querySelector("[data-movimientos-busqueda]");
  const tipo = filtrosMovimientos.querySelector("[data-movimientos-tipo]");
  const filas = [...document.querySelectorAll("[data-movimiento-fila]")];
  const sinResultados = document.querySelector("[data-movimientos-sin-resultados]");
  const conteo = filtrosMovimientos.querySelector("[data-movimientos-conteo]");

  function filtrarMovimientos() {
    const termino = normalizarTextoInventario(busqueda.value.trim());
    const filtroTipo = tipo.value;
    let visibles = 0;

    filas.forEach((fila) => {
      const coincideTexto = normalizarTextoInventario(fila.textContent).includes(termino);
      const coincideTipo = filtroTipo === "todos" || fila.dataset.tipo === filtroTipo;
      const mostrar = coincideTexto && coincideTipo;
      fila.classList.toggle("inventario-fila-oculta", !mostrar);
      if (mostrar) visibles++;
    });

    sinResultados.hidden = visibles > 0;
    conteo.textContent = `Se muestran ${visibles} de ${filas.length} movimientos.`;
  }

  busqueda.addEventListener("input", filtrarMovimientos);
  tipo.addEventListener("change", filtrarMovimientos);
  filtrarMovimientos();
}
