// ============================================================
//  DUMMY DATA - Simulated HRIS Employee Database
//  Based on the TypeScript data provided
// ============================================================

const divisions = [
  { division_id: 1, division_name: "Office of the Regional Director", division_code: "ORD" },
  { division_id: 2, division_name: "Internal Management Services Division", division_code: "IMSD" },
  { division_id: 3, division_name: "Technical Support & Services Division", division_code: "TSSD" },
  { division_id: 4, division_name: "Labor Laws Compliance Division", division_code: "LLCD" },
];

const positions = [
  { title: "Administrative Officer V", sg: 18 },
  { title: "Administrative Officer IV", sg: 15 },
  { title: "Administrative Officer II", sg: 11 },
  { title: "Administrative Assistant III", sg: 9 },
  { title: "Administrative Assistant II", sg: 8 },
  { title: "Administrative Aide VI", sg: 6 },
  { title: "Accountant III", sg: 19 },
  { title: "Accountant II", sg: 16 },
  { title: "Accountant I", sg: 13 },
  { title: "Budget Officer III", sg: 18 },
  { title: "Budget Officer II", sg: 15 },
  { title: "Human Resource Management Officer III", sg: 18 },
  { title: "Human Resource Management Officer II", sg: 15 },
  { title: "Information Technology Officer II", sg: 19 },
  { title: "Information Technology Officer I", sg: 16 },
  { title: "Lawyer III", sg: 21 },
  { title: "Lawyer II", sg: 19 },
  { title: "Project Development Officer III", sg: 18 },
  { title: "Project Development Officer II", sg: 15 },
  { title: "Supply Officer III", sg: 14 },
  { title: "Director IV", sg: 28 },
  { title: "Director III", sg: 26 },
  { title: "Chief Administrative Officer", sg: 24 },
];

const salarySchedule = {
  6: 14993,
  7: 16543,
  8: 18254,
  9: 20402,
  10: 22190,
  11: 24887,
  12: 27755,
  13: 30799,
  14: 33789,
  15: 37024,
  16: 40638,
  17: 45203,
  18: 49835,
  19: 57347,
  20: 63997,
  21: 71511,
  22: 79997,
  23: 90078,
  24: 101418,
  25: 115190,
  26: 130742,
  27: 149160,
  28: 169940,
};

const firstNames = [
  "Juan", "Maria", "Jose", "Ana", "Pedro", "Rosa", "Antonio", "Luz",
  "Eduardo", "Carmen", "Roberto", "Elena", "Fernando", "Gloria", "Ricardo",
  "Marites", "Rodrigo", "Lolita", "Ernesto", "Cristina", "Manuel", "Natividad",
  "Danilo", "Felicitas", "Arnel", "Rowena", "Gerry", "Maricel", "Randy", "Teresita",
  "Edwin", "Nenita", "Roel", "Josephine", "Allan", "Evelyn", "Rey", "Divina",
  "Noel", "Margarita", "Alex", "Virginia", "Mark", "Cecilia", "Ryan", "Elizabeth",
  "Christian", "Annaliza", "John", "Maribel",
];

const lastNames = [
  "Santos", "Reyes", "Cruz", "Bautista", "Garcia", "Ramos", "Lopez", "Hernandez",
  "Gonzalez", "Perez", "Dela Cruz", "Ramirez", "Torres", "Flores", "Villanueva",
  "Fernandez", "Mendoza", "Rivera", "Castro", "Aquino", "Diaz", "Soriano",
  "Manalo", "Aguilar", "Pascual", "De Leon", "Santiago", "Lim", "Tan", "Uy",
  "Corpuz", "Macaraeg", "Delos Reyes", "Buenaventura", "Evangelista", "Ocampo",
  "Mercado", "Tolentino", "Magtibay", "Macapagal",
];

const middleNames = [
  "Dela Cruz", "Santos", "Reyes", "Garcia", "Bautista", "Lopez", "Ramos",
  "Hernandez", "Torres", "Flores", "Rivera", "Mendoza", "Castro", "Diaz",
  "Soriano", "Manalo", "Aguilar", "Pascual", "Santiago", "Lim",
];

const stations = [
  "Davao City", "Tagum City", "Digos City", "Mati City", "Panabo City",
  "Samal Island", "Nabunturan", "Baganga", "Bansalan", "Sta. Cruz",
];

function pad(n, len) {
  return String(n).padStart(len, "0");
}

function randomInt(min, max) {
  return Math.floor(Math.random() * (max - min + 1)) + min;
}

function randomFrom(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

function randomDate(start, end) {
  const d = new Date(start.getTime() + Math.random() * (end.getTime() - start.getTime()));
  return d.toISOString().split("T")[0];
}

function generateGsisBpNo(id) {
  return `${pad(randomInt(1000, 9999), 4)}-${pad(randomInt(100000, 999999), 6)}-${pad(id, 3)}`;
}

function generateGsisCrn(id) {
  return `${pad(id + 1000000, 11)}`;
}

function generatePagibigMid() {
  return `${pad(randomInt(100, 999), 3)}-${pad(randomInt(100, 999), 3)}-${pad(randomInt(100, 999), 3)}`;
}

function generatePhilhealth() {
  return `${pad(randomInt(10, 99), 2)}-${pad(randomInt(100000000, 999999999), 9)}-${pad(randomInt(0, 9), 1)}`;
}

function generateTin() {
  return `${pad(randomInt(100, 999), 3)}-${pad(randomInt(100, 999), 3)}-${pad(randomInt(100, 999), 3)}-${pad(randomInt(0, 999), 3)}`;
}

// Seeded random to keep data consistent across calls
let seed = 42;
function seededRand() {
  seed = (seed * 1664525 + 1013904223) & 0xffffffff;
  return (seed >>> 0) / 0xffffffff;
}

function seededInt(min, max) {
  return Math.floor(seededRand() * (max - min + 1)) + min;
}

function seededFrom(arr) {
  return arr[Math.floor(seededRand() * arr.length)];
}

function generateEmployees() {
  seed = 42; // reset seed for consistency
  const profiles = [];

  for (let i = 1; i <= 82; i++) {
    const division = seededFrom(divisions);
    const position = seededFrom(positions);
    const sg = position.sg;
    const step = seededInt(1, 8);
    const baseSalary = salarySchedule[sg] ?? 30000;
    const stepIncrement = Math.round(baseSalary * 0.0125 * (step - 1));
    const salary = baseSalary + stepIncrement;

    const appointmentDate = randomDate(new Date("2000-01-01"), new Date("2018-12-31"));
    const promotionDate = randomDate(new Date("2019-01-01"), new Date("2023-12-31"));

    const empStatus = i <= 60 ? "Permanent" : seededFrom(["Casual", "Contractual", "Job Order"]);

    profiles.push({
      employee_id: `EMP${pad(i, 3)}`,
      employee_no: `EMP-${pad(i, 4)}`,
      last_name: seededFrom(lastNames),
      first_name: seededFrom(firstNames),
      middle_name: seededFrom(middleNames),
      position_title: position.title,
      plantilla_item_no: `PLTL-${pad(seededInt(1, 200), 4)}`,
      salary_grade: sg,
      step,
      basic_monthly_salary: salary,
      division_id: division.division_id,
      division_name: division.division_name,
      division_code: division.division_code,
      employment_status: empStatus,
      official_station: seededFrom(stations),
      date_original_appointment: appointmentDate,
      last_promotion_date: promotionDate,
      gsis_bp_no: generateGsisBpNo(i),
      gsis_crn: generateGsisCrn(i),
      pagibig_mid_no: generatePagibigMid(),
      philhealth_no: generatePhilhealth(),
      tin: generateTin(),
    });
  }

  return profiles;
}

function generateAttendance() {
  seed = 99;
  const records = [];

  // Dynamically generate 12 months back and 3 months forward from today
  const now = new Date();
  const months = [];

  for (let i = -12; i <= 3; i++) {
    const d = new Date(now.getFullYear(), now.getMonth() + i, 1);
    months.push({ year: d.getFullYear(), month: d.getMonth() + 1 });
  }

  for (let empId = 1; empId <= 82; empId++) {
    for (const { year, month } of months) {
      const mm = String(month).padStart(2, "0");
      const lastDay = new Date(year, month, 0).getDate();

      // First cutoff: 1–15
      records.push({
        employee_id: `EMP${pad(empId, 3)}`,
        cutoff_start: `${year}-${mm}-01`,
        cutoff_end: `${year}-${mm}-15`,
        days_present: seededInt(8, 11),
        lwop_days: seededInt(0, 10) > 8 ? seededInt(1, 2) : 0,
        late_minutes: seededInt(0, 3) > 0 ? seededInt(5, 90) : 0,
        undertime_minutes: seededInt(0, 4) > 0 ? seededInt(0, 60) : 0,
      });

      // Second cutoff: 16–end of month
      records.push({
        employee_id: `EMP${pad(empId, 3)}`,
        cutoff_start: `${year}-${mm}-16`,
        cutoff_end: `${year}-${mm}-${lastDay}`,
        days_present: seededInt(8, 11),
        lwop_days: seededInt(0, 10) > 8 ? seededInt(1, 2) : 0,
        late_minutes: seededInt(0, 3) > 0 ? seededInt(5, 90) : 0,
        undertime_minutes: seededInt(0, 4) > 0 ? seededInt(0, 60) : 0,
      });
    }
  }

  return records;
}

// Generate the data
const employees = generateEmployees();
const attendanceRecords = generateAttendance();

// HRIS employee database (simplified for login)
const HRIS_EMPLOYEES = employees.map(emp => ({
  id: emp.employee_id,
  name: `${emp.first_name} ${emp.last_name}`,
  email: `${emp.first_name.toLowerCase()}.${emp.last_name.toLowerCase()}@dole.gov.ph`,
  department: emp.division_name,
  fullProfile: emp,
}));

module.exports = {
  divisions,
  positions,
  employees,
  attendanceRecords,
  HRIS_EMPLOYEES,
};
