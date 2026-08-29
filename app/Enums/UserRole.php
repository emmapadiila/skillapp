<?php

namespace App\Enums;

enum UserRole: string
{
    case Superadmin = 'superadmin';
    case HumanResources = 'human_resources';
    case Collaborator = 'collaborator';
}
