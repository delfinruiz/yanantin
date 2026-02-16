<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Broadcast;
use Pusher\Pusher;

class TestSoketi extends Command
{
    protected $signature = 'test:soketi';
    protected $description = 'Test connection to Soketi server';

    public function handle()
    {
        $this->info('Testing Soketi Connection...');

        $config = config('broadcasting.connections.pusher');
        $this->info('Configuration loaded:');
        $this->table(
            ['Key', 'Value'],
            [
                ['Driver', $config['driver']],
                ['Key', $config['key']],
                ['Host', $config['options']['host'] ?? 'N/A'],
                ['Port', $config['options']['port'] ?? 'N/A'],
                ['Scheme', $config['options']['scheme'] ?? 'N/A'],
                ['Cluster', $config['options']['cluster'] ?? 'N/A'],
                ['Encrypted', $config['options']['encrypted'] ? 'true' : 'false'],
                ['UseTLS', $config['options']['useTLS'] ? 'true' : 'false'],
            ]
        );

        $this->info('Attempting to connect and trigger event...');

        try {
            $pusher = new Pusher(
                $config['key'],
                $config['secret'],
                $config['app_id'],
                $config['options']
            );

            $payload = ['message' => 'Hello from Laravel CLI ' . now()];
            
            // Trigger on a test channel
            $response = $pusher->trigger('test-channel', 'test-event', $payload);

            if ($response) {
                $this->info('✅ Success! Event triggered successfully.');
                $this->info('Response: ' . print_r($response, true));
            } else {
                $this->error('❌ Failed to trigger event (no exception, but false response).');
            }

        } catch (\Exception $e) {
            $this->error('❌ Exception occurred:');
            $this->error($e->getMessage());
            $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
        }
    }
}
