#!/bin/bash

# ThinkTest AI Test Runner Script
# This script provides convenient commands for running tests and checking coverage

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to run tests with proper setup
run_tests() {
    local filter="$1"
    local options="$2"
    
    print_status "Setting up test environment..."
    
    # Clear caches
    php artisan cache:clear --quiet
    php artisan config:clear --quiet
    php artisan route:clear --quiet
    
    # Run migrations for test database
    php artisan migrate:fresh --env=testing --quiet --force
    
    print_status "Running tests..."
    
    if [ -n "$filter" ]; then
        php artisan test --filter="$filter" $options
    else
        php artisan test $options
    fi
}

# Function to run specific test categories
run_auth_tests() {
    print_status "Running authentication tests..."
    run_tests "Auth" "--stop-on-failure"
}

run_github_tests() {
    print_status "Running GitHub integration tests..."
    run_tests "GitHub" "--stop-on-failure"
}

run_feature_tests() {
    print_status "Running feature tests..."
    run_tests "Feature" ""
}

run_unit_tests() {
    print_status "Running unit tests..."
    run_tests "Unit" ""
}

# Function to check test coverage
check_coverage() {
    print_status "Generating test coverage report..."
    php artisan test --coverage --min=80
}

# Function to run critical tests only
run_critical_tests() {
    print_status "Running critical tests..."
    
    # List of critical test patterns
    critical_tests=(
        "AuthenticationTest"
        "RegistrationTest"
        "CsrfUtilityTest"
        "GitHubIntegrationTest"
        "ThinkTestAccessControlTest"
    )
    
    for test in "${critical_tests[@]}"; do
        print_status "Running $test..."
        run_tests "$test" "--stop-on-failure"
    done
}

# Function to fix common test issues
fix_test_issues() {
    print_status "Fixing common test issues..."
    
    # Create required roles for tests
    php artisan tinker --execute="
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    "
    
    print_success "Test environment prepared"
}

# Function to show test statistics
show_test_stats() {
    print_status "Gathering test statistics..."
    
    total_tests=$(find tests -name "*.php" | wc -l)
    feature_tests=$(find tests/Feature -name "*.php" 2>/dev/null | wc -l || echo 0)
    unit_tests=$(find tests/Unit -name "*.php" 2>/dev/null | wc -l || echo 0)
    
    echo ""
    echo "=== Test Statistics ==="
    echo "Total test files: $total_tests"
    echo "Feature tests: $feature_tests"
    echo "Unit tests: $unit_tests"
    echo ""
}

# Main script logic
case "$1" in
    "all")
        show_test_stats
        run_tests "" ""
        ;;
    "auth")
        run_auth_tests
        ;;
    "github")
        run_github_tests
        ;;
    "feature")
        run_feature_tests
        ;;
    "unit")
        run_unit_tests
        ;;
    "critical")
        run_critical_tests
        ;;
    "coverage")
        check_coverage
        ;;
    "fix")
        fix_test_issues
        ;;
    "stats")
        show_test_stats
        ;;
    *)
        echo "ThinkTest AI Test Runner"
        echo ""
        echo "Usage: $0 {command}"
        echo ""
        echo "Commands:"
        echo "  all       - Run all tests"
        echo "  auth      - Run authentication tests"
        echo "  github    - Run GitHub integration tests"
        echo "  feature   - Run feature tests"
        echo "  unit      - Run unit tests"
        echo "  critical  - Run critical tests only"
        echo "  coverage  - Generate coverage report"
        echo "  fix       - Fix common test issues"
        echo "  stats     - Show test statistics"
        echo ""
        echo "Examples:"
        echo "  $0 all"
        echo "  $0 auth"
        echo "  $0 critical"
        echo ""
        exit 1
        ;;
esac

print_success "Test execution completed"
