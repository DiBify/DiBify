<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * @author https://stackoverflow.com/questions/31451405/cryptographically-secure-unique-id#answer-31460273
 * Datetime: 15.03.2017 16:29
 */

namespace DiBify\DiBify\Id;

use DiBify\DiBify\Model\ModelInterface;
use Exception;

class UuidGenerator implements IdGeneratorInterface
{

    /**
     * @param ModelInterface $model
     * @return Id
     * @throws Exception
     */
    public function __invoke(ModelInterface $model): Id
    {
        if (!$model->id()->isAssigned()) {
            $model->id()->assign($this->generate());
        }
        return $model->id();
    }

    /**
     * Return a UUID (version 4) using random bytes
     * Note that version 4 follows the format:
     *     xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
     * where y is one of: [8, 9, A, B]
     *
     * We use (random_bytes(1) & 0x0F) | 0x40 to force
     * the first character of hex value to always be 4
     * in the appropriate position.
     *
     * For 4: http://3v4l.org/q2JN9
     * For Y: http://3v4l.org/EsGSU
     * For the whole shebang: https://3v4l.org/LNgJb
     *
     * @ref https://stackoverflow.com/a/31460273/2224584
     * @ref https://paragonie.com/b/JvICXzh_jhLyt4y3
     *
     * @return string
     * @throws Exception
     */
    public static function generate(): string
    {
        return implode('-', [
            bin2hex(random_bytes(4)),
            bin2hex(random_bytes(2)),
            bin2hex(chr((ord(random_bytes(1)) & 0x0F) | 0x40)) . bin2hex(random_bytes(1)),
            bin2hex(chr((ord(random_bytes(1)) & 0x3F) | 0x80)) . bin2hex(random_bytes(1)),
            bin2hex(random_bytes(6))
        ]);
    }

}