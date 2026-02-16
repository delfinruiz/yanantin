<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SettingService
{
    public function getSettings()
    {
        return Cache::rememberForever('app_settings', function () {
            // Check if table exists to avoid errors during initial migrations
            if (!Schema::hasTable('settings')) {
                return null;
            }
            
            return Setting::first() ?? Setting::create([
                'timezone' => config('app.timezone', 'UTC'),
                'company_name' => config('app.name', 'Finanzas Personales'),
            ]);
        });
    }

    public function get($key, $default = null)
    {
        $settings = $this->getSettings();
        return $settings ? ($settings->$key ?? $default) : $default;
    }

    public function update(array $data)
    {
        $settings = Setting::firstOrNew();
        
        // Handle file cleanup for replaced images
        if (isset($data['logo_light']) && $settings->logo_light && $settings->logo_light !== $data['logo_light']) {
            Storage::disk('public')->delete($settings->logo_light);
        }
        
        if (isset($data['logo_dark']) && $settings->logo_dark && $settings->logo_dark !== $data['logo_dark']) {
            Storage::disk('public')->delete($settings->logo_dark);
        }

        if (isset($data['favicon']) && $settings->favicon && $settings->favicon !== $data['favicon']) {
            Storage::disk('public')->delete($settings->favicon);
        }

        $settings->fill($data);
        $settings->save();
        
        Cache::forget('app_settings');
        
        return $settings;
    }
    
    public function clearCache()
    {
        Cache::forget('app_settings');
    }
}
