<?php
function distanciaKm(float $latitudA, float $longitudA, float $latitudB, float $longitudB): float
{
    $radioTierraKm = 6371.0088;
    $diferenciaLatitud = deg2rad($latitudB - $latitudA);
    $diferenciaLongitud = deg2rad($longitudB - $longitudA);
    $a = sin($diferenciaLatitud / 2) ** 2 +
        cos(deg2rad($latitudA)) * cos(deg2rad($latitudB)) *
        sin($diferenciaLongitud / 2) ** 2;
    return 2 * $radioTierraKm * asin(min(1, sqrt($a)));
}

function descripcionClima(int $codigo): string
{
    $descripciones = [
        0 => 'Despejado',
        1 => 'Mayormente despejado',
        2 => 'Parcialmente nublado',
        3 => 'Nublado',
        45 => 'Niebla',
        48 => 'Niebla con escarcha',
        51 => 'Llovizna ligera',
        53 => 'Llovizna moderada',
        55 => 'Llovizna intensa',
        56 => 'Llovizna helada ligera',
        57 => 'Llovizna helada intensa',
        61 => 'Lluvia ligera',
        63 => 'Lluvia moderada',
        65 => 'Lluvia intensa',
        66 => 'Lluvia helada ligera',
        67 => 'Lluvia helada intensa',
        71 => 'Nevada ligera',
        73 => 'Nevada moderada',
        75 => 'Nevada intensa',
        77 => 'Granizo menudo',
        80 => 'Chubascos ligeros',
        81 => 'Chubascos moderados',
        82 => 'Chubascos intensos',
        85 => 'Chubascos de nieve ligeros',
        86 => 'Chubascos de nieve intensos',
        95 => 'Tormenta',
        96 => 'Tormenta con granizo ligero',
        99 => 'Tormenta con granizo intenso'
    ];
    return $descripciones[$codigo] ?? 'Condición variable';
}

function cargarPronosticoClima(mysqli $connection, array $configuracion): array
{
    $consulta = $connection->prepare(
        'SELECT Latitud, Longitud, Fecha_Clima, Temperatura_Maxima,
            Probabilidad_Precipitacion, Precipitacion_MM, Codigo_Clima,
            Actualizado, Intento_Actualizacion, Ultimo_Error,
            TIMESTAMPDIFF(SECOND, Actualizado, CURRENT_TIMESTAMP) AS Segundos_Desde_Actualizacion,
            TIMESTAMPDIFF(SECOND, Intento_Actualizacion, CURRENT_TIMESTAMP) AS Segundos_Desde_Intento
        FROM inventario_pronostico_clima WHERE ID_Config=1'
    );
    $consulta->execute();
    $cache = $consulta->get_result()->fetch_assoc() ?: null;
    $hoy = (new DateTimeImmutable('now', new DateTimeZone('America/Mexico_City')))->format('Y-m-d');
    $mismasCoordenadas = $cache &&
        abs((float) $cache['Latitud'] - (float) $configuracion['Latitud']) < 0.000001 &&
        abs((float) $cache['Longitud'] - (float) $configuracion['Longitud']) < 0.000001;
    if ($cache && !$mismasCoordenadas) {
        $connection->query('DELETE FROM inventario_pronostico_clima WHERE ID_Config=1');
        $cache = null;
    }
    $pronosticoDeHoy = $mismasCoordenadas && $cache['Fecha_Clima'] === $hoy &&
        $cache['Actualizado'] !== null;

    $segundosDesdeActualizacion = (int) ($cache['Segundos_Desde_Actualizacion'] ?? PHP_INT_MAX);
    if ($pronosticoDeHoy && $segundosDesdeActualizacion >= 0 && $segundosDesdeActualizacion <= 1800) {
        return [
            'disponible' => true,
            'obsoleto' => false,
            'temperatura_maxima' => (float) $cache['Temperatura_Maxima'],
            'probabilidad_precipitacion' => (int) $cache['Probabilidad_Precipitacion'],
            'precipitacion_mm' => (float) $cache['Precipitacion_MM'],
            'codigo' => (int) $cache['Codigo_Clima'],
            'descripcion' => descripcionClima((int) $cache['Codigo_Clima']),
            'actualizado' => $cache['Actualizado']
        ];
    }

    $segundosDesdeIntento = (int) ($cache['Segundos_Desde_Intento'] ?? PHP_INT_MAX);
    if ($cache && $cache['Intento_Actualizacion'] !== null &&
        $segundosDesdeIntento >= 0 && $segundosDesdeIntento <= 300) {
        return pronosticoDesdeCache($cache, $pronosticoDeHoy);
    }

    $marcarIntento = $connection->prepare(
        'INSERT INTO inventario_pronostico_clima
            (ID_Config, Latitud, Longitud, Intento_Actualizacion, Ultimo_Error)
        VALUES (1, ?, ?, CURRENT_TIMESTAMP, NULL)
        ON DUPLICATE KEY UPDATE Latitud=VALUES(Latitud), Longitud=VALUES(Longitud),
            Intento_Actualizacion=CURRENT_TIMESTAMP, Ultimo_Error=NULL'
    );
    $latitud = (string) $configuracion['Latitud'];
    $longitud = (string) $configuracion['Longitud'];
    $marcarIntento->bind_param('ss', $latitud, $longitud);
    $marcarIntento->execute();

    try {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('La extensión cURL de PHP no está habilitada.');
        }
        $url = 'https://api.open-meteo.com/v1/forecast?' . http_build_query([
            'latitude' => $latitud,
            'longitude' => $longitud,
            'daily' => 'temperature_2m_max,precipitation_probability_max,precipitation_sum,weather_code',
            'timezone' => 'America/Mexico_City',
            'forecast_days' => 1
        ]);
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_USERAGENT => 'CafelizInventoryForecast/1.0'
        ]);
        $respuesta = curl_exec($curl);
        $errorCurl = curl_error($curl);
        $estadoHttp = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if (!is_string($respuesta) || $estadoHttp < 200 || $estadoHttp >= 300) {
            throw new RuntimeException(
                'Open-Meteo respondió HTTP ' . $estadoHttp .
                ($errorCurl !== '' ? ': ' . $errorCurl : '.')
            );
        }
        $datos = json_decode($respuesta, true, 16, JSON_THROW_ON_ERROR);
        $diario = $datos['daily'] ?? null;
        if (!is_array($diario) || ($diario['time'][0] ?? null) !== $hoy ||
            !is_numeric($diario['temperature_2m_max'][0] ?? null) ||
            !is_numeric($diario['precipitation_probability_max'][0] ?? null) ||
            !is_numeric($diario['precipitation_sum'][0] ?? null) ||
            !is_numeric($diario['weather_code'][0] ?? null)) {
            throw new RuntimeException('Open-Meteo no devolvió un pronóstico diario válido para hoy.');
        }

        $temperatura = (float) $diario['temperature_2m_max'][0];
        $probabilidad = (int) $diario['precipitation_probability_max'][0];
        $precipitacion = (float) $diario['precipitation_sum'][0];
        $codigo = (int) $diario['weather_code'][0];
        if ($temperatura < -80 || $temperatura > 60 ||
            $probabilidad < 0 || $probabilidad > 100 || $precipitacion < 0) {
            throw new RuntimeException('Open-Meteo devolvió valores fuera de rango.');
        }
        $guardar = $connection->prepare(
            'UPDATE inventario_pronostico_clima SET Fecha_Clima=?, Temperatura_Maxima=?,
                Probabilidad_Precipitacion=?, Precipitacion_MM=?, Codigo_Clima=?,
                Actualizado=CURRENT_TIMESTAMP, Ultimo_Error=NULL
            WHERE ID_Config=1'
        );
        $guardar->bind_param('sdidi', $hoy, $temperatura, $probabilidad, $precipitacion, $codigo);
        $guardar->execute();
        return [
            'disponible' => true,
            'obsoleto' => false,
            'temperatura_maxima' => $temperatura,
            'probabilidad_precipitacion' => $probabilidad,
            'precipitacion_mm' => $precipitacion,
            'codigo' => $codigo,
            'descripcion' => descripcionClima($codigo),
            'actualizado' => (new DateTimeImmutable('now', new DateTimeZone('America/Mexico_City')))->format('Y-m-d H:i:s')
        ];
    } catch (Throwable $error) {
        $guardarError = $connection->prepare(
            'UPDATE inventario_pronostico_clima SET Ultimo_Error=? WHERE ID_Config=1'
        );
        $detalle = mb_substr($error->getMessage(), 0, 255);
        $guardarError->bind_param('s', $detalle);
        $guardarError->execute();
        error_log('No se pudo actualizar el pronóstico de inventario: ' . (string) $error);
        $cacheActualizada = $connection->prepare(
            'SELECT Latitud, Longitud, Fecha_Clima, Temperatura_Maxima,
                Probabilidad_Precipitacion, Precipitacion_MM, Codigo_Clima,
                Actualizado, Intento_Actualizacion, Ultimo_Error,
                TIMESTAMPDIFF(SECOND, Actualizado, CURRENT_TIMESTAMP) AS Segundos_Desde_Actualizacion,
                TIMESTAMPDIFF(SECOND, Intento_Actualizacion, CURRENT_TIMESTAMP) AS Segundos_Desde_Intento
            FROM inventario_pronostico_clima WHERE ID_Config=1'
        );
        $cacheActualizada->execute();
        $cache = $cacheActualizada->get_result()->fetch_assoc() ?: $cache;
        $mismaFecha = $cache && $cache['Fecha_Clima'] === $hoy && $cache['Actualizado'] !== null &&
            abs((float) $cache['Latitud'] - (float) $configuracion['Latitud']) < 0.000001 &&
            abs((float) $cache['Longitud'] - (float) $configuracion['Longitud']) < 0.000001;
        return pronosticoDesdeCache($cache, $mismaFecha);
    }
}

function pronosticoDesdeCache(?array $cache, bool $mismoDia): array
{
    if (!$cache || !$mismoDia || $cache['Temperatura_Maxima'] === null) {
        return [
            'disponible' => false,
            'obsoleto' => false,
            'mensaje' => 'No se pudo consultar el clima de hoy; no se aplicó ningún ajuste meteorológico.'
        ];
    }
    return [
        'disponible' => true,
        'obsoleto' => true,
        'temperatura_maxima' => (float) $cache['Temperatura_Maxima'],
        'probabilidad_precipitacion' => (int) $cache['Probabilidad_Precipitacion'],
        'precipitacion_mm' => (float) $cache['Precipitacion_MM'],
        'codigo' => (int) $cache['Codigo_Clima'],
        'descripcion' => descripcionClima((int) $cache['Codigo_Clima']),
        'actualizado' => $cache['Actualizado'],
        'mensaje' => 'Se usa el último pronóstico guardado de hoy; no fue posible actualizarlo.'
    ];
}

function calcularAjusteClima(string $sensibilidad, ?array $clima, array $configuracion): float
{
    if (!$clima || empty($clima['disponible'])) {
        return 0.0;
    }
    if ($sensibilidad === 'bebida_fria' &&
        (float) $clima['temperatura_maxima'] >= (float) $configuracion['Umbral_Bebida_Fria']) {
        return (float) $configuracion['Ajuste_Bebida_Fria'];
    }
    if ($sensibilidad === 'bebida_caliente' &&
        (float) $clima['temperatura_maxima'] <= (float) $configuracion['Umbral_Bebida_Caliente']) {
        return (float) $configuracion['Ajuste_Bebida_Caliente'];
    }
    return 0.0;
}

function calcularDemandaInsumos(
    array $ventasUltimasSemanas,
    array $recetas,
    array $sensibilidades,
    array $configuracion,
    ?array $clima,
    float $ajusteEventos
): array {
    $demanda = [];
    foreach ($recetas as $receta) {
        $idProducto = (int) $receta['ID_Producto'];
        $idInsumo = (int) $receta['ID_Insumo'];
        $unidadesPromedio = (float) ($ventasUltimasSemanas[$idProducto] ?? 0) / 4;
        $cantidadReceta = (float) $receta['Cantidad'];
        $base = round($unidadesPromedio * $cantidadReceta, 3);
        $ajusteClima = calcularAjusteClima(
            $sensibilidades[$idProducto] ?? 'neutra',
            $clima,
            $configuracion
        );
        $porcentajeAjustado = max(0.0, min(200.0, $ajusteClima + $ajusteEventos));
        $ajustada = round($base * (1 + $porcentajeAjustado / 100), 3);
        if (!isset($demanda[$idInsumo])) {
            $demanda[$idInsumo] = ['base' => 0.0, 'ajustada' => 0.0];
        }
        $demanda[$idInsumo]['base'] += $base;
        $demanda[$idInsumo]['ajustada'] += $ajustada;
    }
    foreach ($demanda as &$insumo) {
        $insumo['base'] = round($insumo['base'], 3);
        $insumo['ajustada'] = round($insumo['ajustada'], 3);
        $insumo['porcentaje'] = $insumo['base'] > 0
            ? round(($insumo['ajustada'] - $insumo['base']) * 100 / $insumo['base'], 1)
            : 0.0;
    }
    unset($insumo);
    return $demanda;
}
