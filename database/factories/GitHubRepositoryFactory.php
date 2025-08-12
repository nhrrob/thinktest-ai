<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GitHubRepository>
 */
class GitHubRepositoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $owner = $this->faker->userName;
        $repo = $this->faker->slug;

        return [
            'user_id' => \App\Models\User::factory(),
            'owner' => $owner,
            'repo' => $repo,
            'full_name' => "{$owner}/{$repo}",
            'branch' => $this->faker->randomElement(['main', 'master', 'develop']),
            'github_id' => $this->faker->numberBetween(1000, 999999),
            'description' => $this->faker->sentence,
            'is_private' => $this->faker->boolean(20), // 20% chance of being private
            'default_branch' => $this->faker->randomElement(['main', 'master', 'develop']),
            'size_bytes' => $this->faker->numberBetween(100000, 50000000), // Size in bytes
            'language' => $this->faker->randomElement(['PHP', 'JavaScript', 'Python', 'Java', 'TypeScript']),
            'languages' => $this->faker->optional()->randomElements(['PHP', 'JavaScript', 'CSS', 'HTML'], $this->faker->numberBetween(1, 3)),
            'clone_url' => "https://github.com/{$owner}/{$repo}.git",
            'html_url' => "https://github.com/{$owner}/{$repo}",
            'last_updated_at' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
            'plugin_structure' => $this->faker->optional()->randomElements(['classes', 'functions', 'hooks'], $this->faker->numberBetween(1, 3)),
            'file_count' => $this->faker->numberBetween(1, 100),
            'processing_status' => $this->faker->randomElement(['pending', 'processing', 'completed', 'failed']),
            'processing_error' => $this->faker->optional()->sentence,
            'processed_at' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the repository is public.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_private' => false,
        ]);
    }

    /**
     * Indicate that the repository is private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_private' => true,
        ]);
    }

    /**
     * Indicate that the repository has been processed recently.
     */
    public function recentlyProcessed(): static
    {
        return $this->state(fn (array $attributes) => [
            'processing_status' => 'completed',
            'processed_at' => now()->subHours($this->faker->numberBetween(1, 24)),
        ]);
    }
}
