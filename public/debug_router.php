<?php
// u0421u043au0440u0438u043fu0442 u0434u043bu044f u043eu0442u043bu0430u0434u043au0438 u043cu0430u0440u0448u0440u0443u0442u0438u0437u0430u0446u0438u0438

// u041fu043eu0434u043au043bu044eu0447u0430u0435u043c u043du0435u043eu0431u0445u043eu0434u0438u043cu044bu0435 u0444u0430u0439u043bu044b
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/config.php';

// u041fu043eu0434u043au043bu044eu0447u0430u0435u043c u043au043bu0430u0441u0441 Router
use App\Core\Router;
use App\Core\Request;
use App\Core\Response;

echo "<h1>u041eu0442u043bu0430u0434u043au0430 u043cu0430u0440u0448u0440u0443u0442u0438u0437u0430u0446u0438u0438</h1>";

// u0421u043eu0437u0434u0430u0435u043c u043eu0431u044au0435u043au0442u044b u0437u0430u043fu0440u043eu0441u0430 u0438 u043eu0442u0432u0435u0442u0430
$request = new Request();
$response = new Response();

// u0421u043eu0437u0434u0430u0435u043c u043eu0431u044au0435u043au0442 u043cu0430u0440u0448u0440u0443u0442u0438u0437u0430u0442u043eu0440u0430
$router = new Router($request, $response);

// u0417u0430u0433u0440u0443u0436u0430u0435u043c u043cu0430u0440u0448u0440u0443u0442u044b
require_once dirname(__DIR__) . '/config/routes.php';

// u041fu043eu043bu0443u0447u0430u0435u043c u0442u0435u043au0443u0449u0438u0439 u043fu0443u0442u044c u0438 u043cu0435u0442u043eu0434
$path = $request->getPath();
$method = $request->method();

echo "<h2>u0422u0435u043au0443u0449u0438u0439 u0437u0430u043fu0440u043eu0441</h2>";
echo "<p><strong>u041fu0443u0442u044c:</strong> {$path}</p>";
echo "<p><strong>u041cu0435u0442u043eu0434:</strong> {$method}</p>";

// u041fu043eu043bu0443u0447u0430u0435u043c u0432u0441u0435 u0437u0430u0440u0435u0433u0438u0441u0442u0440u0438u0440u043eu0432u0430u043du043du044bu0435 u043cu0430u0440u0448u0440u0443u0442u044b
$routes = $router->getRoutes();

echo "<h2>u0417u0430u0440u0435u0433u0438u0441u0442u0440u0438u0440u043eu0432u0430u043du043du044bu0435 u043cu0430u0440u0448u0440u0443u0442u044b</h2>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>u041cu0435u0442u043eu0434</th><th>u041fu0443u0442u044c</th><th>u041eu0431u0440u0430u0431u043eu0442u0447u0438u043a</th><th>u0421u043eu0432u043fu0430u0434u0435u043du0438u0435</th></tr>";

// u041fu0440u043eu0432u0435u0440u044fu0435u043c u043au0430u0436u0434u044bu0439 u043cu0430u0440u0448u0440u0443u0442
foreach ($routes as $routeMethod => $routePaths) {
    foreach ($routePaths as $routePath => $callback) {
        // u041fu0440u043eu0432u0435u0440u044fu0435u043c, u0441u043eu0432u043fu0430u0434u0430u0435u0442 u043bu0438 u043cu0430u0440u0448u0440u0443u0442 u0441 u0442u0435u043au0443u0449u0438u043c u0437u0430u043fu0440u043eu0441u043eu043c
        $isMatch = ($routeMethod === $method && $router->matchRoute($routePath, $path));
        
        // u041fu043eu043bu0443u0447u0430u0435u043c u0438u043du0444u043eu0440u043cu0430u0446u0438u044e u043e u043au043eu043bu043bu0431u044du043au0435
        $callbackInfo = '';
        if (is_array($callback)) {
            if (is_object($callback[0])) {
                $callbackInfo = get_class($callback[0]) . '::' . $callback[1];
            } else {
                $callbackInfo = $callback[0] . '::' . $callback[1];
            }
        } elseif (is_callable($callback)) {
            $callbackInfo = 'Closure';
        } else {
            $callbackInfo = 'Unknown';
        }
        
        echo "<tr style='" . ($isMatch ? "background-color: #d4edda;" : "") . "'>";
        echo "<td>{$routeMethod}</td>";
        echo "<td>{$routePath}</td>";
        echo "<td>{$callbackInfo}</td>";
        echo "<td>" . ($isMatch ? "u0414u0430" : "u041du0435u0442") . "</td>";
        echo "</tr>";
    }
}

echo "</table>";

// u0422u0435u0441u0442u0438u0440u0443u0435u043c u0440u0430u0437u0440u0435u0448u0435u043du0438u0435 u043cu0430u0440u0448u0440u0443u0442u0430
echo "<h2>u0422u0435u0441u0442 u0440u0430u0437u0440u0435u0448u0435u043du0438u044f u043cu0430u0440u0448u0440u0443u0442u0430</h2>";

try {
    // u041fu044bu0442u0430u0435u043cu0441u044f u0440u0430u0437u0440u0435u0448u0438u0442u044c u043cu0430u0440u0448u0440u0443u0442, u043du043e u043du0435 u0432u044bu043fu043eu043bu043du044fu0435u043c u0435u0433u043e
    $matchedRoute = $router->findMatchingRoute($path, $method);
    
    if ($matchedRoute) {
        echo "<div style='color: green;'>u041du0430u0439u0434u0435u043d u043fu043eu0434u0445u043eu0434u044fu0449u0438u0439 u043cu0430u0440u0448u0440u0443u0442 u0434u043bu044f {$method} {$path}</div>";
        
        // u041fu043eu043bu0443u0447u0430u0435u043c u0438u043du0444u043eu0440u043cu0430u0446u0438u044e u043e u043au043eu043bu043bu0431u044du043au0435
        $callback = $matchedRoute['callback'];
        $callbackInfo = '';
        if (is_array($callback)) {
            if (is_object($callback[0])) {
                $callbackInfo = get_class($callback[0]) . '::' . $callback[1];
            } else {
                $callbackInfo = $callback[0] . '::' . $callback[1];
            }
        } elseif (is_callable($callback)) {
            $callbackInfo = 'Closure';
        } else {
            $callbackInfo = 'Unknown';
        }
        
        echo "<p><strong>u041eu0431u0440u0430u0431u043eu0442u0447u0438u043a:</strong> {$callbackInfo}</p>";
        echo "<p><strong>u041fu0430u0440u0430u043cu0435u0442u0440u044b:</strong> " . print_r($matchedRoute['params'], true) . "</p>";
    } else {
        echo "<div style='color: red;'>u041du0435 u043du0430u0439u0434u0435u043d u043fu043eu0434u0445u043eu0434u044fu0449u0438u0439 u043cu0430u0440u0448u0440u0443u0442 u0434u043bu044f {$method} {$path}</div>";
    }
} catch (Exception $e) {
    echo "<div style='color: red;'>u041eu0448u0438u0431u043au0430 u043fu0440u0438 u0440u0430u0437u0440u0435u0448u0435u043du0438u0438 u043cu0430u0440u0448u0440u0443u0442u0430: {$e->getMessage()}</div>";
}

// u0414u043eu0431u0430u0432u043bu044fu0435u043c u0444u043eu0440u043cu0443 u0434u043bu044f u0442u0435u0441u0442u0438u0440u043eu0432u0430u043du0438u044f u043cu0430u0440u0448u0440u0443u0442u043eu0432
echo "<h2>u0422u0435u0441u0442u0438u0440u043eu0432u0430u043du0438u0435 u043cu0430u0440u0448u0440u0443u0442u043eu0432</h2>";
echo "<form method='get' action=''>";
echo "<div style='margin-bottom: 10px;'><label>u041fu0443u0442u044c: <input type='text' name='test_path' value='{$path}'></label></div>";
echo "<div style='margin-bottom: 10px;'><label>u041cu0435u0442u043eu0434: 
    <select name='test_method'>
        <option value='GET' " . ($method === 'GET' ? 'selected' : '') . ">GET</option>
        <option value='POST' " . ($method === 'POST' ? 'selected' : '') . ">POST</option>
        <option value='PUT' " . ($method === 'PUT' ? 'selected' : '') . ">PUT</option>
        <option value='DELETE' " . ($method === 'DELETE' ? 'selected' : '') . ">DELETE</option>
    </select>
</label></div>";
echo "<div style='margin-bottom: 10px;'><button type='submit'>u041fu0440u043eu0432u0435u0440u0438u0442u044c</button></div>";
echo "</form>";

// u0414u043eu0431u0430u0432u043bu044fu0435u043c u0441u0441u044bu043bu043au0438 u043du0430 u0434u0440u0443u0433u0438u0435 u0441u043au0440u0438u043fu0442u044b u043eu0442u043bu0430u0434u043au0438
echo "<h2>u0414u0440u0443u0433u0438u0435 u0438u043du0441u0442u0440u0443u043cu0435u043du0442u044b u043eu0442u043bu0430u0434u043au0438</h2>";
echo "<ul>";
echo "<li><a href='/view_logs.php'>u041fu0440u043eu0441u043cu043eu0442u0440 u043bu043eu0433u043eu0432</a></li>";
echo "<li><a href='/check_products_table.php'>u041fu0440u043eu0432u0435u0440u043au0430 u0442u0430u0431u043bu0438u0446u044b products</a></li>";
echo "<li><a href='/debug_product_form.php'>u041eu0442u043bu0430u0434u043au0430 u0444u043eu0440u043cu044b u0434u043eu0431u0430u0432u043bu0435u043du0438u044f u043fu0440u043eu0434u0443u043au0442u0430</a></li>";
echo "</ul>";

// u0414u043eu0431u0430u0432u043bu044fu0435u043c u0441u0441u044bu043bu043au0443 u043du0430 u0441u0442u0440u0430u043du0438u0446u0443 u043fu0440u043eu0434u0443u043au0442u043eu0432
echo "<p><a href='/seller/products'>u0412u0435u0440u043du0443u0442u044cu0441u044f u043a u043fu0440u043eu0434u0443u043au0442u0430u043c</a></p>";
