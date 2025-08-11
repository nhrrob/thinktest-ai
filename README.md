# ThinkTest AI

ThinkTest AI is an intelligent WordPress plugin testing platform that automatically generates comprehensive test suites using advanced AI models. The platform supports both file uploads and GitHub repository integration for seamless testing workflows.

## Features

### Core Functionality
- **AI-Powered Test Generation**: Automatically generate comprehensive test suites for WordPress plugins using advanced AI models
- **Multiple AI Providers**: Support for OpenAI GPT-4, ChatGPT-5, and Anthropic Claude
- **Framework Flexibility**: Generate tests for both PHPUnit and Pest testing frameworks
- **Plugin Analysis**: Deep analysis of WordPress plugin structure and functionality
- **User Management**: Secure user authentication and role-based access control
- **Test Coverage**: Comprehensive test coverage analysis and reporting

### GitHub Integration
- **Repository URL Validation**: Intelligent validation of GitHub repository URLs with security checks
- **Branch Selection**: Dynamic branch selection with commit information
- **Automatic Plugin Detection**: Smart detection of WordPress plugin structure in repositories
- **Public & Private Repository Support**: Access both public and private repositories with proper authentication
- **Rate Limiting**: Built-in rate limiting to prevent API abuse
- **Security Measures**: Comprehensive security validation and sanitization
- **Real-time Processing**: Live repository processing with progress feedback

### Security Features
- **URL Validation**: Comprehensive validation of GitHub URLs with security pattern detection
- **Rate Limiting**: Per-user rate limiting for GitHub API requests
- **Content Sanitization**: Automatic sanitization of repository content
- **Size Limits**: Repository and file count limits to prevent abuse
- **Error Handling**: Robust error handling with user-friendly messages

## Installation

### Prerequisites
- PHP 8.2 or higher
- Laravel 11.x
- Node.js 18+ and npm
- MySQL/PostgreSQL database
- GitHub API token (optional, for private repositories)

### Setup Instructions

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-username/thinktest-ai.git
   cd thinktest-ai
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment Configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure Environment Variables**
   Edit `.env` file with your settings:
   ```env
   # Database Configuration
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=thinktest-ai
   DB_USERNAME=your_username
   DB_PASSWORD=your_password

   # AI Provider Configuration
   OPENAI_API_KEY=your_openai_api_key
   ANTHROPIC_API_KEY=your_anthropic_api_key

   # GitHub Integration
   GITHUB_API_TOKEN=your_github_token
   GITHUB_CLIENT_ID=your_github_client_id
   GITHUB_CLIENT_SECRET=your_github_client_secret
   ```

6. **Database Setup**
   ```bash
   php artisan migrate --seed
   ```

7. **Build Assets**
   ```bash
   npm run build
   ```

8. **Start Development Server**
   ```bash
   php artisan serve
   npm run dev
   ```

## GitHub Integration Setup

### GitHub API Token
1. Go to GitHub Settings > Developer settings > Personal access tokens
2. Generate a new token with the following scopes:
   - `repo` (for private repositories)
   - `public_repo` (for public repositories)
3. Add the token to your `.env` file as `GITHUB_API_TOKEN`

### GitHub OAuth (Optional)
For enhanced private repository access:
1. Create a GitHub OAuth App in your GitHub settings
2. Set the authorization callback URL to: `http://your-domain.com/auth/github/callback`
3. Add the client ID and secret to your `.env` file

## Usage

### File Upload Method
1. Navigate to the ThinkTest AI dashboard
2. Select "Upload File" as your source
3. Upload a WordPress plugin file (.php) or ZIP archive
4. Choose your AI provider and testing framework
5. Click "Analyze & Generate Tests"

### GitHub Repository Method
1. Navigate to the ThinkTest AI dashboard
2. Select "GitHub Repository" as your source
3. Enter a GitHub repository URL (e.g., `https://github.com/owner/repo`)
4. Select the branch you want to analyze
5. Choose your AI provider and testing framework
6. Click "Process Repository & Analyze"

### Supported Repository Formats
- Single WordPress plugin files
- WordPress plugin directories
- Monorepo structures with multiple plugins
- ZIP archives containing plugin files

## Configuration

### GitHub Integration Settings
Configure GitHub integration in `config/thinktest_ai.php`:

```php
'github' => [
    'enabled' => true,
    'max_repository_size' => 52428800, // 50MB
    'max_files_per_repo' => 1000,
    'rate_limit_requests_per_hour' => 100,
    'rate_limit_requests_per_minute' => 10,
    'supported_file_extensions' => ['.php', '.js', '.css', '.json'],
    'ignored_directories' => ['node_modules', 'vendor', '.git'],
],
```

## Testing

Run the test suite:
```bash
php artisan test
```

Run specific test groups:
```bash
php artisan test --filter=GitHubIntegrationTest
```

## Security

### Rate Limiting
- Per-user rate limiting for GitHub API requests
- Configurable limits for hourly and per-minute requests
- Automatic retry-after headers for rate limit responses

### Content Validation
- Comprehensive URL validation with security pattern detection
- Repository size and file count limits
- Content sanitization to remove malicious code
- Validation of GitHub usernames and repository names

### Error Handling
- User-friendly error messages for common scenarios
- Detailed logging for debugging and security monitoring
- Graceful handling of GitHub API failures

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

For support and questions:
- Create an issue on GitHub
- Contact the development team
- Check the documentation for common solutions

## Changelog

### v2.0.0 - GitHub Integration
- Added comprehensive GitHub repository integration
- Implemented branch selection and repository validation
- Added security measures and rate limiting
- Enhanced error handling and user feedback
- Added comprehensive test coverage

### v1.0.0 - Initial Release
- AI-powered test generation
- Multiple AI provider support
- File upload functionality
- User authentication and management
