<?php


namespace DiBify\DiBify\Mock;


class TestRepo_1 extends Repository
{

    protected function getClassName(): string
    {
        return TestModel_1::class;
    }
}