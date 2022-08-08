<?php

namespace DiBify\DiBify\Model;

interface ModelBeforeCommitEventInterface
{

    public function onBeforeCommit(): void;

}