<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Jubaer\Zoom\Facades\Zoom;

class TestZoomConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-zoom';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Zoom API connection';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Zoom connection...');

        try {
            // The package uses getAllMeeting() (singular)
            $response = Zoom::getAllMeeting();

            if ($response['status']) {
                $this->info('Connection successful!');
                $this->info('Retrieved ' . count($response['data']['meetings'] ?? []) . ' meetings.');
                
                // Show raw data for debug if needed, or just first one
                if (!empty($response['data']['meetings'])) {
                    $this->info('First meeting: ' . $response['data']['meetings'][0]['topic']);
                }
            } else {
                $this->error('Connection failed.');
                $this->error('Message: ' . ($response['message'] ?? 'Unknown error'));
                // If it's a Guzzle exception wrapped
                if (isset($response['data'])) {
                     $this->error(json_encode($response['data'], JSON_PRETTY_PRINT));
                }
            }
        } catch (\Exception $e) {
            $this->error('Exception: ' . $e->getMessage());
        }
    }
}
