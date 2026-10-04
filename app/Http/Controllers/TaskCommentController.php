<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class TaskCommentController extends Controller
{
    public function index(Task $task)
    {
        $this->authorizeTaskAccess($task);

        $comments = $task->comments()->with('user')->latest()->get()->map(function ($comment) {
            $path = $this->resolveAttachmentPath($comment);

            return [
                'id' => $comment->id,
                'message' => $comment->message,
                'attachment' => $path,
                'attachment_path' => $path,
                'attachment_type' => $comment->attachment_type,
                'attachment_url' => $path ? Storage::disk('public')->url($path) : null,
                'user' => [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                ],
                'is_owner' => Auth::id() === $comment->user_id || Auth::user()->isAdmin(),
                'created_at' => $comment->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json(['comments' => $comments]);
    }

    public function store(Request $request, Task $task)
    {
        $this->authorizeTaskAccess($task);

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:5000'],
            'attachment' => [
                'nullable',
                'file',
                'max:20480',
                function ($attribute, $value, $fail) {
                    if (! $value instanceof \Illuminate\Http\UploadedFile || ! $value->isValid()) {
                        return;
                    }

                    $extension = strtolower($value->getClientOriginalExtension());
                    $mime = strtolower($value->getMimeType() ?? '');
                    $allowedImages = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
                    $allowedDocuments = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'rar', '7z', 'mp3', 'wav', 'ogg', 'm4a', 'aac', 'mp4', 'mov', 'avi', 'mkv'];

                    $isImage = in_array($extension, $allowedImages, true) || str_starts_with($mime, 'image/');
                    $isAudio = in_array($extension, ['mp3', 'wav', 'ogg', 'm4a', 'aac'], true) || str_starts_with($mime, 'audio/');
                    $isDocument = in_array($extension, $allowedDocuments, true) || str_starts_with($mime, 'application/') || str_starts_with($mime, 'text/');

                    if (! $isImage && ! $isAudio && ! $isDocument) {
                        $fail('The attachment type is not supported. Please upload a valid image, audio, or document file.');
                    }
                },
            ],
        ]);

        if (empty($validated['message']) && ! $request->hasFile('attachment')) {
            return response()->json(['message' => 'A message or attachment is required.'], 422);
        }

        $attachmentPath = null;
        $attachmentType = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentPath = $file->store('task-comments', 'public');
            $attachmentType = $this->detectAttachmentType($file);
        }

        $commentData = [
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'message' => $validated['message'] ?? null,
        ];

        if ($attachmentPath) {
            if (Schema::hasColumn('task_comments', 'attachment_path')) {
                $commentData['attachment_path'] = $attachmentPath;
            }

            if (Schema::hasColumn('task_comments', 'attachment')) {
                $commentData['attachment'] = $attachmentPath;
            }

            if (Schema::hasColumn('task_comments', 'attachment_type')) {
                $commentData['attachment_type'] = $attachmentType;
            }
        }

        $comment = TaskComment::create($commentData);

        return response()->json([
            'message' => 'Comment created successfully.',
            'comment' => $this->serializeComment($comment),
        ], 201);
    }

    public function update(Request $request, Task $task, TaskComment $comment)
    {
        $this->authorizeTaskAccess($task);

        if (! Auth::user()->isAdmin() && Auth::id() !== $comment->user_id) {
            abort(403, 'You can only edit your own comments.');
        }

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:5000'],
            'attachment' => [
                'nullable',
                'file',
                'max:20480',
                function ($attribute, $value, $fail) {
                    if (! $value instanceof \Illuminate\Http\UploadedFile || ! $value->isValid()) {
                        return;
                    }

                    $extension = strtolower($value->getClientOriginalExtension());
                    $mime = strtolower($value->getMimeType() ?? '');
                    $allowedImages = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
                    $allowedDocuments = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'rar', '7z', 'mp3', 'wav', 'ogg', 'm4a', 'aac', 'mp4', 'mov', 'avi', 'mkv'];

                    $isImage = in_array($extension, $allowedImages, true) || str_starts_with($mime, 'image/');
                    $isAudio = in_array($extension, ['mp3', 'wav', 'ogg', 'm4a', 'aac'], true) || str_starts_with($mime, 'audio/');
                    $isDocument = in_array($extension, $allowedDocuments, true) || str_starts_with($mime, 'application/') || str_starts_with($mime, 'text/');

                    if (! $isImage && ! $isAudio && ! $isDocument) {
                        $fail('The attachment type is not supported. Please upload a valid image, audio, or document file.');
                    }
                },
            ],
        ]);

        $existingAttachmentPath = $this->resolveAttachmentPath($comment);

        if (empty($validated['message']) && ! $request->hasFile('attachment') && ! $existingAttachmentPath) {
            return response()->json(['message' => 'A message or attachment is required.'], 422);
        }

        if ($request->hasFile('attachment')) {
            if ($existingAttachmentPath && Storage::disk('public')->exists($existingAttachmentPath)) {
                Storage::disk('public')->delete($existingAttachmentPath);
            }

            $file = $request->file('attachment');
            $newAttachmentPath = $file->store('task-comments', 'public');

            if (Schema::hasColumn('task_comments', 'attachment_path')) {
                $comment->attachment_path = $newAttachmentPath;
            }

            if (Schema::hasColumn('task_comments', 'attachment')) {
                $comment->attachment = $newAttachmentPath;
            }

            $comment->attachment_type = $this->detectAttachmentType($file);
        }

        if (array_key_exists('message', $validated)) {
            $comment->message = $validated['message'];
        }

        $comment->save();

        return response()->json([
            'message' => 'Comment updated successfully.',
            'comment' => $this->serializeComment($comment),
        ]);
    }

    public function destroy(Task $task, TaskComment $comment)
    {
        $this->authorizeTaskAccess($task);

        if (! Auth::user()->isAdmin() && Auth::id() !== $comment->user_id) {
            abort(403, 'You can only delete your own comments.');
        }

        $attachmentPath = $this->resolveAttachmentPath($comment);

        if ($attachmentPath && Storage::disk('public')->exists($attachmentPath)) {
            Storage::disk('public')->delete($attachmentPath);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully.']);
    }

    protected function authorizeTaskAccess(Task $task): void
    {
        $user = Auth::user();

        $hasTaskMembersTable = Schema::hasTable('task_user');

        if (! $user || (! $user->isAdmin() && $task->assigned_to !== $user->id && $task->created_by !== $user->id && ! ($hasTaskMembersTable && $task->members()->where('users.id', $user->id)->exists()))) {
            abort(403, 'You cannot access this task chat.');
        }
    }

    protected function serializeComment(TaskComment $comment): array
    {
        $path = $this->resolveAttachmentPath($comment);

        return [
            'id' => $comment->id,
            'message' => $comment->message,
            'attachment' => $path,
            'attachment_path' => $path,
            'attachment_type' => $comment->attachment_type,
            'attachment_url' => $path ? Storage::disk('public')->url($path) : null,
            'user' => [
                'id' => $comment->user->id,
                'name' => $comment->user->name,
            ],
            'is_owner' => Auth::id() === $comment->user_id || Auth::user()->isAdmin(),
            'created_at' => $comment->created_at->format('Y-m-d H:i:s'),
        ];
    }

    protected function resolveAttachmentPath(TaskComment $comment): ?string
    {
        return $comment->attachment_path ?? $comment->attachment ?? null;
    }

    protected function detectAttachmentType($file): string
    {
        $mime = strtolower($file->getMimeType() ?? '');
        $extension = strtolower($file->getClientOriginalExtension() ?? '');

        $imageExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
        $audioExtensions = ['mp3', 'wav', 'ogg', 'm4a', 'aac'];

        if (in_array($extension, $imageExtensions, true) || str_starts_with($mime, 'image/')) {
            return 'image';
        }

        if (in_array($extension, $audioExtensions, true) || str_starts_with($mime, 'audio/')) {
            return 'audio';
        }

        return 'document';
    }
}
