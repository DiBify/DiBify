<?php


namespace DiBify\DiBify\Mock;


class TestRepo_1 extends Repository
{
    public function classes(): array
    {
        return [TestModel_1::class];
    }
}