<?php

declare(strict_types=1);

use pocketmine\lang\Translatable;

require dirname(__DIR__) . '/vendor/autoload.php';

const SHARED_HEADER = <<<'HEADER'
<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\language;

HEADER;

const FACTORY_HEADER = SHARED_HEADER . <<<'HEADER'

use pocketmine\lang\Translatable;

/**
 * This class contains factory methods for all the translations known to CPlot.
 * This class is generated automatically, do NOT modify it by hand.
 *
 * @internal
 */
final class KnownTranslationFactory {


HEADER;

const KEY_HEADER = SHARED_HEADER . <<<'HEADER'

/**
 * This class contains constants for all the translations known to CPlot.
 * This class is generated automatically, do NOT modify it by hand.
 *
 * @internal
 */
final class KnownTranslationKeys {


HEADER;

$fileContents = file(dirname(__DIR__) . "/resources/language/en_us.ini", FILE_SKIP_EMPTY_LINES);
if ($fileContents === false) {
    fwrite(STDERR, "Missing language files!\n");
    exit(1);
}

generateClasses($fileContents);

/**
 * @param array<string, string> $fileContents
 * @return void
 */
function generateClasses(array $fileContents) : void {
    $factoryClass = FACTORY_HEADER;
    $keyClass = KEY_HEADER;
    $translatableClassName = (new ReflectionClass(Translatable::class))->getShortName();
    $parameters = [];
    foreach ($fileContents as $line) {
        if (str_starts_with($line, ";")) {
            preg_match_all("/{%(?P<digit>\d+)}\s=\s(?P<name>[^,()]+)/", $line, $matches);
            foreach ($matches["name"] as $index => $name) {
                $parameters[$matches["digit"][$index]] = parameterify($name);
            }
            continue;
        }
        preg_match("/^(.+?)\s*=\s*(.+)$/", $line, $matches);
        if (!isset($matches[1])) {
            continue;
        }
        $key = $matches[1];
        $constantName = constantify($key);
        ksort($parameters, SORT_NUMERIC);
        $factoryClass .= "    public static function " . 
            functionify($key) . 
            "(" . implode(", ", array_map(fn(string $paramName) => "$translatableClassName|string \$$paramName", $parameters)) . ") : $translatableClassName {\n" .
            "        return new $translatableClassName(KnownTranslationKeys::$constantName, [";
        foreach($parameters as $parameterKey => $parameterName){
            $factoryClass .= "\n            $parameterKey => \$$parameterName,";
        }
        if(count($parameters) > 0){
            $factoryClass .= "\n        ";
            $parameters = [];
        }
        $factoryClass .= "]);\n    }\n\n";
        
        $keyClass .= "    public const " . $constantName . " = \"" . $key . "\";\n";
    }
    $factoryClass .= "}";
    $keyClass .= "}";
    file_put_contents(dirname(__DIR__) . "/src/ColinHDev/CPlot/language/KnownTranslationFactory.php", $factoryClass);
    file_put_contents(dirname(__DIR__) . "/src/ColinHDev/CPlot/language/KnownTranslationKeys.php", $keyClass);
}

function constantify(string $name) : string {
    return strtoupper(str_replace([".", "-"], "_", $name));
}

function functionify(string $name) : string {
    return str_replace([".", "-"], "_", $name);
}

function parameterify(string $name) : string {
    return str_replace([" ", ".", "-"], "_", trim($name));
}