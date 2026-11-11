<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/core/bootstrap.php';
require __DIR__ . '/support/HttpTest.php';

use App\Core\Database;
use App\Services\LicenseJourneyService;
use App\Services\LicenseProviderService;

$base = rtrim($argv[1] ?? 'http://127.0.0.1/DIRI/public', '/');
$url = parse_url($base);
if (!$url || !in_array($url['scheme'] ?? '', ['http', 'https'], true)
    || !in_array($url['host'] ?? '', ['127.0.0.1', 'localhost', '[::1]'], true)
    || isset($url['user']) || isset($url['pass']) || isset($url['query']) || isset($url['fragment'])) {
    fwrite(STDERR, "Usa una URL local.\n"); exit(2);
}

try {
    $service = new LicenseJourneyService();
    $profile = $service->defaults('primera');
    $profile['edad'] = '21mas';
    $profile['tengo'] = ['ci' => '1', 'medico' => '1'];
    $profile['zona'] = 'miraflores';
    $now = new DateTimeImmutable('2026-10-21 12:00:00', new DateTimeZone('America/La_Paz'));
    $plan = $service->plan($profile, $now);
    ensure($plan['next']['key'] === 'habilidades' && !in_array('medico', array_column($plan['actions'], 'key'), true),
        'Un médico vigente ya obtenido se omite y se recomienda la autoescuela.');
    ensure(in_array('registro_medico', array_column($plan['actions'], 'key'), true),
        'Registro médico sin confirmar no obliga a repetir la evaluación.');
    ensure($plan['providers'][0]['matches_zone'] && $plan['providers'][0]['zone'] === 'miraflores',
        'Se priorizan autoescuelas de la zona preferida.');
    $expired = $profile;
    $expired['vencimientos']['medico'] = '2026-10-20';
    $expiredPlan = $service->plan($expired, $now);
    ensure($expiredPlan['next']['key'] === 'medico' && count($expiredPlan['warnings']) === 1,
        'Un documento marcado pero vencido vuelve a la ruta.');
    $expiresToday = $profile;
    $expiresToday['vencimientos']['medico'] = '2026-10-21';
    ensure($service->plan($expiresToday, $now)['next']['key'] === 'habilidades',
        'La fecha de hoy no se confunde con una fecha ya pasada.');
    $all = $profile;
    $all['tengo'] = array_fill_keys(array_keys(LicenseJourneyService::DOCUMENTS), '1');
    $all['medico_registro'] = 'si';
    $ready = $service->plan($all, $now);
    ensure($ready['ready'] && $ready['next']['key'] === 'segip' && count($ready['actions']) === 1,
        'Con documentos preparados se conserva solo la presentación en SEGIP.');
    $all['medico_registro'] = 'pendiente';
    ensure(!$service->plan($all, $now)['ready'] && $service->plan($all, $now)['next']['key'] === 'registro_medico',
        'Registro médico pendiente impide afirmar que ya puede presentar la solicitud.');
    $renewal = $service->defaults('renovacion');
    $renewal['tengo'] = ['ci' => '1', 'medico' => '1'];
    $renewal['medico_registro'] = 'si';
    $renewal['renovacion_larga'] = 'no';
    ensure($service->plan($renewal, $now)['next']['key'] === 'antecedentes',
        'Renovación sin la condición larga no añade la autoescuela.');
    $renewal['renovacion_larga'] = 'si';
    ensure($service->plan($renewal, $now)['next']['key'] === 'habilidades',
        'Renovación larga sin certificado sí añade habilidades.');
    $renewal['renovacion_larga'] = 'no_se';
    ensure($service->plan($renewal, $now)['next']['key'] === 'confirmar_habilidades',
        'Una condición desconocida exige confirmar antes de pagar una evaluación.');
    foreach (['no_se', 'menor18', '18a20'] as $age) {
        $limited = $profile;
        $limited['edad'] = $age;
        $limited['categoria'] = 'A';
        ensure($service->plan($limited, $now)['blocked'] !== null && $service->plan($limited, $now)['actions'] === [],
            'No se genera una ruta incompatible con la edad: ' . $age);
    }
    foreach ([['categoria' => 'B'], ['categoria' => ['P']], ['tengo' => 'medico'],
        ['tengo' => ['inventado' => '1']], ['tengo' => ['medico' => ['1']]],
        ['vencimientos' => ['medico' => '2026-02-30']], ['vencimientos' => ['medico' => "2026\0xx-xx"]],
        ['zona' => '<script>'], ['medico_registro' => ['si']]] as $invalid) {
        ensure($service->validate(array_replace($profile, $invalid), 'primera')['errors'] !== [],
            'Entrada inválida rechazada: ' . implode(',', array_keys($invalid)));
    }
    $providers = new LicenseProviderService();
    ensure(count($providers->data()['providers']) === 25 && count($providers->options('medico', 'todas')) === 13
        && count($providers->options('escuela', 'todas')) === 12, 'Directorio oficial: 13 centros médicos y 12 autoescuelas de La Paz.');
    ensure(count(array_filter($providers->options('escuela', 'achumani'), static fn($row) => $row['matches_zone'])) === 0,
        'Una zona sin coincidencias no recibe una recomendación inventada.');

    $pdo = Database::connection();
    $before = $pdo->query('SELECT COUNT(*) FROM rutas_guardadas')->fetchColumn();
    $browser = new TestBrowser();
    [$status, $html] = $browser->request($base, '/mi-ruta');
    ensure($status === 200 && strpos($html, 'name="tengo[medico]"') !== false,
        'Formulario gratuito con casillas disponible para visitantes.');
    preg_match('/name="version" value="([a-f0-9]{64})"/', $html, $matches);
    $request = $profile + ['_token' => token($html), 'version' => $matches[1]];
    $withoutToken = $request; unset($withoutToken['_token']);
    ensure($browser->request($base, '/mi-ruta', $withoutToken)[0] === 419, 'Personalizar requiere CSRF.');
    ensure($browser->request($base, '/mi-ruta', array_replace($request, ['version' => str_repeat('0', 64)]))[0] === 409,
        'Un formulario de reglas anteriores se rechaza.');
    ensure($browser->request($base, '/mi-ruta', array_replace($request, ['zona' => ['miraflores']]))[0] === 422,
        'Tipos inválidos por HTTP no producen un error interno.');
    ensure($browser->request($base, '/mi-ruta', $request)[0] === 303, 'Calcular ruta redirige al resultado.');
    [$status, $html] = $browser->request($base, '/mi-ruta');
    ensure($status === 200 && strpos($html, 'data-next-step="habilidades"') !== false
        && strpos($html, 'data-pending-step="medico"') === false,
        'Resultado HTTP omite médico y recomienda autoescuela.');
    ensure(strpos($html, 'Coincide con tu zona') !== false, 'Resultado explica su criterio de zona.');
    $other = new TestBrowser();
    ensure(strpos($other->request($base, '/mi-ruta')[1], 'data-next-step=') === false,
        'Otra sesión no ve las declaraciones personales.');
    ensure(strpos($browser->request($base, '/mi-ruta?modalidad=renovacion')[1], 'data-next-step=') === false,
        'Las declaraciones no se trasladan automáticamente entre modalidades.');
    ensure($browser->request($base, '/mi-ruta?modalidad[]=primera')[0] === 422,
        'Modalidad con tipo inválido controlada.');
    ensure($browser->request($base, '/mi-ruta/reiniciar')[0] === 405, 'Reiniciar no permite GET.');
    ensure($browser->request($base, '/mi-ruta/reiniciar', ['modalidad' => 'primera'])[0] === 419, 'Reiniciar requiere CSRF.');
    ensure($browser->request($base, '/mi-ruta/reiniciar', ['modalidad' => 'primera', '_token' => token($html)])[0] === 303,
        'Se pueden borrar las declaraciones de la sesión.');
    ensure(strpos($browser->request($base, '/mi-ruta')[1], 'data-next-step=') === false,
        'Reiniciar elimina el resultado anterior.');
    ensure($pdo->query('SELECT COUNT(*) FROM rutas_guardadas')->fetchColumn() === $before,
        'La beta no escribe rutas guardadas ni datos de cuenta.');
    echo "OK: personalización de licencia y opciones verificadas comprobadas.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'ERROR: ' . $exception->getMessage() . "\n"); exit(1);
}
