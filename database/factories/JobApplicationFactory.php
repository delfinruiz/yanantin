<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JobApplication>
 */
class JobApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_offer_id' => \App\Models\JobOffer::factory(),
            'user_id' => \App\Models\User::factory(),
            'applicant_name' => $this->faker->name,
            'applicant_email' => $this->faker->email,
            'applicant_phone' => $this->faker->phoneNumber,
            'cv_snapshot' => [],
            'status' => 'submitted',
            'submitted_at' => now(),
        ];
    }
}
