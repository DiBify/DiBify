<?php


namespace DiBify\DiBify\Mock;


class TestRepo_2 extends Repository
{

    public function classes(): array
    {
        return [TestModel_2::class];
    }
}