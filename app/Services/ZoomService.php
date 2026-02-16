<?php

namespace App\Services;

use Jubaer\Zoom\Zoom;
use GuzzleHttp\Client;

/**
 * @property Client $client
 */
class ZoomService extends Zoom
{
    public function addRegistrant(string $meetingId, array $registrantData)
    {
        try {
            $response = $this->client->request('POST', "meetings/{$meetingId}/registrants", [
                'json' => $registrantData,
            ]);
            
            return [
                'status' => true,
                'data' => json_decode($response->getBody(), true),
            ];
        } catch (\Throwable $th) {
             return [
                'status' => false,
                'message' => $th->getMessage(),
            ];
        }
    }

    public function updateRegistrantStatus(string $meetingId, array $registrants, string $action)
    {
         try {
            // registrants should be an array of ['email' => '...']
            $response = $this->client->request('PUT', "meetings/{$meetingId}/registrants/status", [
                'json' => [
                    'action' => $action, // cancel, approve, deny
                    'registrants' => $registrants
                ],
            ]);
            
            return [
                'status' => true,
                'data' => $response->getStatusCode() === 204,
            ];
        } catch (\Throwable $th) {
             return [
                'status' => false,
                'message' => $th->getMessage(),
            ];
        }
    }

    public function listRegistrants(string $meetingId)
    {
        try {
            $response = $this->client->request('GET', "meetings/{$meetingId}/registrants");
            
            return [
                'status' => true,
                'data' => json_decode($response->getBody(), true),
            ];
        } catch (\Throwable $th) {
             return [
                'status' => false,
                'message' => $th->getMessage(),
            ];
        }
    }

    public function deleteMeeting(string $meetingId, array $query = [])
    {
        try {
            $response = $this->client->request('DELETE', 'meetings/' . $meetingId, [
                'query' => $query
            ]);

            if ($response->getStatusCode() === 204) {
                return [
                    'status' => true,
                    'message' => 'Meeting Deleted Successfully',
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'Something went wrong',
                ];
            }
        } catch (\Throwable $th) {
            return [
                'status' => false,
                'message' => $th->getMessage(),
            ];
        }
    }
}
