<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\DoctorSearchController;
use App\Http\Controllers\Api\MedicalTestAppointmentController;
use App\Http\Controllers\Api\MedicalTestController;
use App\Http\Controllers\Api\MedicalTestResultController;
use App\Http\Controllers\Api\MedicalTestSearchController;
use App\Http\Controllers\Api\PatientAuthController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\ResetPasswordController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// User Routes
Route::prefix('users-group')->group(function () {
    // User Routes
    Route::apiResource('users', UserController::class)->middleware('auth:sanctum');

    // User Authentication Routes
    Route::post('user/register', [UserController::class, 'register']);
    Route::post('user/login', [UserController::class, 'login']);

    // User Logout Route
    Route::post('user/logout', [UserController::class, 'logout'])->middleware('auth:sanctum,doctor,patient');
});

// Doctor Routes
Route::prefix('doctors-group')->group(function () {
    // Doctor Routes
    Route::apiResource('doctors', DoctorController::class)->middleware('auth:sanctum,doctor');
    // Update Doctor Route
    Route::post('doctors/update/{id}', [DoctorController::class, 'update'])->middleware('auth:sanctum,doctor');

    // Authentication Routes
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    // Doctor Logout Route
    Route::post('doctor/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum,doctor,patient');

    // Top Rated Doctors
    Route::get('top-doctors', [DoctorController::class, 'topDoctors'])->middleware('auth:sanctum,patient');

    // Routes for patient (view only: index and show)
    Route::middleware('auth:sanctum,patient')->group(function () {
        Route::get('/doctors', [DoctorController::class, 'index']); // All Doctors
        Route::get('/doctors/view/{id}', [DoctorController::class, 'show']); // Single Doctor
    });

    // New Route to restore Doctor from archive
    Route::post('doctors/restore/{id}', [DoctorController::class, 'restore'])->middleware('auth:sanctum');
    // New Route to Delete Doctor Data from Archive
    Route::delete('doctors/delete/archive/{id}', [DoctorController::class, 'deleteArchive'])->middleware('auth:sanctum');
});

// Patient Routes
Route::prefix('patient-group')->group(function () {
    // Route for patient registration (without authentication)
    Route::post('patient/register', [PatientController::class, 'store']);

    // Patient Authentication Routes
    Route::post('patient/login', [PatientAuthController::class, 'login']);

    // Protected routes for patients (for patient, owner and Doctor using sanctum)
    Route::middleware('auth:sanctum,doctor,patient')->group(function () {
        Route::post('patient/logout', [PatientAuthController::class, 'logout']);
        Route::get('patients', [PatientController::class, 'index']);
        Route::get('patient/me', [PatientController::class, 'showCurrentPatient']);
        Route::get('patients/{id}', [PatientController::class, 'show']);
        Route::post('patient/update/{id}', [PatientController::class, 'update']);
        Route::delete('patient/destroy/{id}', [PatientController::class, 'destroy']);

        // New Routes for favorite_doctors
        Route::post('favorite/toggle', [PatientController::class, 'toggleFavorite']); // Add/Remove a doctor from favorites
        Route::get('favorite/doctors', [PatientController::class, 'getFavoriteDoctors']); // Retrieve the list of favorite doctors

        Route::post('emergency/call', [PatientController::class, 'emergencyCall']);
    });
});

// Routes for managing appointments
Route::prefix('appointments')->middleware('auth:sanctum,doctor,patient')->group(function () {
    // Book a new appointment (Patient only)
    Route::post('book', [AppointmentController::class, 'bookAppointment'])->name('appointments.book');

    // Cancel an existing appointment (Patient only)
    Route::delete('cancel/{appointmentId}', [AppointmentController::class, 'cancelAppointment'])->name('appointments.cancel');

    // Get available appointments for a doctor on a specific date (Patient or Doctor)
    Route::get('doctors/available/{docId}', [AppointmentController::class, 'getAvailableAppointments'])->name('appointments.available');

    // Get upcoming appointments for the authenticated patient
    Route::get('upcoming', [AppointmentController::class, 'getPatientAppointments'])->name('appointments.upcoming');

    // Get canceled appointments for the authenticated patient
    Route::get('canceled', [AppointmentController::class, 'getCanceledAppointments'])->name('appointments.canceled');
});

// Route to reset password Patient
Route::prefix('reset-password')->group(function () {
    // Request to send password reset code
    Route::post('send-code', [ResetPasswordController::class, 'sendResetCode'])->name('password.send-code');

    // Reset password
    Route::post('reset', [ResetPasswordController::class, 'resetPassword'])->name('password.reset');

    // Resend the code
    Route::post('resend-code', [ResetPasswordController::class, 'resendCode'])->name('password.resend-code');
});

// Routes for Medical Tests with auth:sanctum middleware
Route::prefix('medical-test')->group(function () {
    // Route to get all medical tests
    Route::get('medical-tests', [MedicalTestController::class, 'index'])->middleware('auth:sanctum,doctor,patient');

    // Route to create a new medical test
    Route::post('create/medical-test', [MedicalTestController::class, 'store'])->middleware('auth:sanctum');

    // Route to get a specific medical test
    Route::get('medical-test/view/{id}', [MedicalTestController::class, 'show'])->middleware('auth:sanctum,patient');

    // Route to update a specific medical test
    Route::put('medical-test/update/{id}', [MedicalTestController::class, 'update'])->middleware('auth:sanctum');

    // Route to delete a specific medical test
    Route::delete('medical-test/delete/{id}', [MedicalTestController::class, 'destroy'])->middleware('auth:sanctum');
});

// Routes for managing medical test appointments
Route::prefix('medical-test-appointments')->middleware('auth:sanctum,doctor,patient')->group(function () {
    // Appoint a new medical test (Patient only)
    Route::post('appoint', [MedicalTestAppointmentController::class, 'appointTest'])->name('medical_test_appointments.appoint');

    // Cancel an existing medical test appointment (Patient only)
    Route::delete('cancel/{appointmentId}', [MedicalTestAppointmentController::class, 'cancelTest'])->name('medical_test_appointments.cancel');

    // Get available appointments for a medical test on a specific date (Patient or Doctor)
    Route::get('tests/available/{testId}', [MedicalTestAppointmentController::class, 'getAvailableTests'])->name('medical_test_appointments.available');

    // Get upcoming medical test appointments for the authenticated patient
    Route::get('upcoming', [MedicalTestAppointmentController::class, 'getUpcomingMedicalTestAppointments'])->name('medical-tests.upcoming');

    // Get canceled medical test appointments for the authenticated patient
    Route::get('canceled', [MedicalTestAppointmentController::class, 'getCanceledMedicalTestAppointments'])->name('medical-tests.canceled');
});

// Routes for managing medical test results
Route::prefix('medical-test-results')->middleware('auth:sanctum,doctor,patient')->group(function () {
    Route::post('upload', [MedicalTestResultController::class, 'uploadResult'])->name('medical_test_results.upload')->middleware('auth:sanctum,doctor');
    Route::get('download/{resultId}', [MedicalTestResultController::class, 'downloadResult'])->name('medical_test_results.download');
});

// Routes for searching doctors and medical tests
Route::prefix('search')->middleware('auth:patient')->group(function () {
    Route::get('doctors/search', [DoctorSearchController::class, 'search']);
    Route::get('medical-tests/search', [MedicalTestSearchController::class, 'search']);
});
