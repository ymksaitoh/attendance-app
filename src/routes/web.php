<?php


use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/', function() {
    if(auth()->check()){
        $user = auth()->user();
        if($user->role === 'admin') {
            return redirect()->route('admin.attendance.list');
        }
    }
});

Route::get('/certification', function () {
    return view('auth.certification');
})->middleware('auth')->name('verification.notice');

Route::get('email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect()->route('attendances.create');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('email/verification-notification', function (Request $request) {
    $user = Auth::user();

    if ($user && !$user->hasVerifiedEmail()) {
        $user->sendEmailVerificationNotification();
    }
    return redirect('http://localhost:8025');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'create'])->name('attendances.create');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendances.store');

    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendances.list');

    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'detail'])->name('attendances.detail');
    Route::patch('/attendance/detail/{id}/request', [AttendanceController::class, 'update'])->name('attendances.request');

    Route::get('/stamp_correction_request/list', [AttendanceController::class, 'request'])->name('attendances.request.list');
});

Route::get('admin/login', function() {
        return view('admin.admin_login');
    })->name('admin.login')->middleware('guest:admin');

Route::post('admin/login', [AdminController::class, 'login'])
    ->name('admin.login.submit')->middleware('guest:admin');

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->group(function () {
    Route::get('attendance/list/{date?}', [AdminController::class, 'attendanceList'])->name('admin.attendances.list');
    Route::get('attendance/{id}', [AttendanceController::class, 'detail'])->name('admin.attendances.detail');
    Route::patch('attendance/{id}', [AttendanceController::class, 'update'])->name('admin.attendances.update');

    Route::get('staff/list', [AdminController::class, 'staffList'])->name('admin.staff.list');

    Route::get('attendance/staff/export', [AdminController::class, 'exportStaffAttendance'])->name('admin.attendances.export');
    Route::get('attendance/staff/{id}', [AdminController::class, 'staffAttendance'])->name('admin.attendances.staff');

});

Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('/correction_request/approve/{attendance_correct_request_id}', [AdminController::class, 'approve'])
        ->name('admin.attendances.approve');
    Route::patch('/correction_request/approve/{attendance_correct_request_id}', [AdminController::class, 'approveUpdate'])->name('admin.attendances.approveUpdate');
});

Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::post('/logout', function () {
        Auth::guard('web')->logout();
        return redirect()->route('admin.login');
    })->name('admin.logout');

});
