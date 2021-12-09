<?php
/**
 * This file is part of SwowCloud
 * @license  https://github.com/swow-cloud/music-server/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Collision;

use Coduo\ToString\StringConverter;
use SwowCloud\Highlighter\Highlighter;
use SwowCloud\Highlighter\Renderer\StandardLineRenderer;

class Handler
{
    private static Highlighter $highlighter;

    private static StandardLineRenderer $renderer;

    public const LEFT_PADDING = '    ';

    public static bool $bootStrap = false;

    public static function setupHandlers(int $errorTypes = E_ERROR): void
    {
        set_error_handler([__CLASS__, 'errorHandler'], $errorTypes);
        set_exception_handler([__CLASS__, 'exceptionHandler']);
    }

    protected static function bootStrap(): void
    {
        if (!self::$bootStrap) {
            self::$bootStrap = true;
            self::$highlighter = new Highlighter();
            self::$highlighter->setTheme(new HighlighterTheme());
            self::$renderer = self::$highlighter->lineRenderer;
        }
    }

    /**
     * @param $errorCode
     * @param $message
     * @param $file
     * @param $line
     *
     * @throws \Exception
     */
    public static function errorHandler($errorCode, $message, $file, $line): void
    {
        $error = match ($errorCode) {
            E_PARSE, E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR => 'Fatal Error',
            E_WARNING, E_USER_WARNING, E_COMPILE_WARNING, E_RECOVERABLE_ERROR => 'Warning',
            E_NOTICE, E_USER_NOTICE => 'Notice',
            E_STRICT => 'Strict',
            E_DEPRECATED, E_USER_DEPRECATED => 'Deprecated',
            default => 'Unknown error',
        };

        echo self::buildBaseError($error, $message, $file, $line);
    }

    /**
     * @param \Exception $exception
     *
     * @throws \Exception
     */
    public static function exceptionHandler(\Throwable $exception): void
    {
        self::bootStrap();
        echo PHP_EOL;
        echo self::buildBaseError(get_class($exception), $exception->getMessage(), $exception->getFile(), $exception->getLine());
        echo self::buildStackTrace($exception->getTrace());
        echo PHP_EOL;
    }

    /**
     * @param $string
     */
    private static function printText($string, null|array $styleCode = []): string
    {
        return sprintf('%s%s%s', self::$renderer->buildStyleCode($styleCode), $string, self::$renderer::ANSI_RESET_STYLES);
    }

    /**
     * @param $title
     * @param $message
     * @param $file
     * @param $line
     *
     * @throws \Exception
     */
    private static function buildBaseError($title, $message, $file, $line): string
    {
        $output = self::LEFT_PADDING . self::printText(sprintf(' %s ', $title), ['bg_red', 'white', 'bold']);
        $output .= ' : ';
        $output .= self::printText($message, ['yellow']);
        $output .= PHP_EOL . PHP_EOL;
        $output .= self::LEFT_PADDING . 'at ';
        $output .= self::printText($file, ['green']);
        $output .= PHP_EOL;

        $output .= self::LEFT_PADDING . ' ' . str_replace("\n", "\n " . self::LEFT_PADDING, self::$highlighter->getSnippet($file, $line, 9));

        return $output;
    }

    private static function buildStackTrace(array $trace = []): string
    {
        $output = '';

        if ($trace) {
            $output .= self::printText("\n" . self::LEFT_PADDING . 'Call trace: ', ['yellow']);
        }

        foreach (array_reverse($trace) as $i => $traceItem) {
            $output .= PHP_EOL . PHP_EOL . self::LEFT_PADDING;
            $output .= sprintf('%s%d %s', self::$renderer->buildStyleCode(['blue']), $i + 1, self::$renderer::ANSI_RESET_STYLES);
            $output .= self::LEFT_PADDING;
            $output .= self::printText($traceItem['class'] ?? '', ['yellow']);
            $output .= self::printText($traceItem['type'] ?? '', ['white']);
            $output .= self::printText($traceItem['function'], ['light_gray']);

            $output .= '(';

            $argArray = [];

            foreach ($traceItem['args'] as $arg) {
                if (is_string($arg)) {
                    $item = sprintf('"%s"', $arg);
                } else {
                    $item = (string) (new StringConverter($arg));
                }

                $argArray[] = match (gettype($arg)) {
                    'string' => self::printText($item, ['green']),
                    'integer' => self::printText($item, ['blue']),
                    default => self::printText($item, ['light_cyan']),
                };
            }

            $output .= implode(',', $argArray);

            $output .= ')';

            $output .= PHP_EOL . self::LEFT_PADDING . self::LEFT_PADDING . '  ';
            /** @noinspection PhpIdempotentOperationInspection */
            $file = $traceItem['file'] ?? '';
            $line = $traceItem['line'] ?? '';
            $output .= self::printText($file . ' : ' . $line, ['green']);
        }

        return $output;
    }
}
