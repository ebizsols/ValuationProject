<?php

namespace App;

use Illuminate\Database\Eloquent\Collection;

class MyUser extends \App\User
{
    protected $table = 'users';
    
    public function employeePerk()
    {
        return $this->hasMany(EmployeePerk::class, 'user_id');
    }
    
    public function employeeTerm()
    {
        return $this->hasMany(EmployeeTerm::class, 'user_id');
    }
    
    public function employee()
    {
        return $this->hasMany(EmployeeDetails::class, 'user_id');
    }
    
    public function employeePayroll()
    {
        return $this->hasMany(EmployeePayroll::class, 'user_id');
    }
    
    public function employeePenalty()
    {
        return $this->hasMany(EmployeePenalty::class, 'user_id');
    }

    /**
     * Department and User are in Many to Many Relationship
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function department()
    {
//         return $this->belongsToMany(Department::class);
        return $this->hasMany(DepartmentEmployee::class, 'user_id');
    }
    
    /**
     * Payroll Group and User are in Many-Many Realationship
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function payrollGroup()
    {
        return $this->hasMany(PayrollGroupUser::class, 'user_id');
    }

    /**
     * Fetch all users that belong to a department
     *  
     * @param integer $departmentId
     * 
     * @return Collection | null
     */
    public static function departmentUsers($departmentId)
    {
        $users = User::join('department_employees', 'department_employees.user_id', '=', 'users.id')
            ->select('users.id', 'users.name', 'users.email', 'users.created_at')
            ->where('department_employees.department_id', $departmentId);
        
        return $users->get();
    }
    
    /**
     * Fetch all users that belong to a PayrollGroup
     *
     * @param integer $payrollGroupId
     *
     * @return Collection | null
     */
    public static function payrollGroupUsers($payrollGroupId)
    {
        $users = User::join('payroll_group_users', 'payroll_group_users.user_id', '=', 'users.id')
            ->select('users.id', 'users.name', 'users.email', 'users.created_at')
            ->where('payroll_group_users.payroll_group_id', $payrollGroupId);
        
        return $users->get();
    }

}
