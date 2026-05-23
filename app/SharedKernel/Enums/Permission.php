<?php

namespace App\SharedKernel\Enums;

enum Permission: string
{
    // Module/UI Navigation Access
    case PAYROLL_ACCESS = 'payroll.access';
    case TEV_ACCESS = 'tev.access';
    case CONFIGURATION_ACCESS = 'configuration.access';
    case REPORTS_ACCESS = 'reports.access';
    case ADMINISTRATION_ACCESS = 'administration.access';

    // Payroll
    case PAYROLL_VIEW = 'payroll.view';
    case PAYROLL_CREATE = 'payroll.create';
    case PAYROLL_DELETE_DRAFT = 'payroll.delete-draft';
    case PAYROLL_COMPUTE = 'payroll.compute';
    case PAYROLL_SUBMIT = 'payroll.submit';
    case PAYROLL_CERTIFY = 'payroll.certify';
    case PAYROLL_APPROVE = 'payroll.approve';
    case PAYROLL_LOCK = 'payroll.lock';
    case PAYROLL_FORCE_EDIT = 'payroll.force-edit';
    case PAYROLL_SPECIAL_MANAGE = 'payroll.special.manage';
    case PAYROLL_REPORTS_VIEW = 'payroll.reports.view';

    // TEV Office Orders
    case TEV_OFFICE_ORDERS_VIEW = 'tev.office-orders.view';
    case TEV_OFFICE_ORDERS_PULL = 'tev.office-orders.pull';
    case TEV_OFFICE_ORDERS_APPROVE = 'tev.office-orders.approve';
    case TEV_OFFICE_ORDERS_CANCEL = 'tev.office-orders.cancel';

    // TEV Vouchers
    case TEV_VOUCHERS_VIEW = 'tev.vouchers.view';
    case TEV_VOUCHERS_CREATE = 'tev.vouchers.create';
    case TEV_VOUCHERS_APPROVE = 'tev.vouchers.approve';
    case TEV_VOUCHERS_CERTIFY = 'tev.vouchers.certify';
    case TEV_VOUCHERS_DISBURSE = 'tev.vouchers.disburse';

    // TEV Reports
    case TEV_REPORTS_VIEW = 'tev.reports.view';

    // Employees
    case EMPLOYEES_VIEW = 'employees.view';
    case EMPLOYEES_MANAGE = 'employees.manage';

    // System
    case USERS_MANAGE = 'users.manage';
}
