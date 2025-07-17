# 🤖 CodeTwin - AI Code Review Bot

> *"Because every developer deserves a second pair of eyes"*

Built with ❤️  - A story of innovation, challenges, and smart solutions.

## 🌟 Our Story

At **Our Development Team**, we faced a common but frustrating challenge: 

**The Problem** 💔
- Code reviews were taking forever
- Team members were too busy to review properly
- Important bugs and security issues were slipping through
- Junior developers weren't getting the feedback they needed
- Different reviewers had different standards

**The "Aha!" Moment** 💡
During a late-night debugging session, our team realized: *"What if we had an AI that could give consistent, thorough code reviews 24/7?"*

**The Challenge** 🎯
- Build something that actually understands code context
- Make it friendly and educational, not just critical
- Create separate, focused reviews (not one giant comment)
- Ensure it asks the right questions when needed
- Keep it simple for our team to use

## 🚀 What We Built

**CodeTwin** - Our AI-powered code review assistant that gives you:

### 📋 **4 Types of Reviews**
1. **Business Logic Review** - What changed from a business perspective
2. **Technical Code Review** - Deep technical analysis like a senior developer
3. **Security Check** - Focused security concerns and vulnerabilities  
4. **Quick Summary** - TL;DR of the changes

### 🎯 **Smart Question System**
- Only asks questions when there are real issues
- Directly mentions the author for important clarifications
- No spam - only when needed!

## 🛠️ The Technical Journey

### **Challenges We Overcame**

**Challenge #1: Information Overload** 
- *Problem*: One massive comment was overwhelming
- *Solution*: Split into focused, digestible sections

**Challenge #2: Generic Reviews**
- *Problem*: AI was giving generic, unhelpful feedback
- *Solution*: Crafted specific prompts for business logic, technical depth, and security

**Challenge #3: Spam Questions**
- *Problem*: Bot was asking questions about everything
- *Solution*: Smart logic to only ask when there are genuine concerns

**Challenge #4: Poor User Experience**
- *Problem*: Formal, robotic language
- *Solution*: Friendly, conversational tone that helps teams learn

## 🎯 What Makes CodeTwin Special

- **Separate Comments**: Each review type gets its own comment
- **Context-Aware**: Understands business logic, not just code syntax
- **Educational**: Helps junior developers learn best practices
- **Security-Focused**: Dedicated security analysis
- **Team-Friendly**: Mentions authors only when needed

## 🚀 Quick Start

### **What You Need**
- PHP 7.4+ with cURL
- Your GitLab server (self-hosted or GitLab.com)
- OpenAI API key
- 5 minutes of setup time

### **Step 1: Get the Code**
```bash
git clone https://github.com/mirzaaghazadeh/codetwin-bot.git
cd codetwin-bot
```

### **Step 2: Configure**
Copy `config.example.php` to `config.php` and fill in:
```php
// Your GitLab server
define('GITLAB_URL', 'https://yourgitlab.com');
define('GITLAB_ACCESS_TOKEN', 'your-token-here');

// OpenAI settings
define('OPENAI_API_KEY', 'your-openai-key');
define('OPENAI_MODEL', 'gpt-4.1-nano');

// Security
define('WEBHOOK_SECRET_TOKEN', 'your-secret-token');
```

### **Step 3: Test It**
```bash
php test.php
```

### **Step 4: Set Up Webhook**
In GitLab: Project → Settings → Webhooks
- URL: `https://your-domain.com/webhook.php`
- Secret: Same as your config
- Trigger: "Merge request events"

## 🎉 See It In Action

Once set up, **CodeTwin** automatically springs into action:

### **What Happens**
1. Developer creates/updates merge request
2. GitLab sends webhook to CodeTwin
3. CodeTwin analyzes the changes with AI
4. Multiple focused comments appear on the MR

### **Example: What Your Team Will See**

**Comment 1: Business Logic Review**
```
# 📋 Business Logic Review

This update adds user authentication to the dashboard. 
Users can now log in with email/password and stay logged in for 24 hours.
The system also tracks login attempts for security.

---
*Automated review by [CodeTwin](https://github.com/mirzaaghazadeh/CodeTwin) 🤖*
```

**Comment 2: Technical Code Review**
```
# 👥 Technical Code Review

## Code Structure
- New AuthService class with clean separation
- Database migrations properly structured
- Good use of middleware pattern

## Code Quality
- Consistent naming conventions
- Proper error handling
- Well-documented methods

## Potential Issues
- Consider adding input validation for email format
- Password hashing looks good
- Database queries are efficient

## Testing
- Unit tests needed for AuthService
- Integration tests for login flow recommended

---
*Automated review by [CodeTwin](https://github.com/mirzaaghazadeh/CodeTwin) 🤖*
```

**Comment 3: Security Check**
```
# 🔒 Security Check

✅ Password hashing using bcrypt - good!
✅ JWT tokens properly signed
⚠️ Consider adding rate limiting to login endpoint
⚠️ Add CSRF protection for login form
✅ Session management looks secure

---
*Automated review by [CodeTwin](https://github.com/mirzaaghazadeh/CodeTwin) 🤖*
```

**Comment 4: Questions (only when needed)**
```
# ❓ Questions for @developer

Why did you choose 24-hour token expiration? 
Should we add two-factor authentication support?
Is the password complexity requirement sufficient?

---
*Please clarify these points - [CodeTwin](https://github.com/mirzaaghazadeh/CodeTwin) 🤖*
```

## ⚙️ Fine-Tuning CodeTwin

### **Make It Yours**
```php
// Bot behavior
define('SKIP_COMMENT_IF_EXISTS', true);      // Avoid spam

// Analysis limits (adjust for your team)
define('MAX_DIFF_SIZE', 10000);             // Characters to analyze
define('MAX_FILES_TO_ANALYZE', 20);         // Files per MR
```

### **Choose Your AI Model**
```php
// Cost vs Quality trade-off
define('OPENAI_MODEL', 'gpt-4.1-nano');     // Fast and cheap
// define('OPENAI_MODEL', 'gpt-4');        // Slower but smarter
```

## 🧪 Testing Your Setup

### **Quick Health Check**
```bash
php test.php
```
This verifies your GitLab and OpenAI connections.

### **Test with Real Data**
```bash
php test.php 123 45
```
Replace `123` with your project ID and `45` with a merge request number.

## 🔧 When Things Go Wrong

### **The "It's Not Working" Checklist**

**🔴 GitLab Connection Issues**
- Is your GitLab URL correct? (Check for typos)
- Does your token have `api` scope?
- Can your server reach GitLab?

**🔴 OpenAI Problems**
- Valid API key? (Check OpenAI dashboard)
- Got credits? (AI isn't free!)
- Model name correct? (gpt-4.1-nano)

**🔴 Webhook Silent Treatment**
- URL accessible from GitLab?
- Secret token matches?
- Check server logs for errors

**🔴 Bot Being Lazy**
- Already commented? (Check `SKIP_COMMENT_IF_EXISTS`)
- Merge request action supported? (open, update, reopen)
- Error logs showing anything?

### **Debug Mode**
When you're pulling your hair out:
```php
define('DEBUG_MODE', true);
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 🔒 Security (We Take This Seriously)

At **TwinCoders**, we learned security the hard way. Here's what we recommend:

- **HTTPS only** for webhook endpoints
- **Secret tokens** to prevent unauthorized requests  
- **File permissions** - 644 for PHP files
- **API keys** - Never commit them! Use `.gitignore`
- **IP whitelisting** - Lock down who can trigger your bot

## 📁 What's In The Box

```
codetwin-bot/
├── webhook.php           # Main webhook handler
├── config.example.php    # Configuration template
├── GitLabAnalyzer.php    # The brain of the operation
├── test.php             # Your debugging buddy
├── README.md            # This story
├── .htaccess            # Security settings
└── .gitignore           # Keep secrets secret
```

## 💰 Cost Reality Check

**OpenAI API isn't free, but it's cheap:**
- **gpt-4.1-nano**: ~$0.40 per 1M tokens (our choice)

**Real numbers:** Most merge requests cost $0.01-$0.05 to analyze. For a team of 5 developers, expect ~$10-20/month.

## 🚀 Performance & Limits

**API Limits We Work With:**
- **GitLab**: 600 requests/minute (plenty for most teams)
- **OpenAI**: Depends on your plan (usually not an issue)

**Our Smart Defaults:**
- Max 20 files per merge request
- Max 10,000 characters per diff
- Smart truncation when needed

## 🤝 Join The TwinCoders Community

Found a bug? Want to add a feature? We'd love your help!

1. **Fork** the repository
2. **Create** a feature branch
3. **Test** your changes thoroughly
4. **Submit** a pull request with a clear description

### **Current Contributors**
- The **TwinCoders** team (that's us!)
- Future contributors (that could be you!)

## 📞 Get Help

**Stuck? We've been there.**

1. **Check** the troubleshooting section above
2. **Run** `php test.php` to diagnose issues
3. **Review** your server logs
4. **Create** an issue on GitHub with:
   - Error messages
   - Your configuration (without API keys!)
   - Steps to reproduce

## 📜 License

MIT License - Use it, modify it, share it. Just don't blame us if your code reviews become too good! 😄

## 🎉 Final Words

**CodeTwin** was born from frustration and built with love. We hope it helps your team write better code, learn faster, and ship with confidence.

Remember: **AI is your assistant, not your replacement.** Use CodeTwin's suggestions as a starting point, but always apply your own judgment.

Happy coding! 🚀

---

*Built with ❤️ by **TwinCoders** - Making code reviews less painful, one merge request at a time.* 