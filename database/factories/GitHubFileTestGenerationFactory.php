<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GitHubFileTestGeneration>
 */
class GitHubFileTestGenerationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fileContent = '<?php echo "Hello World"; ?>';
        $fileName = $this->faker->randomElement(['index.php', 'class.php', 'functions.php', 'helper.php']);

        return [
            'user_id' => \App\Models\User::factory(),
            'github_repository_id' => \App\Models\GitHubRepository::factory(),
            'file_path' => $this->faker->randomElement(['', 'src/', 'includes/']) . $fileName,
            'file_name' => $fileName,
            'file_sha' => $this->faker->sha1,
            'file_size' => strlen($fileContent),
            'branch' => $this->faker->randomElement(['main', 'master', 'develop']),
            'provider' => $this->faker->randomElement(['openai-gpt5', 'anthropic-claude']),
            'framework' => $this->faker->randomElement(['phpunit', 'pest']),
            'generated_tests' => 'Generated test content for ' . $fileName,
            'test_suite' => [
                'main_test_file' => [
                    'filename' => str_replace('.php', 'Test.php', $fileName),
                    'content' => 'Test content',
                    'description' => 'Main test file',
                ],
            ],
            'analysis_data' => [
                'functions' => [],
                'classes' => [],
                'hooks' => [],
                'complexity_score' => $this->faker->numberBetween(1, 10),
            ],
            'file_content_hash' => hash('sha256', $fileContent),
            'generation_status' => $this->faker->randomElement(['completed', 'failed', 'pending']),
            'generation_error' => null,
            'generated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the generation was successful.
     */
    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'generation_status' => 'completed',
            'generation_error' => null,
            'generated_at' => now(),
        ]);
    }

    /**
     * Indicate that the generation failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'generation_status' => 'failed',
            'generation_error' => 'Test generation failed due to API error',
            'generated_tests' => null,
            'test_suite' => null,
            'generated_at' => null,
        ]);
    }
}
