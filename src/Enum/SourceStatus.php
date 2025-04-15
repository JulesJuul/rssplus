<?php

namespace App\Enum;

enum SourceStatus: int
{
    case COMPLETE = 200;
    case INIT = 0;
}
