<?php
const DS = DIRECTORY_SEPARATOR;

if (!function_exists('vamp')) {
    function vamp(mixed ...$VARDUMP): void
    {
        echo "<mark style='background-color: transparent'><pre style='overflow-x: auto;font-size:10px; border:1px inset orangered;background-color:#e1e1e1;text-align: left;' dir='ltr'>";

        for ($i = 1; $i <= count($VARDUMP); $i++) {
            var_dump($VARDUMP[$i-1]);
            if($i != count($VARDUMP)){
                echo "<hr/>";
            }
        }
        echo "</pre></mark>";
    }
}
if (!function_exists('generateUUID')) {
    function generateUUID($separator = '-') {
        return sprintf("%04x%04x$separator%04x$separator%04x$separator%04x$separator%04x%04x%04x",
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}

if (!function_exists('env')) {
    function env($key, $default = null): mixed
    {
        $envFilePath = realpath(__DIR__.'/../.env');
        if ($envFilePath) {
            $envFile = @file_get_contents($envFilePath);
            $envVariables = [];

            foreach (explode("\n", $envFile) as $line) {
                $segments = explode('=', $line, 2);
                if (count($segments) === 2 && !empty(trim($segments[0]))) {
                    $envVariables[$segments[0]] = $segments[1];
                }
            }
            if (isset($envVariables[$key])) {
                if (gettype($envVariables[$key]) === 'string') {
                    $envVariables[$key] = str_replace("\n", "", trim($envVariables[$key]));
                }
            }

            return $envVariables[$key] ?? $default;
        } else {
            return null;
        }
    }
}

if (!function_exists('config')) {
    function config($key = null, $default = null): mixed
    {
        if (!$key) {
            return $default;
        }

        $exp = explode('.', $key);
        $fileName = $exp[0];
        $configPath = base_path('config/'.$fileName.'.php');

        if (!file_exists($configPath)) {
            return $default;
        }

        $configFile = include $configPath;

        if (!is_array($configFile)) {
            return $default;
        }

        array_shift($exp);

        if ($exp) {
            $result = findNested(implode('.', $exp), $configFile);
            return gettype($result) === 'string' ? trim($result) : $result;
        } else {
            return $configFile;
        }
    }
}

if (!function_exists('findNested')) {
    function findNested(string $keyString, array $array): mixed
    {
        $keys = explode('.', $keyString);
        $value = $array;

        foreach ($keys as $key) {
            if (is_array($value) && isset($value[$key])) {
                $value = $value[$key];
            } else {
                return null;
            }
        }

        return $value;
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $path = str_replace('/', '\\', $path);
        return join(DS, [__DIR__.'\..', $path]);
    }
}

if (!function_exists('view')) {
    function view($viewName, $data = [])
    {
        extract($data); // Extract data into variables

        if (str_contains($viewName, "::")) {
            list($module, $viewPath) = explode("::", $viewName);
            $path = base_path('/modules/'.$module.'/resources/views/'.$viewPath.'.php');
        } else {
            $path = base_path('/resources/views/'.$viewName.'.php');
        }

        include_once $path;
    }
}
