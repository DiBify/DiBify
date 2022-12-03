<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 04.12.2022 00:48
 */

namespace DiBify\DiBify\Id;

use DiBify\DiBify\Model\ModelInterface;

class SortableUniqueIdGenerator implements IdGeneratorInterface
{

    private int $length;
    private string $separator;

    public function __construct(int $length = 16, string $separator = '')
    {
        $this->length = $length;
        $this->separator = $separator;
    }

    public function __invoke(ModelInterface $model): Id
    {
        if (!$model->id()->isAssigned()) {
            $model->id()->assign(static::generate($this->length, $this->separator));
        }
        return $model->id();
    }

    public static function generate(int $length = 16, string $separator = ''): string
    {
        $microtime = round(microtime(true) * 1000);
        $time = base_convert($microtime, 10, 36);
        $randLength = $length - strlen($time) - strlen($separator);
        $rand = '';
        while (strlen($rand) < $randLength) {
            $rand.= base_convert(rand(1000000000, 9999999999), 10, 36);
        }

        $rand = substr($rand, 0, $randLength);

        return join($separator, [$time, $rand]);
    }

    public static function fixture(float $timestamp, int $length = 16, string $separator = ''): string
    {
        $microtime = round($timestamp * 1000);
        $time = base_convert($microtime, 10, 36);
        $randLength = $length - strlen($time) - strlen($separator);
        $rand = '';
        while (strlen($rand) < $randLength) {
            $rand.= $time;
        }

        $rand = substr($rand, 0, $randLength);

        return join($separator, [$time, $rand]);
    }
}