<?php
declare(strict_types=1);

namespace App\Controllers;

/**
 * Основной контроллер для парсинга лог-файла
 * @package App\Controllers
 */
class LogParser
{
    private const PATTERN = "/(\S+) (\S+) (\S+) \[([^:]+):(\d+:\d+:\d+) ([^\]]+)\] \"(\S+) (.*?) (\S+)\" (\S+) (\S+) (\".*?\") (\".*?\")/";
    private const URL         = 'url';
    private const STATUS_CODE = 'status_code';
    private const TRAFFIC     = 'traffic';
    private const USER_AGENT  = 'user_agent';
    //TODO подумать над лучшей реализацией поиска ботов из строки
    private const GOOGLE_BOT  = 'googlebot';
    private const YANDEX_BOT  = 'yandex.com/bots';
    private const MAIL_BOT    = 'mail.ru_bot';
    private const RAMBLER_BOT = 'stackrambler';
    private const YAHOO_BOT   = 'ysearch/slurp';
    private const BING_BOT    = 'bingbot';
    private const GOOGLE  = 'Google';
    private const YANDEX  = 'Yandex';
    private const MAIL    = 'Mail';
    private const RAMBLER = 'Rambler';
    private const YAHOO   = 'Yahoo';
    private const BING    = 'Bing';

    /**
     * Основной метод для старта парсинга файла, принимает на вход путь до файла
     * @param $filePath
     * @return string
     */
    public function parseFile($filePath): string
    {
        if (!$this->_isValidString($filePath)) {
            return $this->_error('file path is empty');
        }
        if (!$this->_isFileReadable($filePath)) {
            return $this->_error('file error');
        }

        $totalCounter = 0;
        $trafficCounter = 0;
        $uniqueUrls = [];
        $statusCodes = [];
        $crawlers = [
            self::GOOGLE => 0,
            self::YANDEX => 0,
            self::RAMBLER => 0,
            self::BING    => 0,
            self::YAHOO   => 0,
            self::MAIL    => 0,
        ];

        $file = fopen($filePath, 'r');
        if (!is_resource($file)) {
            return $this->_error('File opening error');
        }
        while (!feof($file)) {
            $fileRow = fgets($file);
            if (!$this->_isValidString($fileRow)) {
                continue;
            }

            $totalCounter++;
            $record = $this->_parseRecordFromFile($fileRow);
            if (count($record) === 0) {
                continue;
            }

            if (!in_array($record[self::URL], $uniqueUrls)) {
                $uniqueUrls[] = $record[self::URL];
            }

            if (array_key_exists($record[self::STATUS_CODE], $statusCodes)) {
                $statusCodes[$record[self::STATUS_CODE]]++;
            } else {
                $statusCodes[$record[self::STATUS_CODE]] = 1;
            }

            if ($record[self::STATUS_CODE] === 200) {
                $trafficCounter += $record[self::TRAFFIC];
            }

            $searchSystem = $this->_searchBotPicker($record[self::USER_AGENT]);
            if ($searchSystem !== null && array_key_exists($searchSystem, $crawlers)) {
                $crawlers[$searchSystem]++;
            }
        }

        return $this->_success([
            'views'        => $totalCounter,
            'urls'         => count($uniqueUrls),
            'traffic'      => $trafficCounter,
            'crawlers'     => $crawlers,
            'status_codes' => $statusCodes
        ]);
    }

    /**
     * Метод для парсинга строки из файла
     * @param string $fileRow
     * @return array
     */
    private function _parseRecordFromFile(string $fileRow): array {
        $parsedFileRow = [];
        preg_match (self::PATTERN, $fileRow, $parsedFileRow);
        if (count($parsedFileRow) === 14) {
            return [
                self::URL         => $parsedFileRow[8],
                self::STATUS_CODE => (int) $parsedFileRow[10],
                self::TRAFFIC     => (int) $parsedFileRow[11],
                self::USER_AGENT  => strtolower($parsedFileRow[13]),
            ];
        }
        return [];
    }

    /**
     * Метод для поиска конкретного бота
     * @param string $userAgent
     * @return string|null
     */
    private function _searchBotPicker(string $userAgent): ?string {
        if (strpos($userAgent, self::GOOGLE_BOT) !== false) {
            return self::GOOGLE;
        } elseif (strpos($userAgent, self::YANDEX_BOT) !== false) {
            return self::YANDEX;
        } elseif (strpos($userAgent, self::MAIL_BOT) !== false) {
            return self::MAIL;
        } elseif (strpos($userAgent, self::RAMBLER_BOT) !== false) {
            return self::RAMBLER;
        } elseif (strpos($userAgent, self::YAHOO_BOT) !== false) {
            return self::YAHOO;
        } elseif (strpos($userAgent, self::BING_BOT) !== false) {
            return self::BING;
        }
        return null;
    }

    /**
     * @param string $message
     * @return string
     */
    private function _error(string $message): string
    {
        return "Error $message!";
    }

    /**
     * @param array $result
     * @return string
     */
    private function _success(array $result): string
    {
        return json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * @param string $value
     * @return bool
     */
    private function _isValidString(string $value): bool
    {
        return $value !== '';
    }

    /**
     * @param string $pathToFile
     * @return bool
     */
    private function _isFileReadable(string $pathToFile): bool {
        return is_readable($pathToFile);
    }
}