<?php

namespace DiBify\DiBify\Manager;

enum TransactionEvent: string
{

    case BEFORE_COMMIT = 'BEFORE_COMMIT';
    case AFTER_COMMIT = 'AFTER_COMMIT';
    case COMMIT_EXCEPTION = 'COMMIT_EXCEPTION';

}