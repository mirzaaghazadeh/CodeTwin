<?php
require_once 'config.php';
require_once 'GitLabAnalyzer.php';

// Test configuration
echo "=== GitLab Merge Request Bot - Test Script ===\n\n";

// Check configuration
echo "1. Checking Configuration:\n";
echo "   GitLab URL: " . GITLAB_URL . "\n";
echo "   GitLab Token: " . (GITLAB_ACCESS_TOKEN ? "Set (" . substr(GITLAB_ACCESS_TOKEN, 0, 8) . "...)" : "NOT SET") . "\n";
echo "   OpenAI Key: " . (OPENAI_API_KEY ? "Set (" . substr(OPENAI_API_KEY, 0, 8) . "...)" : "NOT SET") . "\n";
echo "   OpenAI Model: " . OPENAI_MODEL . "\n";
echo "   Bot Name: CodeTwin\n\n";

// Test GitLab API connection
echo "2. Testing GitLab API Connection:\n";
try {
    $analyzer = new GitLabAnalyzer();
    
    // Test with a simple API call to get user info
    $url = rtrim(GITLAB_URL, '/') . '/api/v4/user';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . GITLAB_ACCESS_TOKEN,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $userInfo = json_decode($response, true);
        echo "   ✓ GitLab API connection successful!\n";
        echo "   User: " . $userInfo['name'] . " (@" . $userInfo['username'] . ")\n";
    } else {
        echo "   ✗ GitLab API connection failed. HTTP Code: $httpCode\n";
        echo "   Response: $response\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error testing GitLab API: " . $e->getMessage() . "\n";
}

echo "\n3. Testing OpenAI API Connection:\n";
try {
    $data = [
        'model' => OPENAI_MODEL,
        'messages' => [
            [
                'role' => 'user',
                'content' => 'Hello, this is a test message. Please respond with "Connection successful!"'
            ]
        ],
        'max_tokens' => 50
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . OPENAI_API_KEY,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        echo "   ✓ OpenAI API connection successful!\n";
        echo "   Response: " . $result['choices'][0]['message']['content'] . "\n";
    } else {
        echo "   ✗ OpenAI API connection failed. HTTP Code: $httpCode\n";
        echo "   Response: $response\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error testing OpenAI API: " . $e->getMessage() . "\n";
}

echo "\n4. Manual Test (Optional):\n";
echo "   To test with a specific merge request, run:\n";
echo "   php test.php [project_id] [merge_request_iid]\n";

// Manual test with specific MR
if ($argc >= 3) {
    $projectId = $argv[1];
    $mergeRequestIid = $argv[2];
    
    echo "\n5. Testing with Merge Request:\n";
    echo "   Project ID: $projectId\n";
    echo "   Merge Request IID: $mergeRequestIid\n\n";
    
    try {
        $analyzer = new GitLabAnalyzer();
        $result = $analyzer->analyzeMergeRequest($projectId, $mergeRequestIid);
        
        if ($result['success']) {
            echo "   ✓ Analysis completed successfully!\n";
            echo "   Analysis preview:\n";
            echo "   " . substr($result['analysis'], 0, 200) . "...\n";
        } else {
            echo "   ✗ Analysis failed: " . $result['error'] . "\n";
        }
    } catch (Exception $e) {
        echo "   ✗ Error during analysis: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Test Complete ===\n";
?> 