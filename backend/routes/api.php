<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\OrganizationMemberController;
use App\Http\Controllers\Api\OrganizationPositionController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectFieldController;
use App\Http\Controllers\Api\ProjectMemberController;
use App\Http\Controllers\Api\TaskAttachmentController;
use App\Http\Controllers\Api\TaskCategoryController;
use App\Http\Controllers\Api\TaskCommentController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TaskStatusController;
use App\Http\Controllers\Api\WorkLogController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    // Always available once authenticated (even before email verification).
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);

    // Email verification (called by the SPA /verify-email page with the signed
    // params; requires login + a valid signature). Must keep the name.
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('signed')
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1');

    // Everything below requires a verified email.
    Route::middleware('verified')->group(function () {

        // Billing / plans
        Route::get('/plans', [BillingController::class, 'plans']);
        Route::get('/organizations/{organization}/billing', [BillingController::class, 'summary']);

        // Organizations
        Route::get('/organizations', [OrganizationController::class, 'index']);
        Route::post('/organizations', [OrganizationController::class, 'store']);
        Route::get('/organizations/{organization}', [OrganizationController::class, 'show']);

        // Members & invitations (scoped to the current organization tenant)
        Route::middleware('organization')->group(function () {
            Route::get('/organizations/{organization}/members', [OrganizationMemberController::class, 'index']);
            Route::patch('/organizations/{organization}/members/{member}', [OrganizationMemberController::class, 'update']);
            Route::post('/organizations/{organization}/invitations', [OrganizationMemberController::class, 'invite']);

            // Custom job-title positions (display labels)
            Route::get('/organizations/{organization}/positions', [OrganizationPositionController::class, 'index']);
            Route::post('/organizations/{organization}/positions', [OrganizationPositionController::class, 'store']);
            Route::delete('/organizations/{organization}/positions/{position}', [OrganizationPositionController::class, 'destroy']);

            // Projects within an organization
            Route::get('/organizations/{organization}/projects', [ProjectController::class, 'index']);
            Route::post('/organizations/{organization}/projects', [ProjectController::class, 'store']);
        });

        Route::post('/invitations/{token}/accept', [InvitationController::class, 'accept']);

        // Project detail & members (cross-organization membership)
        Route::get('/projects/{project}', [ProjectController::class, 'show']);
        Route::patch('/projects/{project}/settings', [ProjectController::class, 'updateSettings']);
        Route::post('/projects/{project}/archive', [ProjectController::class, 'archive']);
        Route::post('/projects/{project}/unarchive', [ProjectController::class, 'unarchive']);
        Route::get('/projects/{project}/members', [ProjectMemberController::class, 'index']);
        Route::post('/projects/{project}/members', [ProjectMemberController::class, 'store']);
        Route::patch('/projects/{project}/members/{member}', [ProjectMemberController::class, 'update']);

        // Custom fields (project-defined)
        Route::get('/projects/{project}/fields', [ProjectFieldController::class, 'index']);
        Route::post('/projects/{project}/fields', [ProjectFieldController::class, 'store']);
        Route::patch('/projects/{project}/fields/reorder', [ProjectFieldController::class, 'reorder']);
        Route::patch('/projects/{project}/fields/{field}', [ProjectFieldController::class, 'update']);
        Route::delete('/projects/{project}/fields/{field}', [ProjectFieldController::class, 'destroy']);

        // Task categories (project-defined)
        Route::post('/projects/{project}/categories', [TaskCategoryController::class, 'store']);
        Route::patch('/projects/{project}/categories/reorder', [TaskCategoryController::class, 'reorder']);
        Route::patch('/projects/{project}/categories/{category}', [TaskCategoryController::class, 'update']);
        Route::delete('/projects/{project}/categories/{category}', [TaskCategoryController::class, 'destroy']);

        // Statuses (board columns)
        Route::post('/projects/{project}/statuses', [TaskStatusController::class, 'store']);
        Route::patch('/projects/{project}/statuses/reorder', [TaskStatusController::class, 'reorder']);
        Route::patch('/projects/{project}/statuses/{status}', [TaskStatusController::class, 'update']);
        Route::delete('/projects/{project}/statuses/{status}', [TaskStatusController::class, 'destroy']);

        // Tasks
        Route::get('/projects/{project}/tasks', [TaskController::class, 'index']);
        Route::post('/projects/{project}/tasks', [TaskController::class, 'store']);
        Route::get('/tasks/{task}', [TaskController::class, 'show']);
        Route::patch('/tasks/{task}', [TaskController::class, 'update']);
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);

        // Task comments
        Route::get('/tasks/{task}/comments', [TaskCommentController::class, 'index']);
        Route::post('/tasks/{task}/comments', [TaskCommentController::class, 'store']);

        // Work logs (actual effort)
        Route::get('/tasks/{task}/work-logs', [WorkLogController::class, 'index']);
        Route::post('/tasks/{task}/work-logs', [WorkLogController::class, 'store']);

        // Attachments (counts toward the per-project storage quota)
        Route::get('/tasks/{task}/attachments', [TaskAttachmentController::class, 'index']);
        Route::post('/tasks/{task}/attachments', [TaskAttachmentController::class, 'store']);
        Route::delete('/tasks/{task}/attachments/{attachment}', [TaskAttachmentController::class, 'destroy']);

    }); // end verified group
});
