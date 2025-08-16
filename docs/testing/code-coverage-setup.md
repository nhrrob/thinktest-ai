# Code Coverage Setup Guide

## Overview

This guide explains how to set up and use code coverage analysis for the ThinkTest AI project.

## Coverage Configuration

### PHPUnit Configuration

The `phpunit.xml` file has been configured with:

- **Source Directory**: `app/` (with exclusions for Console and Kernel)
- **Coverage Reports**: HTML, Text, and Clover formats
- **Thresholds**: 50% low, 80% high
- **Output**: `coverage-report/` directory for HTML, `coverage.xml` for Clover

### GitHub Actions Integration

The CI pipeline (`.github/workflows/tests.yml`) includes:

- **Xdebug**: Configured for coverage collection
- **Coverage Threshold**: 80% minimum requirement
- **Codecov Integration**: Automatic upload of coverage reports
- **Fail Conditions**: CI fails if coverage drops below 80%

## Local Development Setup

### Installing Coverage Driver

Choose one of the following options:

#### Option 1: PCOV (Recommended for speed)
```bash
# macOS with Homebrew
brew install pcov

# Ubuntu/Debian
sudo apt-get install php-pcov

# Or via PECL
pecl install pcov
```

#### Option 2: Xdebug (More features, slower)
```bash
# macOS with Homebrew
brew install xdebug

# Ubuntu/Debian
sudo apt-get install php-xdebug

# Or via PECL
pecl install xdebug
```

### Running Coverage Analysis

```bash
# Run tests with coverage report
php artisan test --coverage

# Run with minimum threshold check
php artisan test --coverage --min=80

# Generate HTML coverage report
./vendor/bin/pest --coverage-html coverage-report

# Generate Clover XML for CI
./vendor/bin/pest --coverage-clover coverage.xml
```

## Coverage Targets

### Current Targets
- **Overall Coverage**: 80% minimum
- **Critical Components**: 90%+ target
  - Authentication & Authorization
  - Payment Processing
  - AI Provider Services
  - File Processing

### Coverage Exclusions
- Console commands (excluded in phpunit.xml)
- HTTP Kernel (excluded in phpunit.xml)
- Configuration files
- Database migrations
- Blade templates

## Interpreting Coverage Reports

### HTML Report
- Open `coverage-report/index.html` in browser
- Green: Well covered (>80%)
- Yellow: Moderate coverage (50-80%)
- Red: Poor coverage (<50%)

### Text Report
- Summary displayed in terminal
- Shows percentage by directory
- Highlights uncovered files

### CI Integration
- Coverage reports uploaded to Codecov
- Pull requests show coverage diff
- Failing coverage blocks merges

## Best Practices

### Writing Testable Code
1. **Dependency Injection**: Use constructor injection for testability
2. **Single Responsibility**: Keep methods focused and small
3. **Avoid Static Calls**: Use facades or inject dependencies
4. **Return Values**: Prefer return values over void methods

### Improving Coverage
1. **Unit Tests**: Test individual methods and classes
2. **Feature Tests**: Test complete user workflows
3. **Edge Cases**: Test error conditions and boundary values
4. **Mock External Services**: Use mocks for APIs and external dependencies

### Coverage Quality
- **Line Coverage**: Measures executed lines
- **Branch Coverage**: Measures decision paths
- **Method Coverage**: Measures called methods
- **Class Coverage**: Measures instantiated classes

## Troubleshooting

### Common Issues

#### "Code coverage driver not available"
- Install Xdebug or PCOV extension
- Verify extension is loaded: `php -m | grep -E "(xdebug|pcov)"`

#### Slow coverage generation
- Use PCOV instead of Xdebug for coverage-only runs
- Exclude unnecessary directories in phpunit.xml

#### Memory issues
- Increase PHP memory limit: `php -d memory_limit=512M artisan test --coverage`
- Use `--coverage-filter` to limit scope

### Performance Tips
- Use PCOV for coverage, Xdebug for debugging
- Run coverage on CI, not every local test run
- Use `--parallel` flag for faster test execution
- Cache coverage results when possible

## Integration with IDEs

### PHPStorm
1. Configure PHP interpreter with Xdebug
2. Set up test configuration with coverage
3. View coverage in editor gutters

### VS Code
1. Install PHP Debug extension
2. Configure launch.json for coverage
3. Use Coverage Gutters extension for visualization

## Maintenance

### Regular Tasks
- Review coverage reports weekly
- Update coverage targets quarterly
- Exclude new non-testable code appropriately
- Monitor coverage trends over time

### Coverage Goals
- **Phase 1**: Achieve 80% overall coverage
- **Phase 2**: Improve critical path coverage to 90%
- **Phase 3**: Maintain coverage while adding features
- **Phase 4**: Implement mutation testing for quality assurance
