<?php

namespace App\Http\Controllers;

use App\Models\WeeklyHoliday;
use App\Models\WorkCalendar;
use Illuminate\Http\Request;

class WorkCalendarController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->input('year', date('Y'));

        $weeklyHolidays = WeeklyHoliday::pluck('is_holiday', 'day_name')->toArray();
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        // Initialize default if empty
        if (empty($weeklyHolidays)) {
            foreach ($days as $day) {
                WeeklyHoliday::firstOrCreate([
                    'day_name' => $day
                ], [
                    'is_holiday' => in_array($day, ['Saturday', 'Sunday'])
                ]);
            }
            $weeklyHolidays = WeeklyHoliday::pluck('is_holiday', 'day_name')->toArray();
        }

        $specialDays = WorkCalendar::whereYear('date', $year)->orderBy('date')->get();

        return view('admin.work-calendar.index', compact('year', 'weeklyHolidays', 'specialDays', 'days'));
    }

    public function updateWeekly(Request $request)
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $holidays = $request->input('holidays', []);

        foreach ($days as $day) {
            WeeklyHoliday::updateOrCreate(
                ['day_name' => $day],
                ['is_holiday' => in_array($day, $holidays)]
            );
        }

        return back()->with('success', 'Pengaturan hari libur mingguan berhasil disimpan.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date|unique:work_calendars,date',
            'description' => 'required|string|max:300',
            'status' => 'required|in:holiday,working_day',
        ]);

        if ($request->status === 'holiday') {
            $hasAttendance = \App\Models\Attendance::where('date', $request->date)->exists();
            if ($hasAttendance) {
                return back()->with('error', '⚠️ Tidak dapat mengubah menjadi hari libur. Sudah terdapat data absensi pada tanggal tersebut. Status hari tidak dapat diubah menjadi Hari Libur.');
            }
        }

        WorkCalendar::create($request->all());

        return back()->with('success', 'Hari khusus berhasil ditambahkan.');
    }

    public function update(Request $request, WorkCalendar $workCalendar)
    {
        $request->validate([
            'date' => 'required|date|unique:work_calendars,date,' . $workCalendar->id,
            'description' => 'required|string|max:300',
            'status' => 'required|in:holiday,working_day',
        ]);

        if ($request->status === 'holiday') {
            $hasAttendance = \App\Models\Attendance::where('date', $request->date)->exists();
            if ($hasAttendance) {
                return back()->with('error', 'Tidak dapat mengubah hari kerja menjadi hari libur. Sudah terdapat data absensi pada tanggal tersebut. Status hari tidak dapat diubah menjadi Hari Libur.');
            }
        }

        $workCalendar->update($request->all());

        return back()->with('success', 'Hari khusus berhasil diperbarui.');
    }

    public function destroy(WorkCalendar $workCalendar)
    {
        $workCalendar->delete();

        return back()->with('success', 'Hari khusus berhasil dihapus.');
    }
}
