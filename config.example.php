<?php
// Example configuration file for GitLab MR Bot
// Copy this file to config.php and fill in your actual values

// GitLab Configuration
define('GITLAB_URL', 'https://your-gitlab-server.com'); // Your GitLab instance URL
define('GITLAB_ACCESS_TOKEN', 'glpat-xxxxxxxxxxxxxxxxxxxx'); // GitLab Personal Access Token with 'api' scope

// OpenAI Configuration
define('OPENAI_API_KEY', 'sk-xxxxxxxxxxxxxxxxxxxx'); // Your OpenAI API key
define('OPENAI_MODEL', 'gpt-4'); // Model to use: 'gpt-4' or 'gpt-3.5-turbo'

// Webhook Security (recommended)
define('WEBHOOK_SECRET_TOKEN', 'your-secure-random-token-here'); // Generate a secure random token

// Analysis Settings
define('MAX_DIFF_SIZE', 10000); // Maximum characters in diff to analyze (adjust based on your needs)
define('MAX_FILES_TO_ANALYZE', 20); // Maximum number of files to analyze in one MR

// Bot Settings
define('BOT_NAME', 'Code Review Bot'); // Name that appears in comments
define('SKIP_COMMENT_IF_EXISTS', true); // Skip analysis if bot already commented on this MR

/*
SETUP INSTRUCTIONS:

1. GitLab Personal Access Token:
   - Go to GitLab → User Settings → Access Tokens
   - Create a new token with 'api' scope
   - Copy the token and paste it above

2. OpenAI API Key:
   - Visit https://platform.openai.com/api-keys
   - Create a new API key
   - Copy the key and paste it above

3. Webhook Secret Token:
   - Generate a secure random string (use a password generator)
   - This same token must be set in your GitLab webhook settings

4. Test your configuration:
   - Run: php test.php
   - Fix any connection issues before setting up the webhook

5. Set up GitLab webhook:
   - Go to your GitLab project → Settings → Webhooks
   - URL: https://your-domain.com/path/to/webhook.php
   - Secret Token: Same as WEBHOOK_SECRET_TOKEN above
   - Trigger: Check "Merge request events"
   - Add webhook and test it

SECURITY NOTES:
- Never commit this file to version control
- Use HTTPS for your webhook endpoint
- Consider IP whitelisting for additional security
- Keep your API keys secure and rotate them regularly
*/
?> 