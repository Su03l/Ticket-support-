<?php

namespace App\Enums;

enum UserType: string
{
    case SuperAdmin = 'super_admin';
    case CompanyAdmin = 'company_admin';
    case DepartmentManager = 'department_manager';
    case DepartmentDeputy = 'department_deputy';
    case SupportAgent = 'support_agent';
    case Customer = 'customer';
    case QualityAssurance = 'quality_assurance';
    case BillingAdmin = 'billing_admin';
}
