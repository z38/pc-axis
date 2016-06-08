<?php

namespace Z38\PcAxis;

use RuntimeException;
use stdClass;

/**
 * PC-Axis (PX) file reader
 */
class Px
{
    const DEFAULT_CHARSET = '437';

    /**
     * @var string
     */
    private $path;

    /**
     * @var resource
     */
    private $handle;

    /**
     * @var array
     */
    private $keywords;

    /**
     * @var array
     */
    private $data;

    /**
     * @var int
     */
    private $dataOffset;

    /**
     * @var string
     */
    private $charset;

    /**
     * @var string
     */
    private $codepage;

    /**
     * Constructor
     *
     * @param string $path path to your PX file
     */
    public function __construct($path)
    {
        $this->path = $path;
        $this->charset = self::DEFAULT_CHARSET;
    }

    public function variables()
    {
        return array_merge($this->keyword('STUB')->values, $this->keyword('HEADING')->values);
    }

    /**
     * @param string $variable
     *
     * @return array
     */
    public function values($variable)
    {
        foreach ($this->keywordList('VALUES') as $keyword) {
            if ($keyword->subKeys[0] == $variable) {
                return $keyword->values;
            }
        }
        throw new RuntimeException(sprintf('Could not determine values of "%s".', $variable));
    }

    /**
     * @param string variable
     *
     * @return array|null
     */
    public function codes($variable)
    {
        foreach ($this->keywordList('CODES') as $keyword) {
            if ($keyword->subKeys[0] == $variable) {
                return $keyword->values;
            }
        }

        return null;
    }

    public function index($choices)
    {
        $px = $this;
        $counts = array_map(function ($variable) use ($px) {
            return count($px->values($variable));
        }, $this->variables());

        $index = 0;
        for ($i = 0; $i < count($choices) - 1; $i++) {
            $index += $choices[$i] * array_product(array_slice($counts, $i + 1));
        }
        $index += end($choices);

        return $index;
    }

    public function datum($choices)
    {
        $this->assertData();

        $index = $this->index($choices);
        if (isset($this->data[$index])) {
            return $this->data[$index];
        } else {
            return null;
        }
    }

    public function keywords()
    {
        $this->assertKeywords();

        return $this->keywords;
    }

    public function keywordList($keyword)
    {
        $this->assertKeywords();

        if (isset($this->keywords[$keyword])) {
            return $this->keywords[$keyword];
        } else {
            return [];
        }
    }

    public function hasKeyword($keyword)
    {
        $this->assertKeywords();

        return isset($this->keywords[$keyword]);
    }

    public function keyword($keyword)
    {
        $list = $this->keywordList($keyword);
        if (!$list) {
            throw new RuntimeException(sprintf('Keyword "%s" does not exist.', $keyword));
        }

        return $list[0];
    }

    public function data()
    {
        $this->assertData();

        return $this->data;
    }

    private function parseKeywordLine($line)
    {
        $data = new stdClass();

        $line = trim(str_replace('""', ' ', $line));
        $data->raw = $line;

        $equalPos = self::findQuoted($line, '=');
        if ($equalPos <= 0) {
            return;
        }

        $key = substr($line, 0, $equalPos);
        $data->subKeys = [];
        if (substr($key, -1) == ')' && ($start = self::findQuotedReverse($key, '(')) !== false) {
            $data->subKeys = self::split(substr($key, $start + 1, -1));
            $key = substr($key, 0, $start);
        }
        $data->lang = null;
        if (substr($key, -1) == ']' && ($start = self::findQuotedReverse($key, '[')) !== false) {
            $data->lang = trim(substr($key, $start + 1, -1), '"');
            $key = substr($key, 0, $start);
        }

        $data->values = self::split(substr($line, $equalPos + 1));

        if (!isset($this->keywords[$key])) {
            $this->keywords[$key] = [];
        }
        $this->keywords[$key][] = $data;

        if ($key === 'CHARSET') {
            $this->charset = $data->values[0];
            if ($this->charset === 'ANSI' && $this->codepage === null) {
                $this->codepage = 'ISO-8859-1';
            }
        } elseif ($key === 'CODEPAGE') {
            $this->codepage = $data->values[0];
        }
    }

    private function assertKeywords()
    {
        if ($this->keywords !== null) {
            return;
        }

        $this->handle = fopen($this->path, 'r');
        if ($this->handle === false) {
            throw new RuntimeException('Could not open file.');
        }

        $this->keywords = [];
        $remainder = '';
        while (($line = fgets($this->handle)) !== false) {
            $line = trim($this->decodeLine($line));
            if ($line == 'DATA=') {
                break;
            }
            $remainder .= $line;
            $i = 0;
            while (($i = self::findQuoted($remainder, ';')) !== false) {
                $this->parseKeywordLine(substr($remainder, 0, $i));
                $remainder = substr($remainder, $i + 1);
                $i = 0;
            }
        }

        $this->dataOffset = ftell($this->handle);
    }

    private function assertData()
    {
        if ($this->data !== null) {
            return;
        }

        $this->assertKeywords();

        fseek($this->handle, $this->dataOffset, SEEK_SET);

        $raw = '';
        while (($line = fgets($this->handle)) !== false) {
            $line = trim($this->decodeLine($line), "\r\n");
            $raw .= $line;
        }

        $cells = [];
        $len = strlen($raw);
        $value = '';
        for ($i = 0;$i < $len;$i++) {
            if (!$value && $raw[$i] == '"' && ($end = strpos($raw, '"', $i + 1)) !== false) {
                $cells[] = substr($raw, $i + 1, $end - $i - 1);
                $i = $end;
                $value = '';
            } elseif (in_array($raw[$i], [' ', ',', ';', "\t"])) {
                if ($value) {
                    $cells[] = $value;
                    $value = '';
                }
            } else {
                $value .= $raw[$i];
            }
        }
        if ($value) {
            $cells[] = $value;
        }

        $this->data = $cells;

        fclose($this->handle);
    }

    private function decodeLine($line)
    {
        if ($this->codepage !== null) {
            $line = iconv($this->codepage, 'UTF8', $line);
        } elseif ($this->charset !== 'ANSI') {
            $line = iconv($this->charset, 'UTF8', $line);
        }

        return $line;
    }

    private static function split($string)
    {
        $values = [];
        while (($pos = self::findQuoted($string, ',')) !== false) {
            $values[] = trim(trim(substr($string, 0, $pos)), '"');
            $string = substr($string, $pos + 1);
        }
        $values[] = trim(trim($string), '"');

        return $values;
    }

    private static function findQuoted($haystack, $needle)
    {
        $pos = 0;
        while (($pos = strpos($haystack, $needle, $pos)) !== false) {
            if (substr_count($haystack, '"', 0, $pos) % 2 == 0) {
                return $pos;
            }
            $pos++;
        }

        return $pos;
    }

    private static function findQuotedReverse($haystack, $needle)
    {
        $len = strlen($haystack);
        $pos = strlen($haystack);
        while (($pos = strrpos($haystack, $needle, $pos - $len)) !== false) {
            if (substr_count($haystack, '"', $pos) % 2 == 0) {
                return $pos;
            }
            $pos--;
        }

        return $pos;
    }
}
