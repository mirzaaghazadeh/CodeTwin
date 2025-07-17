<?php
class GitLabAnalyzer {
    private $gitlabUrl;
    private $gitlabToken;
    private $openaiKey;
    private $openaiModel;
    
    public function __construct() {
        $this->gitlabUrl = rtrim(GITLAB_URL, '/');
        $this->gitlabToken = GITLAB_ACCESS_TOKEN;
        $this->openaiKey = OPENAI_API_KEY;
        $this->openaiModel = OPENAI_MODEL;
    }
    
    /**
     * Analyze a merge request and post a comment
     */
    public function analyzeMergeRequest($projectId, $mergeRequestIid) {
        try {
            // Check if bot already commented (if enabled)
            if (SKIP_COMMENT_IF_EXISTS && $this->hasExistingBotComment($projectId, $mergeRequestIid)) {
                return ['success' => false, 'error' => 'Bot already commented on this MR'];
            }
            
            // Get merge request details
            $mergeRequest = $this->getMergeRequest($projectId, $mergeRequestIid);
            if (!$mergeRequest) {
                return ['success' => false, 'error' => 'Failed to fetch merge request'];
            }
            
            // Get merge request changes
            $changes = $this->getMergeRequestChanges($projectId, $mergeRequestIid);
            if (!$changes) {
                return ['success' => false, 'error' => 'Failed to fetch merge request changes'];
            }
            
            // Analyze with OpenAI
            $analysis = $this->analyzeWithOpenAI($mergeRequest, $changes);
            if (!$analysis) {
                return ['success' => false, 'error' => 'Failed to analyze with OpenAI'];
            }
            
            // Post multiple comments to merge request
            $commentResults = $this->postMultipleComments($projectId, $mergeRequestIid, $analysis, $mergeRequest['author']['username']);
            
            return ['success' => $commentResults['success'], 'analysis' => $analysis];
            
        } catch (Exception $e) {
            error_log("Error in analyzeMergeRequest: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Make API request to GitLab
     */
    private function makeGitLabRequest($endpoint, $method = 'GET', $data = null) {
        $url = $this->gitlabUrl . '/api/v4' . $endpoint;
        
        $headers = [
            'Authorization: Bearer ' . $this->gitlabToken,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For self-signed certificates
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        }
        
        error_log("GitLab API Error: HTTP $httpCode - $response");
        return false;
    }
    
    /**
     * Get merge request details
     */
    private function getMergeRequest($projectId, $mergeRequestIid) {
        return $this->makeGitLabRequest("/projects/$projectId/merge_requests/$mergeRequestIid");
    }
    
    /**
     * Get merge request changes
     */
    private function getMergeRequestChanges($projectId, $mergeRequestIid) {
        return $this->makeGitLabRequest("/projects/$projectId/merge_requests/$mergeRequestIid/changes");
    }
    
    /**
     * Check if bot already commented on this MR
     */
    private function hasExistingBotComment($projectId, $mergeRequestIid) {
        $notes = $this->makeGitLabRequest("/projects/$projectId/merge_requests/$mergeRequestIid/notes");
        if (!$notes) return false;
        
        foreach ($notes as $note) {
            if (stripos($note['body'], 'CodeTwin') !== false) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Analyze changes with OpenAI
     */
    private function analyzeWithOpenAI($mergeRequest, $changes) {
        $prompt = $this->buildAnalysisPrompt($mergeRequest, $changes);
        
        $data = [
            'model' => $this->openaiModel,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a friendly code review assistant. Analyze merge request changes and provide feedback in separate sections: business logic review, comprehensive technical code review, security check, quick summary, and questions if needed. Use simple, easy English. Be helpful and friendly, not overly formal. Make the technical review thorough like a real senior developer would do.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 2500,
            'temperature' => 0.2
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->openaiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            return $result['choices'][0]['message']['content'] ?? null;
        }
        
        error_log("OpenAI API Error: HTTP $httpCode - $response");
        return false;
    }
    
    /**
     * Build analysis prompt for OpenAI
     */
    private function buildAnalysisPrompt($mergeRequest, $changes) {
        $prompt = "Please review this merge request and provide 4 different types of reports:\n\n";
        $prompt .= "**Title:** " . $mergeRequest['title'] . "\n";
        $prompt .= "**Description:** " . ($mergeRequest['description'] ?? 'No description') . "\n";
        $prompt .= "**Author:** " . $mergeRequest['author']['name'] . "\n";
        $prompt .= "**Source Branch:** " . $mergeRequest['source_branch'] . "\n";
        $prompt .= "**Target Branch:** " . $mergeRequest['target_branch'] . "\n\n";
        
        $prompt .= "**Changes:**\n";
        
        $fileCount = 0;
        $totalDiffSize = 0;
        
        foreach ($changes['changes'] as $change) {
            if ($fileCount >= MAX_FILES_TO_ANALYZE) break;
            
            $filename = $change['new_path'] ?? $change['old_path'];
            $diff = $change['diff'] ?? '';
            
            if (strlen($diff) > MAX_DIFF_SIZE) {
                $diff = substr($diff, 0, MAX_DIFF_SIZE) . "\n... (truncated)";
            }
            
            $totalDiffSize += strlen($diff);
            if ($totalDiffSize > MAX_DIFF_SIZE * 2) break;
            
            $prompt .= "\n**File:** $filename\n";
            $prompt .= "```diff\n$diff\n```\n";
            
            $fileCount++;
        }
        
        if ($fileCount >= MAX_FILES_TO_ANALYZE) {
            $prompt .= "\n... (some files omitted due to size limits)\n";
        }
        
        $prompt .= "\n\nPlease provide your response in this EXACT format with these sections separated by '---SECTION---':\n\n";
        
        $prompt .= "BUSINESS_LOGIC:\n";
        $prompt .= "What happened from a business perspective? What features or functionality changed? Keep it simple and explain like you're talking to a non-technical person.\n\n";
        
        $prompt .= "---SECTION---\n\n";
        
        $prompt .= "TECHNICAL_REVIEW:\n";
        $prompt .= "Complete technical code review like a real senior developer would do. Include:\n";
        $prompt .= "- Code structure and architecture changes\n";
        $prompt .= "- Code quality, patterns, and best practices\n";
        $prompt .= "- Potential bugs or issues\n";
        $prompt .= "- Performance implications\n";
        $prompt .= "- Testing considerations\n";
        $prompt .= "- Maintainability and readability\n";
        $prompt .= "- Specific file and line feedback where relevant\n";
        $prompt .= "Be thorough but keep it easy to understand.\n\n";
        
        $prompt .= "---SECTION---\n\n";
        
        $prompt .= "SECURITY_CHECK:\n";
        $prompt .= "Any security issues or concerns? Input validation, authentication, data exposure, etc. If no issues, just say 'No security concerns found'.\n\n";
        
        $prompt .= "---SECTION---\n\n";
        
        $prompt .= "QUICK_SUMMARY:\n";
        $prompt .= "Short summary of this merge request in 1-2 sentences. What's the main point?\n\n";
        
        $prompt .= "---SECTION---\n\n";
        
        $prompt .= "QUESTIONS:\n";
        $prompt .= "Only include questions if there are important problems or unclear parts that need clarification. If everything looks good, just write 'NONE'.\n\n";
        
        $prompt .= "Remember:\n";
        $prompt .= "- Use simple, easy English (not overly professional/formal)\n";
        $prompt .= "- Be helpful and friendly\n";
        $prompt .= "- Focus on the most important points\n";
        $prompt .= "- Make technical review comprehensive but readable\n";
        
        return $prompt;
    }
    
    /**
     * Post multiple comments to merge request
     */
    private function postMultipleComments($projectId, $mergeRequestIid, $analysis, $authorUsername) {
        $sections = $this->parseAnalysisIntoSections($analysis);
        $results = [];
        
        // 1. Business Logic Review
        if (!empty($sections['business_logic'])) {
            $comment = "# 📋 Business Logic Review\n\n";
            $comment .= $sections['business_logic'];
            $comment .= "\n\n---\n*Automated review by [CodeTwin](https://github.com/mirzaaghazadeh/CodeTwin) 🤖*";
            
            $results['business'] = $this->postSingleComment($projectId, $mergeRequestIid, $comment);
        }
        
        // 2. Technical Review
        if (!empty($sections['technical_review'])) {
            $comment = "# 👥 Technical Code Review\n\n";
            $comment .= $sections['technical_review'];
            $comment .= "\n\n---\n*Automated review by [CodeTwin](https://github.com/mirzaaghazadeh/CodeTwin) 🤖*";
            
            $results['technical'] = $this->postSingleComment($projectId, $mergeRequestIid, $comment);
        }
        
        // 3. Security Check
        if (!empty($sections['security_check'])) {
            $comment = "# 🔒 Security Check\n\n";
            $comment .= $sections['security_check'];
            $comment .= "\n\n---\n*Automated review by [CodeTwin](https://github.com/mirzaaghazadeh/CodeTwin) 🤖*";
            
            $results['security'] = $this->postSingleComment($projectId, $mergeRequestIid, $comment);
        }
        
        // 4. Quick Summary
        if (!empty($sections['quick_summary'])) {
            $comment = "# 💡 Quick Summary\n\n";
            $comment .= $sections['quick_summary'];
            $comment .= "\n\n---\n*Automated review by [CodeTwin](https://github.com/mirzaaghazadeh/CodeTwin) 🤖*";
            
            $results['summary'] = $this->postSingleComment($projectId, $mergeRequestIid, $comment);
        }
        
        // 5. Questions (separate comment, only if needed)
        if (!empty($sections['questions']) && trim($sections['questions']) !== 'NONE') {
            $comment = "# ❓ Questions for @" . $authorUsername . "\n\n";
            $comment .= $sections['questions'];
            $comment .= "\n\n---\n*Please clarify these points - [CodeTwin](https://github.com/mirzaaghazadeh/CodeTwin) 🤖*";
            
            $results['questions'] = $this->postSingleComment($projectId, $mergeRequestIid, $comment);
        }
        
        $success = !empty($results) && !in_array(false, $results);
        return ['success' => $success, 'results' => $results];
    }
    
    /**
     * Parse AI analysis into sections
     */
    private function parseAnalysisIntoSections($analysis) {
        $sections = [];
        $parts = explode('---SECTION---', $analysis);
        
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;
            
            if (strpos($part, 'BUSINESS_LOGIC:') === 0) {
                $sections['business_logic'] = trim(str_replace('BUSINESS_LOGIC:', '', $part));
            } elseif (strpos($part, 'TECHNICAL_REVIEW:') === 0) {
                $sections['technical_review'] = trim(str_replace('TECHNICAL_REVIEW:', '', $part));
            } elseif (strpos($part, 'SECURITY_CHECK:') === 0) {
                $sections['security_check'] = trim(str_replace('SECURITY_CHECK:', '', $part));
            } elseif (strpos($part, 'QUICK_SUMMARY:') === 0) {
                $sections['quick_summary'] = trim(str_replace('QUICK_SUMMARY:', '', $part));
            } elseif (strpos($part, 'QUESTIONS:') === 0) {
                $sections['questions'] = trim(str_replace('QUESTIONS:', '', $part));
            }
        }
        
        return $sections;
    }
    
    /**
     * Post a single comment to merge request
     */
    private function postSingleComment($projectId, $mergeRequestIid, $comment) {
        $data = ['body' => $comment];
        
        return $this->makeGitLabRequest(
            "/projects/$projectId/merge_requests/$mergeRequestIid/notes",
            'POST',
            $data
        );
    }
}
?> 