<?php
header('Content-Type: application/json');

// Configuration
require_once 'config.php';
require_once 'GitLabAnalyzer.php';

// Verify webhook token if configured
if (defined('WEBHOOK_SECRET_TOKEN') && WEBHOOK_SECRET_TOKEN) {
    $received_token = $_SERVER['HTTP_X_GITLAB_TOKEN'] ?? '';
    if ($received_token !== WEBHOOK_SECRET_TOKEN) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

// Get the raw POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Log the webhook data for debugging
error_log("GitLab Webhook Data: " . json_encode($data, JSON_PRETTY_PRINT));

// Check if this is a merge request event
if (!isset($data['object_kind']) || $data['object_kind'] !== 'merge_request') {
    echo json_encode(['message' => 'Not a merge request event']);
    exit;
}

// Check if this is an 'opened' or 'updated' merge request
$action = $data['object_attributes']['action'] ?? '';
if (!in_array($action, ['open', 'update', 'reopen'])) {
    echo json_encode(['message' => 'Merge request action not relevant: ' . $action]);
    exit;
}

try {
    $analyzer = new GitLabAnalyzer();
    
    // Extract merge request information
    $mergeRequest = $data['object_attributes'];
    $projectId = $data['project']['id'];
    $mergeRequestIid = $mergeRequest['iid'];
    
    // Analyze the merge request
    $result = $analyzer->analyzeMergeRequest($projectId, $mergeRequestIid);
    
    if ($result['success']) {
        echo json_encode(['message' => 'Analysis completed successfully']);
    } else {
        echo json_encode(['error' => 'Analysis failed: ' . $result['error']]);
    }
    
} catch (Exception $e) {
    error_log("Error processing webhook: " . $e->getMessage());
    echo json_encode(['error' => 'Internal server error']);
}
?> 