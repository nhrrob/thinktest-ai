<?php

namespace App\Services\Dashboard;

use App\Models\AIConversationState;
use App\Models\GitHubFileTestGeneration;
use App\Models\GitHubRepository;
use App\Models\PluginAnalysisResult;
use App\Models\User;
use App\Models\UserApiToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardStatsService
{
    /**
     * Get comprehensive dashboard statistics for a user.
     */
    public function getUserStats(User $user): array
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        return [
            'overview' => $this->getOverviewStats($user),
            'monthly' => $this->getMonthlyStats($user, $currentMonth),
            'recent_activity' => $this->getRecentActivity($user),
            'provider_usage' => $this->getProviderUsage($user),
            'trends' => $this->getTrendData($user, $lastMonth, $currentMonth),
        ];
    }

    /**
     * Get overview statistics.
     */
    private function getOverviewStats(User $user): array
    {
        return [
            'total_tests_generated' => $this->getTotalTestsGenerated($user),
            'total_repositories_processed' => $this->getTotalRepositoriesProcessed($user),
            'total_files_analyzed' => $this->getTotalFilesAnalyzed($user),
            'active_api_tokens' => $this->getActiveApiTokensCount($user),
        ];
    }

    /**
     * Get monthly statistics.
     */
    private function getMonthlyStats(User $user, Carbon $currentMonth): array
    {
        return [
            'tests_this_month' => $this->getTestsGeneratedInPeriod($user, $currentMonth, Carbon::now()),
            'repositories_this_month' => $this->getRepositoriesProcessedInPeriod($user, $currentMonth, Carbon::now()),
            'files_this_month' => $this->getFilesAnalyzedInPeriod($user, $currentMonth, Carbon::now()),
            'conversations_this_month' => $this->getConversationsInPeriod($user, $currentMonth, Carbon::now()),
        ];
    }

    /**
     * Get recent activity.
     */
    private function getRecentActivity(User $user): array
    {
        $recentTests = GitHubFileTestGeneration::where('user_id', $user->id)
            ->where('generation_status', 'completed')
            ->with('githubRepository')
            ->orderBy('generated_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($test) {
                return [
                    'type' => 'test_generated',
                    'title' => "Generated tests for {$test->file_name}",
                    'description' => "Repository: {$test->githubRepository->full_name}",
                    'timestamp' => $test->generated_at,
                    'provider' => $test->provider,
                    'framework' => $test->framework,
                ];
            });

        $recentAnalyses = PluginAnalysisResult::where('user_id', $user->id)
            ->orderBy('analyzed_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($analysis) {
                return [
                    'type' => 'plugin_analyzed',
                    'title' => "Analyzed {$analysis->filename}",
                    'description' => "Complexity score: {$analysis->complexity_score}",
                    'timestamp' => $analysis->analyzed_at,
                ];
            });

        $recentRepositories = GitHubRepository::where('user_id', $user->id)
            ->where('processing_status', 'completed')
            ->orderBy('processed_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($repo) {
                return [
                    'type' => 'repository_processed',
                    'title' => "Processed {$repo->full_name}",
                    'description' => "Files: {$repo->file_count}, Language: {$repo->language}",
                    'timestamp' => $repo->processed_at,
                ];
            });

        // Merge and sort all activities
        $allActivities = collect()
            ->merge($recentTests)
            ->merge($recentAnalyses)
            ->merge($recentRepositories)
            ->sortByDesc('timestamp')
            ->take(10)
            ->values();

        return $allActivities->toArray();
    }

    /**
     * Get provider usage statistics.
     */
    private function getProviderUsage(User $user): array
    {
        $providerStats = GitHubFileTestGeneration::where('user_id', $user->id)
            ->where('generation_status', 'completed')
            ->select('provider', DB::raw('count(*) as count'))
            ->groupBy('provider')
            ->get()
            ->pluck('count', 'provider')
            ->toArray();

        $conversationStats = AIConversationState::where('user_id', $user->id)
            ->where('status', 'completed')
            ->select('provider', DB::raw('count(*) as count'))
            ->groupBy('provider')
            ->get()
            ->pluck('count', 'provider')
            ->toArray();

        // Merge stats from both sources
        $totalStats = [];
        foreach (array_merge(array_keys($providerStats), array_keys($conversationStats)) as $provider) {
            $totalStats[$provider] = ($providerStats[$provider] ?? 0) + ($conversationStats[$provider] ?? 0);
        }

        return $totalStats;
    }

    /**
     * Get trend data comparing current month to previous month.
     */
    private function getTrendData(User $user, Carbon $lastMonth, Carbon $currentMonth): array
    {
        $lastMonthEnd = $currentMonth->copy()->subSecond();
        $currentMonthEnd = Carbon::now();

        $lastMonthTests = $this->getTestsGeneratedInPeriod($user, $lastMonth, $lastMonthEnd);
        $currentMonthTests = $this->getTestsGeneratedInPeriod($user, $currentMonth, $currentMonthEnd);

        $lastMonthRepos = $this->getRepositoriesProcessedInPeriod($user, $lastMonth, $lastMonthEnd);
        $currentMonthRepos = $this->getRepositoriesProcessedInPeriod($user, $currentMonth, $currentMonthEnd);

        return [
            'tests_trend' => $this->calculateTrend($lastMonthTests, $currentMonthTests),
            'repositories_trend' => $this->calculateTrend($lastMonthRepos, $currentMonthRepos),
        ];
    }

    /**
     * Calculate trend percentage.
     */
    private function calculateTrend(int $previous, int $current): array
    {
        if ($previous === 0) {
            $percentage = $current > 0 ? 100 : 0;
        } else {
            $percentage = round((($current - $previous) / $previous) * 100, 1);
        }

        return [
            'previous' => $previous,
            'current' => $current,
            'percentage' => $percentage,
            'direction' => $percentage > 0 ? 'up' : ($percentage < 0 ? 'down' : 'stable'),
        ];
    }

    /**
     * Get total tests generated by user.
     */
    private function getTotalTestsGenerated(User $user): int
    {
        return GitHubFileTestGeneration::where('user_id', $user->id)
            ->where('generation_status', 'completed')
            ->count() + 
            AIConversationState::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();
    }

    /**
     * Get total repositories processed by user.
     */
    private function getTotalRepositoriesProcessed(User $user): int
    {
        return GitHubRepository::where('user_id', $user->id)
            ->where('processing_status', 'completed')
            ->count();
    }

    /**
     * Get total files analyzed by user.
     */
    private function getTotalFilesAnalyzed(User $user): int
    {
        return PluginAnalysisResult::where('user_id', $user->id)->count() +
            GitHubFileTestGeneration::where('user_id', $user->id)->count();
    }

    /**
     * Get active API tokens count.
     */
    private function getActiveApiTokensCount(User $user): int
    {
        return UserApiToken::where('user_id', $user->id)
            ->where('is_active', true)
            ->count();
    }

    /**
     * Get tests generated in a specific period.
     */
    private function getTestsGeneratedInPeriod(User $user, Carbon $start, Carbon $end): int
    {
        return GitHubFileTestGeneration::where('user_id', $user->id)
            ->where('generation_status', 'completed')
            ->whereBetween('generated_at', [$start, $end])
            ->count() +
            AIConversationState::where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$start, $end])
            ->count();
    }

    /**
     * Get repositories processed in a specific period.
     */
    private function getRepositoriesProcessedInPeriod(User $user, Carbon $start, Carbon $end): int
    {
        return GitHubRepository::where('user_id', $user->id)
            ->where('processing_status', 'completed')
            ->whereBetween('processed_at', [$start, $end])
            ->count();
    }

    /**
     * Get files analyzed in a specific period.
     */
    private function getFilesAnalyzedInPeriod(User $user, Carbon $start, Carbon $end): int
    {
        return PluginAnalysisResult::where('user_id', $user->id)
            ->whereBetween('analyzed_at', [$start, $end])
            ->count() +
            GitHubFileTestGeneration::where('user_id', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    /**
     * Get conversations in a specific period.
     */
    private function getConversationsInPeriod(User $user, Carbon $start, Carbon $end): int
    {
        return AIConversationState::where('user_id', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }
}
