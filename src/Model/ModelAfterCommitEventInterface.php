<?php

namespace DiBify\DiBify\Model;

interface ModelAfterCommitEventInterface
{

    public function onAfterCommit(): void;

}