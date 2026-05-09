<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\SharedKernel\Models\Employee;
use App\SharedKernel\Models\Division;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HrisEmployeeSeeder extends Seeder
{
    /**
     * Import employee data from HRIS system.
     * This mirrors the data structure from HRIS/data.js
     */
    public function run(): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Clear existing employees
        Employee::truncate();
        
        // Ensure divisions exist first
        $this->seedDivisions();
        
        // Import employees
        $employees = $this->generateEmployees();
        
        foreach ($employees as $empData) {
            Employee::create($empData);
        }
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->command->info('Imported ' . count($employees) . ' employees from HRIS data.');
    }
    
    private function seedDivisions(): void
    {
        $divisions = [
            ['division_name' => 'Office of the Regional Director', 'division_code' => 'ORD'],
            ['division_name' => 'Internal Management Services Division', 'division_code' => 'IMSD'],
            ['division_name' => 'Technical Support & Services Division', 'division_code' => 'TSSD'],
            ['division_name' => 'Labor Laws Compliance Division', 'division_code' => 'LLCD'],
        ];
        
        foreach ($divisions as $division) {
            Division::firstOrCreate([
                'code' => $division['division_code']
            ], [
                'name' => $division['division_name'],
                'code' => $division['division_code'],
                'is_active' => true,
            ]);
        }
    }
    
    /**
     * Generate 82 employees matching HRIS data structure
     */
    private function generateEmployees(): array
    {
        $divisions = Division::all()->keyBy('code');
        
        $positions = [
            ['title' => 'Administrative Officer V', 'sg' => 18],
            ['title' => 'Administrative Officer IV', 'sg' => 15],
            ['title' => 'Administrative Officer II', 'sg' => 11],
            ['title' => 'Administrative Assistant III', 'sg' => 9],
            ['title' => 'Administrative Assistant II', 'sg' => 8],
            ['title' => 'Administrative Aide VI', 'sg' => 6],
            ['title' => 'Accountant III', 'sg' => 19],
            ['title' => 'Accountant II', 'sg' => 16],
            ['title' => 'Accountant I', 'sg' => 13],
            ['title' => 'Budget Officer III', 'sg' => 18],
            ['title' => 'Budget Officer II', 'sg' => 15],
            ['title' => 'Human Resource Management Officer III', 'sg' => 18],
            ['title' => 'Human Resource Management Officer II', 'sg' => 15],
            ['title' => 'Information Technology Officer II', 'sg' => 19],
            ['title' => 'Information Technology Officer I', 'sg' => 16],
            ['title' => 'Lawyer III', 'sg' => 21],
            ['title' => 'Lawyer II', 'sg' => 19],
            ['title' => 'Project Development Officer III', 'sg' => 18],
            ['title' => 'Project Development Officer II', 'sg' => 15],
            ['title' => 'Supply Officer III', 'sg' => 14],
            ['title' => 'Director IV', 'sg' => 28],
            ['title' => 'Director III', 'sg' => 26],
            ['title' => 'Chief Administrative Officer', 'sg' => 24],
        ];
        
        $salarySchedule = [
            6 => 14993, 7 => 16543, 8 => 18254, 9 => 20402, 10 => 22190,
            11 => 24887, 12 => 27755, 13 => 30799, 14 => 33789, 15 => 37024,
            16 => 40638, 17 => 45203, 18 => 49835, 19 => 57347, 20 => 63997,
            21 => 71511, 22 => 79997, 23 => 90078, 24 => 101418, 25 => 115190,
            26 => 130742, 27 => 149160, 28 => 169940,
        ];
        
        $firstNames = [
            'Juan', 'Maria', 'Jose', 'Ana', 'Pedro', 'Rosa', 'Antonio', 'Luz',
            'Eduardo', 'Carmen', 'Roberto', 'Elena', 'Fernando', 'Gloria', 'Ricardo',
            'Marites', 'Rodrigo', 'Lolita', 'Ernesto', 'Cristina', 'Manuel', 'Natividad',
            'Danilo', 'Felicitas', 'Arnel', 'Rowena', 'Gerry', 'Maricel', 'Randy', 'Teresita',
            'Edwin', 'Nenita', 'Roel', 'Josephine', 'Allan', 'Evelyn', 'Rey', 'Divina',
            'Noel', 'Margarita', 'Alex', 'Virginia', 'Mark', 'Cecilia', 'Ryan', 'Elizabeth',
            'Christian', 'Annaliza', 'John', 'Maribel',
        ];
        
        $lastNames = [
            'Santos', 'Reyes', 'Cruz', 'Bautista', 'Garcia', 'Ramos', 'Lopez', 'Hernandez',
            'Gonzalez', 'Perez', 'Dela Cruz', 'Ramirez', 'Torres', 'Flores', 'Villanueva',
            'Fernandez', 'Mendoza', 'Rivera', 'Castro', 'Aquino', 'Diaz', 'Soriano',
            'Manalo', 'Aguilar', 'Pascual', 'De Leon', 'Santiago', 'Lim', 'Tan', 'Uy',
            'Corpuz', 'Macaraeg', 'Delos Reyes', 'Buenaventura', 'Evangelista', 'Ocampo',
            'Mercado', 'Tolentino', 'Magtibay', 'Macapagal',
        ];
        
        $middleNames = [
            'Dela Cruz', 'Santos', 'Reyes', 'Garcia', 'Bautista', 'Lopez', 'Ramos',
            'Hernandez', 'Torres', 'Flores', 'Rivera', 'Mendoza', 'Castro', 'Diaz',
            'Soriano', 'Manalo', 'Aguilar', 'Pascual', 'Santiago', 'Lim',
        ];
        
        $stations = [
            'Davao City', 'Tagum City', 'Digos City', 'Mati City', 'Panabo City',
            'Samal Island', 'Nabunturan', 'Baganga', 'Bansalan', 'Sta. Cruz',
        ];
        
        $employees = [];
        $seed = 42;
        
        for ($i = 1; $i <= 82; $i++) {
            $seed = ($seed * 1664525 + 1013904223) & 0xffffffff;
            $rand = ($seed & 0xffffffff) / 0xffffffff;
            
            $division = $divisions->values()[intval($rand * $divisions->count())];
            $position = $positions[intval($rand * count($positions))];
            $sg = $position['sg'];
            $step = intval($rand * 8) + 1;
            $baseSalary = $salarySchedule[$sg] ?? 30000;
            $stepIncrement = round($baseSalary * 0.0125 * ($step - 1));
            $salary = $baseSalary + $stepIncrement;
            
            $empStatus = $i <= 60 ? 'Permanent' : ['Casual', 'Contractual', 'Job Order'][intval($rand * 3)];
            
            $firstName = $firstNames[intval($rand * count($firstNames))];
            $lastName = $lastNames[intval($rand * count($lastNames))];
            $middleName = $middleNames[intval($rand * count($middleNames))];
            $station = $stations[intval($rand * count($stations))];
            
            $employees[] = [
                'employee_no' => 'EMP' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'plantilla_item_no' => 'PLTL-' . str_pad(intval($rand * 200) + 1, 4, '0', STR_PAD_LEFT),
                'last_name' => $lastName,
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'position_title' => $position['title'],
                'division_id' => $division->id,
                'salary_grade' => $sg,
                'step' => $step,
                'basic_salary' => $salary,
                'pera' => 2000, // Standard PERA
                'employment_status' => $empStatus,
                'official_station' => $station,
                'hire_date' => '2015-06-01',
                'original_appointment_date' => '2015-06-01',
                'last_promotion_date' => '2020-01-15',
                'tin' => $this->generateTin($i),
                'gsis_bp_no' => $this->generateGsisBpNo($i),
                'gsis_crn' => $this->generateGsisCrn($i),
                'pagibig_no' => $this->generatePagibigMid(),
                'philhealth_no' => $this->generatePhilhealth(),
                'sss_no' => $this->generateSss($i),
                'vacation_leave_balance' => 15.0,
                'sick_leave_balance' => 15.0,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        return $employees;
    }
    
    private function generateGsisBpNo(int $id): string
    {
        return str_pad(random_int(1000, 9999), 4, '0', STR_PAD_LEFT) . '-' .
               str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT) . '-' .
               str_pad($id, 3, '0', STR_PAD_LEFT);
    }
    
    private function generateGsisCrn(int $id): string
    {
        return str_pad($id + 1000000, 11, '0', STR_PAD_LEFT);
    }
    
    private function generatePagibigMid(): string
    {
        return str_pad(random_int(100, 999), 3, '0', STR_PAD_LEFT) . '-' .
               str_pad(random_int(100, 999), 3, '0', STR_PAD_LEFT) . '-' .
               str_pad(random_int(100, 999), 3, '0', STR_PAD_LEFT);
    }
    
    private function generatePhilhealth(): string
    {
        return str_pad(random_int(10, 99), 2, '0', STR_PAD_LEFT) . '-' .
               str_pad(random_int(100000000, 999999999), 9, '0', STR_PAD_LEFT) . '-' .
               random_int(0, 9);
    }
    
    private function generateTin(): string
    {
        return str_pad(random_int(100, 999), 3, '0', STR_PAD_LEFT) . '-' .
               str_pad(random_int(100, 999), 3, '0', STR_PAD_LEFT) . '-' .
               str_pad(random_int(100, 999), 3, '0', STR_PAD_LEFT) . '-' .
               str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);
    }
    
    private function generateSss(int $id): string
    {
        return str_pad($id + 2000000, 10, '0', STR_PAD_LEFT);
    }
}
