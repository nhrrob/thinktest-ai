# GitHub Integration Setup Guide

This guide will help you set up and configure the GitHub integration feature in ThinkTest AI.

## Overview

The GitHub integration allows users to:
- Connect GitHub repositories directly to ThinkTest AI
- Select specific branches for analysis
- Process both public and private repositories
- Automatically detect WordPress plugin structures
- Generate tests directly from repository content

## Prerequisites

1. **GitHub Account**: You need a GitHub account to access repositories
2. **GitHub API Token**: Required for accessing private repositories and higher rate limits
3. **GitHub OAuth App** (Optional): For enhanced user authentication

## Setup Instructions

### 1. GitHub API Token Setup

#### Creating a Personal Access Token

1. **Navigate to GitHub Settings**
   - Go to [GitHub Settings](https://github.com/settings/tokens)
   - Click on "Developer settings" in the left sidebar
   - Click on "Personal access tokens" > "Tokens (classic)"

2. **Generate New Token**
   - Click "Generate new token" > "Generate new token (classic)"
   - Give your token a descriptive name (e.g., "ThinkTest AI Integration")
   - Set expiration as needed (recommended: 90 days or no expiration for production)

3. **Select Scopes**
   For public repositories only:
   - ✅ `public_repo` - Access public repositories

   For private repositories:
   - ✅ `repo` - Full control of private repositories
   - ✅ `repo:status` - Access commit status
   - ✅ `repo_deployment` - Access deployment status

4. **Generate and Copy Token**
   - Click "Generate token"
   - **Important**: Copy the token immediately as it won't be shown again
   - Store it securely

#### Adding Token to Environment

Add the token to your `.env` file:
```env
GITHUB_API_TOKEN=ghp_your_token_here
```

### 2. GitHub OAuth App Setup (Optional)

For enhanced authentication and private repository access:

#### Creating a GitHub OAuth App

1. **Navigate to GitHub Settings**
   - Go to [GitHub Settings](https://github.com/settings/applications/new)
   - Click "New OAuth App"

2. **Configure OAuth App**
   ```
   Application name: ThinkTest AI
   Homepage URL: https://your-domain.com
   Application description: AI-powered WordPress plugin testing platform
   Authorization callback URL: https://your-domain.com/auth/github/callback
   ```

3. **Get Client Credentials**
   - After creating the app, note the "Client ID"
   - Generate a new "Client Secret"

#### Adding OAuth Credentials

Add to your `.env` file:
```env
GITHUB_CLIENT_ID=your_client_id_here
GITHUB_CLIENT_SECRET=your_client_secret_here
GITHUB_OAUTH_REDIRECT_URI="${APP_URL}/auth/github/callback"
```

### 3. Configuration Options

#### Environment Variables

```env
# GitHub Integration Core Settings
GITHUB_INTEGRATION_ENABLED=true
GITHUB_API_TOKEN=your_token_here
GITHUB_CLIENT_ID=your_client_id
GITHUB_CLIENT_SECRET=your_client_secret
GITHUB_OAUTH_REDIRECT_URI="${APP_URL}/auth/github/callback"

# Repository Processing Limits
GITHUB_MAX_REPO_SIZE=52428800          # 50MB in bytes
GITHUB_MAX_FILES_PER_REPO=1000         # Maximum files to process
GITHUB_CLONE_TIMEOUT=300               # 5 minutes timeout

# Rate Limiting
GITHUB_RATE_LIMIT_PER_HOUR=500         # Requests per hour per user
GITHUB_RATE_LIMIT_PER_MINUTE=30        # Requests per minute per user

# Caching
GITHUB_CACHE_REPO_INFO_MINUTES=60      # Cache repository info
GITHUB_CACHE_BRANCHES_MINUTES=30       # Cache branch information
```

#### Application Configuration

The main configuration is in `config/thinktest_ai.php`:

```php
'github' => [
    'enabled' => env('GITHUB_INTEGRATION_ENABLED', true),
    'api_token' => env('GITHUB_API_TOKEN'),
    'client_id' => env('GITHUB_CLIENT_ID'),
    'client_secret' => env('GITHUB_CLIENT_SECRET'),
    
    // Security settings
    'max_repository_size' => env('GITHUB_MAX_REPO_SIZE', 52428800),
    'max_files_per_repo' => env('GITHUB_MAX_FILES_PER_REPO', 1000),
    'supported_file_extensions' => ['.php', '.js', '.css', '.json', '.md', '.txt'],
    'ignored_directories' => ['node_modules', 'vendor', '.git', '.github', 'tests'],
    
    // Rate limiting
    'rate_limit_requests_per_hour' => env('GITHUB_RATE_LIMIT_PER_HOUR', 500),
    'rate_limit_requests_per_minute' => env('GITHUB_RATE_LIMIT_PER_MINUTE', 30),
],
```

## Usage

### For End Users

1. **Access ThinkTest AI Dashboard**
   - Navigate to the ThinkTest AI application
   - Log in to your account

2. **Select GitHub Repository Source**
   - Choose "GitHub Repository" instead of "Upload File"
   - Enter a repository URL (e.g., `https://github.com/owner/repo`)

3. **Repository Validation**
   - The system will validate the repository URL
   - Check if the repository is accessible
   - Display repository information (name, description, size, language)

4. **Branch Selection**
   - Select from available branches
   - View commit information for each branch
   - Default branch is pre-selected

5. **Process Repository**
   - Choose AI provider (ChatGPT-5, Anthropic Claude)
   - Select testing framework (PHPUnit, Pest)
   - Click "Process Repository & Analyze"

### Supported Repository Formats

- **Single Plugin**: Repository containing a single WordPress plugin
- **Plugin Directory**: Repository with plugin files in subdirectories
- **Monorepo**: Repository containing multiple plugins
- **WordPress Project**: Full WordPress installation with plugins

## Security Features

### Rate Limiting
- Per-user rate limiting prevents API abuse
- Configurable limits for hourly and per-minute requests
- Automatic retry-after headers for rate limit responses

### Content Validation
- Repository size limits (default: 50MB)
- File count limits (default: 1000 files)
- File type validation (only supported extensions)
- Directory filtering (ignores node_modules, vendor, etc.)

### URL Security
- Comprehensive URL validation
- Protection against malicious URLs
- Domain whitelist (only GitHub.com allowed)
- Pattern detection for suspicious content

## Troubleshooting

### Common Issues

1. **"Repository not found" Error**
   - Check if the repository URL is correct
   - Ensure the repository is public or you have access
   - Verify your GitHub token has the correct permissions

2. **Rate Limit Exceeded**
   - Wait for the rate limit to reset
   - Consider upgrading your GitHub token permissions
   - Contact administrator to increase rate limits

3. **Repository Too Large**
   - Repository exceeds the 50MB size limit
   - Consider processing specific directories only
   - Contact administrator to increase size limits

4. **Authentication Issues**
   - Verify your GitHub token is valid and not expired
   - Check token permissions include required scopes
   - Ensure OAuth app credentials are correct

### Error Messages

- **"Invalid repository URL format"**: Check URL format
- **"Repository size exceeds maximum"**: Repository is too large
- **"Too many requests"**: Rate limit exceeded
- **"Access denied"**: Insufficient permissions

## Best Practices

1. **Token Security**
   - Never commit tokens to version control
   - Use environment variables for all credentials
   - Rotate tokens regularly
   - Use minimal required permissions

2. **Rate Limiting**
   - Monitor API usage
   - Implement proper caching
   - Use webhooks for real-time updates when possible

3. **Error Handling**
   - Provide clear error messages to users
   - Log detailed errors for debugging
   - Implement retry mechanisms for transient failures

## Support

For issues with GitHub integration:
1. Check the application logs for detailed error messages
2. Verify your GitHub token permissions
3. Test with a simple public repository first
4. Contact support with specific error messages and repository URLs
