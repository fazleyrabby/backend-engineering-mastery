# ⚙️ Task Scheduling, Artisan Commands, Storage & Mail

This guide details task scheduling, custom Artisan CLI commands, file storage integrations, and asynchronous mail/notification delivery in Laravel.

---

## 💡 Conceptual Blueprint & First Principles

Real-world applications require background scheduling, command utilities, asset storage, and client notification loops:

```mermaid
graph TD
    Cron[System Cron / * * * * *] --> Schedule[Laravel Kernel Scheduler]
    Schedule --> Command[Custom Artisan Command]
    Command --> Storage[Write logs/reports to S3]
    Command --> Mail[Queue Notification/Email to user]
```

1. **Artisan CLI**: Custom developer/operational commands.
2. **Task Scheduler**: Replaces individual server-level cron tabs with a single master cron entry calling Laravel's schedule runner.
3. **File Storage**: Abstract storage engines (Local, AWS S3) via the unified Flysystem client API.
4. **Mail & Notifications**: Deliver multi-channel messages (Mail, database logs, SMS) asynchronously.

---

## 🔬 Under-the-Hood Mechanics

### The Schedule Loop
To run Laravel's scheduler, you configure one server-level system cron job:
`* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1`
* **Mechanics**: Every minute, the server executes `schedule:run`. Laravel checks all defined commands in your schedule configurations (e.g. `routes/console.php` or `Kernel.php`), compares their cron frequencies, and executes matching commands.

### Flysystem Storage Driver
Laravel wraps the Flysystem PHP package. Changing your environment variable `FILESYSTEM_DISK=s3` shifts file uploads from local filesystems to cloud object storage automatically without changing a single line of controller code.

---

## 💻 Production Code & Patterns

### 1. Custom Artisan Command
Generate command: `php artisan make:command CleanTempFiles`

```php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanTempFiles extends Command
{
    // CLI Command signature
    protected $signature = 'app:clean-temp-files {--hours=24 : Clean files older than X hours}';
    protected $description = 'Clean up temporary files from public storage';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $this->info("Cleaning files older than {$hours} hours...");

        // Dummy logic to purge files
        $files = Storage::disk('local')->files('temp');
        foreach ($files as $file) {
            if (now()->subHours($hours)->gt(Storage::disk('local')->lastModified($file))) {
                Storage::disk('local')->delete($file);
                $this->line("Deleted: {$file}");
            }
        }

        $this->info('Cleanup complete.');
        return Command::SUCCESS;
    }
}
```

### 2. Schedule Configuration
Define frequencies cleanly inside `routes/console.php` (Laravel 11+) or `app/Console/Kernel.php` (Laravel 10 and below):

```php
use Illuminate\Support\Facades\Schedule;

// Run the CleanTempFiles command daily at midnight
Schedule::command('app:clean-temp-files --hours=12')->daily()->withoutOverlapping();
```

### 3. File Upload to S3/Cloud storage
```php
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

public function uploadProfilePicture(UploadedFile $file, $userId): string
{
    // Stores file in a private directory inside AWS S3
    $path = $file->storeAs(
        "avatars/{$userId}", 
        'profile_' . time() . '.' . $file->getClientOriginalExtension(), 
        's3'
    );

    // Generate a temporary signed URL valid for 10 minutes
    return Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(10));
}
```

---

## ⚔️ Staff / Senior Interview Scenarios

### Q1: What is the purpose of `withoutOverlapping()` in the Laravel Scheduler, and how does it work?
* **Answer**: It prevents a scheduled command from starting if the previous execution instance is still running.
  * **Mechanics**: Laravel sets a cache lock when the command starts. If the next minute triggers and the lock exists, the execution is skipped. When the command successfully exits, the cache lock is released.
  * **Critical warning**: If a command crashes or the server is forced shut, the cache lock might persist. Be prepared to clear cache locks or define lock timeouts (`onOneServer()->expireAt(...)`).

### Q2: How do you send notification payloads asynchronously without blocking HTTP response times?
* **Answer**: Implement the `ShouldQueue` contract on your Notification or Mailable class.
  ```php
  use Illuminate\Contracts\Queue\ShouldQueue;
  use Illuminate\Notifications\Notification;

  class OrderShippedNotification extends Notification implements ShouldQueue
  {
      use Queueable;
      // ...
  }
  ```
  Laravel will automatically push the mail/notification rendering and dispatch steps to your configured background queue instead of running it inline during the client request.
