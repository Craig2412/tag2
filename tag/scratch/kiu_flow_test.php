<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$kiuClient = app(\App\Services\Kiu\KiuClient::class);

echo "=== Buscando vuelos disponibles ===\n";

$results = [];
$routes = [
    ['CCS', 'MIA', '2026-06-20'],
    ['PTY', 'BOG', '2026-06-20'],
    ['PTY', 'MIA', '2026-06-20'],
    ['PTY', 'CUN', '2026-06-20'],
];

foreach ($routes as [$origin, $dest, $date]) {
    foreach (['CM', 'AA', 'LA', 'AV'] as $airline) {
        $r = $kiuClient->availability(['context' => [
            'origin' => $origin, 'destination' => $dest,
            'departure_date' => $date, 'airline' => $airline, 'max_responses' => 3,
        ]]);
        $b = $r['response']['body'];
        $hasFlights = str_contains($b, 'FlightSegment');
        echo "  {$airline} {$origin}->{$dest}: " . ($hasFlights ? "ENCONTRADO" : "-") . "\n";
        if ($hasFlights) {
            $results[$airline] = ['data' => $r['response']['data'], 'origin' => $origin, 'dest' => $dest];
        }
    }
}

if (empty($results)) {
    echo "\nEl sandbox KIU no tiene vuelos en estas rutas/fechas.\n";
    echo "Esto es normal - disponibilidad limitada en sandbox.\n";
    echo "\nSobre tu error 22030 'ERROR EN COTIZACION':\n";
    echo "Ocurre porque G6 (GOL) no opera PTY->MIA y/o el vuelo 201 no existe.\n";
    echo "Siempre obten availability primero y usa datos reales para pricing.\n";
    exit(0);
}

// Primer vuelo disponible
$firstAirline = array_key_first($results);
$d = $results[$firstAirline];
$data = $d['data'];
$opts = $data['OriginDestinationInformation']['OriginDestinationOptions']['OriginDestinationOption'] ?? null;
if (is_array($opts) && isset($opts[0])) $opt = $opts[0]; else $opt = $opts;
$seg = $opt['FlightSegment'] ?? null;
if (is_array($seg) && isset($seg[0])) $seg = $seg[0];

$al = $seg['MarketingAirline']['@attributes']['Code'];
$fl = $seg['@attributes']['FlightNumber'];
$or = $seg['DepartureAirport']['@attributes']['LocationCode'];
$ds = $seg['ArrivalAirport']['@attributes']['LocationCode'];
$dd = $seg['@attributes']['DepartureDateTime'];
$ad = $seg['@attributes']['ArrivalDateTime'];

$cls = 'Y';
foreach ($seg['BookingClassAvail'] ?? [] as $c) {
    if ((int)($c['@attributes']['ResBookDesigQuantity'] ?? 0) > 0) {
        $cls = $c['@attributes']['ResBookDesigCode']; break;
    }
}

echo "\n=== PRICING: {$al}{$fl} {$or}->{$ds} (clase {$cls}) ===\n";
echo "Salida: {$dd} | Llegada: {$ad}\n\n";

$pr = $kiuClient->pricing(['context' => [
    'currency' => 'USD', 'adults' => 1,
    'segment' => [
        'origin' => $or, 'destination' => $ds,
        'departure_datetime' => $dd, 'arrival_datetime' => $ad,
        'flight_number' => $fl, 'booking_class' => $cls,
        'marketing_airline' => $al,
    ],
]]);

$pBody = $pr['response']['body'];
$pData = $pr['response']['data'] ?? [];
echo "Status: {$pr['response']['status']} | Body: " . strlen($pBody) . " bytes\n";

if (isset($pData['PricedItineraries'])) {
    $itin = $pData['PricedItineraries']['PricedItinerary']['AirItineraryPricingInfo'] ?? [];
    $total = $itin['ItinTotalFare']['TotalFare']['@attributes']['Amount'] ?? '?';
    $base  = $itin['ItinTotalFare']['BaseFare']['@attributes']['Amount'] ?? '?';
    echo "PRECIO: Base={$base} Total={$total}\n";
} elseif (isset($pData['Error'])) {
    echo "KIU: {$pData['Error']['ErrorCode']} - {$pData['Error']['ErrorMsg']}\n";
} else {
    echo $pBody . "\n";
}
