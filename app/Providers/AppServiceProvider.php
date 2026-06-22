<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // @money($value) → "1,234.5 ₪" (يحذف الأصفار العشرية الزائدة)
        Blade::directive('money', function ($expr) {
            return "<?php echo rtrim(rtrim(number_format((float) ($expr), 2), '0'), '.') . ' ₪'; ?>";
        });
    }
}
