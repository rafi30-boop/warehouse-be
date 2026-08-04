<?php

namespace App\Services;

use App\Models\AktivitasLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AktivitasLogService
{
    private const SENSITIVE_FIELDS = ['password', 'remember_token'];

    public static function log(Model $model, string $action): void
    {
        $class = get_class($model);

        if (! str_starts_with($class, 'App\\Models\\') || $class === AktivitasLog::class) {
            return;
        }

        $changes = $model->getChanges();

        if ($action === 'update' && empty($changes)) {
            return;
        }

        if ($class === User::class && $action === 'update' && array_keys($changes) === ['last_login_at']) {
            return;
        }

        $dataOld = null;
        $dataNew = null;

        if ($action === 'create') {
            $dataNew = self::sanitize($model->getAttributes());
        } elseif ($action === 'update') {
            foreach ($changes as $key => $value) {
                $dataNew[$key] = $value;
                $dataOld[$key] = $model->getOriginal($key);
            }
        } elseif ($action === 'delete') {
            $dataOld = self::sanitize($model->getAttributes());
        }

        $request = request();

        AktivitasLog::create([
            'user_id' => Auth::guard('api')->id() ?? $request->user()?->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'action' => $action,
            'model' => class_basename($model),
            'model_id' => $model->getKey(),
            'data_old' => $dataOld,
            'data_new' => $dataNew,
        ]);
    }

    private static function sanitize(array $data): array
    {
        foreach (self::SENSITIVE_FIELDS as $field) {
            unset($data[$field]);
        }

        return $data;
    }
}
