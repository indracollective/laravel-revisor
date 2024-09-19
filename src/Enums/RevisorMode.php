<?php

declare(strict_types=1);

namespace Indra\Revisor\Enums;

enum RevisorMode: string
{
    case Draft = 'Draft';
    case Version = 'Version';
    case Published = 'Published';
}
