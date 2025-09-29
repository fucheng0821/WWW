<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

// 获取导出参数
$format = $_GET['format'] ?? 'csv';
$status = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// 构建查询条件
$where_conditions = ['1=1'];
$params = [];

if (!empty($status)) {
    $where_conditions[] = "status = ?";
    $params[] = $status;
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(created_at) <= ?";
    $params[] = $date_to;
}

$where_clause = implode(' AND ', $where_conditions);

try {
    // 获取询价数据
    $sql = "SELECT 
                id, name, company, phone, email, service_type, budget, 
                project_description, requirements, timeline, source,
                status, priority, notes, response_content, response_at,
                created_at, updated_at
            FROM inquiries 
            WHERE $where_clause
            ORDER BY created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $inquiries = $stmt->fetchAll();
    
    if (empty($inquiries)) {
        header('Location: index.php?error=no_data_to_export');
        exit();
    }
    
    // 状态和优先级映射
    $status_map = [
        'pending' => '待处理',
        'processing' => '处理中',
        'completed' => '已完成',
        'cancelled' => '已取消'
    ];
    
    $priority_map = [
        'low' => '低',
        'normal' => '普通',
        'high' => '高',
        'urgent' => '紧急'
    ];
    
    if ($format === 'csv') {
        // CSV导出
        $filename = 'inquiries_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        
        // 输出BOM以支持中文
        echo "\xEF\xBB\xBF";
        
        // CSV头部
        $headers = [
            'ID', '姓名', '公司', '电话', '邮箱', '服务类型', '预算', 
            '项目描述', '具体要求', '期望时间', '来源',
            '状态', '优先级', '内部备注', '回复内容', '回复时间',
            '创建时间', '更新时间'
        ];
        
        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);
        
        // 输出数据
        foreach ($inquiries as $inquiry) {
            $row = [
                $inquiry['id'],
                $inquiry['name'],
                $inquiry['company'] ?? '',
                $inquiry['phone'],
                $inquiry['email'] ?? '',
                $inquiry['service_type'],
                $inquiry['budget'] ?? '',
                $inquiry['project_description'],
                $inquiry['requirements'] ?? '',
                $inquiry['timeline'] ?? '',
                $inquiry['source'],
                $status_map[$inquiry['status']] ?? $inquiry['status'],
                $priority_map[$inquiry['priority']] ?? $inquiry['priority'],
                $inquiry['notes'] ?? '',
                $inquiry['response_content'] ?? '',
                $inquiry['response_at'] ?? '',
                $inquiry['created_at'],
                $inquiry['updated_at']
            ];
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit();
        
    } else {
        // Excel导出（简单的HTML表格格式）
        $filename = 'inquiries_export_' . date('Y-m-d_H-i-s') . '.xls';
        
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        
        echo "\xEF\xBB\xBF"; // BOM
        
        echo '<table border="1">';
        echo '<tr>';
        echo '<th>ID</th><th>姓名</th><th>公司</th><th>电话</th><th>邮箱</th>';
        echo '<th>服务类型</th><th>预算</th><th>项目描述</th><th>具体要求</th>';
        echo '<th>期望时间</th><th>来源</th><th>状态</th><th>优先级</th>';
        echo '<th>内部备注</th><th>回复内容</th><th>回复时间</th>';
        echo '<th>创建时间</th><th>更新时间</th>';
        echo '</tr>';
        
        foreach ($inquiries as $inquiry) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($inquiry['id']) . '</td>';
            echo '<td>' . htmlspecialchars($inquiry['name']) . '</td>';
            echo '<td>' . htmlspecialchars($inquiry['company'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($inquiry['phone']) . '</td>';
            echo '<td>' . htmlspecialchars($inquiry['email'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($inquiry['service_type']) . '</td>';
            echo '<td>' . htmlspecialchars($inquiry['budget'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($inquiry['project_description']) . '</td>';
            echo '<td>' . htmlspecialchars($inquiry['requirements'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($inquiry['timeline'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($inquiry['source']) . '</td>';
            echo '<td>' . htmlspecialchars($status_map[$inquiry['status']] ?? $inquiry['status']) . '</td>';
            echo '<td>' . htmlspecialchars($priority_map[$inquiry['priority']] ?? $inquiry['priority']) . '</td>';
            echo '<td>' . htmlspecialchars($inquiry['notes'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($inquiry['response_content'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($inquiry['response_at'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($inquiry['created_at']) . '</td>';
            echo '<td>' . htmlspecialchars($inquiry['updated_at']) . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        exit();
    }
    
} catch (Exception $e) {
    header('Location: index.php?error=' . urlencode('导出失败：' . $e->getMessage()));
    exit();
}
?>