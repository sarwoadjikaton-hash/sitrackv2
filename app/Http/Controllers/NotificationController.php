<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\Letter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Throwable;

class NotificationController extends Controller
{
    /**
     * Ensure notification table exists in DB
     */
    protected function ensureTableExists(): void
    {
        try {
            if (!Schema::hasTable('app_notifications')) {
                Schema::create('app_notifications', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('letter_id')->nullable()->constrained('letters')->nullOnDelete();
                    $table->string('type')->default('new_letter_submission');
                    $table->string('title');
                    $table->text('message')->nullable();
                    $table->json('data')->nullable();
                    $table->boolean('is_read')->default(false);
                    $table->timestamp('read_at')->nullable();
                    $table->timestamps();
                });
            }
        } catch (Throwable $e) {
            // Ignore if creation is not permitted
        }
    }

    /**
     * Synchronize un-notified incoming letter submissions into app_notifications
     */
    protected function syncPendingLetterSubmissions(): void
    {
        try {
            if (!Schema::hasTable('app_notifications')) {
                return;
            }

            $existingLetterIds = AppNotification::whereNotNull('letter_id')
                ->pluck('letter_id')
                ->toArray();

            $pendingLetters = Letter::where(function ($query) {
                    $query->where('status', 'Pengajuan Berhasil')
                          ->orWhere(function ($q) {
                              $q->where('changed_by', 'Pemohon (Publik)')
                                ->orWhere('letter_source', 'Manual');
                          });
                })
                ->whereNotIn('id', $existingLetterIds)
                ->orderBy('created_at', 'desc')
                ->limit(30)
                ->get();

            foreach ($pendingLetters as $letter) {
                $senderUnit = $letter->sender_unit ?: 'Unit Pengusul';
                $senderName = $letter->sender_name ?: 'Pemohon';
                $subject = $letter->subject ?: 'Tanpa Perihal';

                AppNotification::create([
                    'letter_id' => $letter->id,
                    'type' => 'new_letter_submission',
                    'title' => 'Pengajuan Surat Masuk Baru',
                    'message' => "{$senderUnit} ({$senderName}) mengajukan permohonan naskah: \"{$subject}\"",
                    'data' => [
                        'tracking_code' => $letter->tracking_code,
                        'agenda_number' => $letter->agenda_number,
                        'sender_unit' => $senderUnit,
                        'sender_name' => $senderName,
                        'destination' => $letter->destination,
                        'subject' => $subject,
                        'priority' => $letter->priority ?? 'Biasa',
                        'created_at' => $letter->created_at ? $letter->created_at->toIso8601String() : now()->toIso8601String(),
                    ],
                    'is_read' => false,
                    'created_at' => $letter->created_at ?? now(),
                ]);
            }
        } catch (Throwable $e) {
            // Silently ignore sync failures
        }
    }

    /**
     * Get recent notifications and unread count
     */
    public function index(): JsonResponse
    {
        try {
            $this->ensureTableExists();
            $this->syncPendingLetterSubmissions();

            if (Schema::hasTable('app_notifications')) {
                $unreadCount = AppNotification::where('is_read', false)->count();

                $notifications = AppNotification::with(['letter:id,tracking_code,agenda_number,subject,sender_unit,sender_name,created_at'])
                    ->orderBy('created_at', 'desc')
                    ->limit(20)
                    ->get();

                return response()->json([
                    'ok' => true,
                    'unread_count' => $unreadCount,
                    'notifications' => $notifications,
                ]);
            }

            // Fallback direct from Letter if table cannot be used
            $readLetterIds = session()->get('read_letter_notifications', []);
            $letters = Letter::where(function ($query) {
                    $query->where('status', 'Pengajuan Berhasil')
                          ->orWhere('changed_by', 'Pemohon (Publik)')
                          ->orWhere('letter_source', 'Manual');
                })
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();

            $formatted = $letters->map(function ($letter) use ($readLetterIds) {
                $isRead = in_array($letter->id, $readLetterIds);
                $senderUnit = $letter->sender_unit ?: 'Unit Pengusul';
                $senderName = $letter->sender_name ?: 'Pemohon';
                $subject = $letter->subject ?: 'Tanpa Perihal';

                return [
                    'id' => $letter->id,
                    'letter_id' => $letter->id,
                    'type' => 'new_letter_submission',
                    'title' => 'Pengajuan Surat Masuk Baru',
                    'message' => "{$senderUnit} ({$senderName}) mengajukan permohonan naskah: \"{$subject}\"",
                    'data' => [
                        'tracking_code' => $letter->tracking_code,
                        'agenda_number' => $letter->agenda_number,
                        'sender_unit' => $senderUnit,
                        'sender_name' => $senderName,
                        'destination' => $letter->destination,
                        'subject' => $subject,
                        'priority' => $letter->priority ?? 'Biasa',
                        'created_at' => $letter->created_at ? $letter->created_at->toIso8601String() : now()->toIso8601String(),
                    ],
                    'is_read' => $isRead,
                    'created_at' => $letter->created_at ? $letter->created_at->toIso8601String() : now()->toIso8601String(),
                ];
            });

            $unreadCount = $formatted->where('is_read', false)->count();

            return response()->json([
                'ok' => true,
                'unread_count' => $unreadCount,
                'notifications' => $formatted->values(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'ok' => true,
                'unread_count' => 0,
                'notifications' => [],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Mark a specific notification as read
     */
    public function markAsRead(int $id): JsonResponse
    {
        try {
            $this->ensureTableExists();

            if (Schema::hasTable('app_notifications')) {
                $notification = AppNotification::find($id);
                if ($notification) {
                    $notification->update([
                        'is_read' => true,
                        'read_at' => now(),
                    ]);
                } else {
                    AppNotification::where('letter_id', $id)->update([
                        'is_read' => true,
                        'read_at' => now(),
                    ]);
                }
            }

            $readLetterIds = session()->get('read_letter_notifications', []);
            $readLetterIds[] = (int) $id;
            session()->put('read_letter_notifications', array_unique($readLetterIds));

            $unreadCount = 0;
            if (Schema::hasTable('app_notifications')) {
                $unreadCount = AppNotification::where('is_read', false)->count();
            }

            return response()->json([
                'ok' => true,
                'unread_count' => $unreadCount,
            ]);
        } catch (Throwable $e) {
            return response()->json(['ok' => true, 'unread_count' => 0]);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): JsonResponse
    {
        try {
            $this->ensureTableExists();

            if (Schema::hasTable('app_notifications')) {
                AppNotification::where('is_read', false)->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);
            }

            $allLetterIds = Letter::pluck('id')->toArray();
            session()->put('read_letter_notifications', $allLetterIds);

            return response()->json([
                'ok' => true,
                'unread_count' => 0,
            ]);
        } catch (Throwable $e) {
            return response()->json(['ok' => true, 'unread_count' => 0]);
        }
    }
}
