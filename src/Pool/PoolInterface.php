<?php
/**
 * Created for DiBify
 * Date: 03.01.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Pool;


interface PoolInterface
{

    public function __construct($current, $pool = null);

    public function getCurrent();

    public function getPool();

    public function getResult();

    public function add($value): void;

    public function subtract($value): void;

}