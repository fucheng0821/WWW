<?php
/**
 * 内容管理类
 */

// 加载图片处理函数
spl_autoload_register(function() {
    require_once dirname(__FILE__) . '/functions_image.php';
});

class ContentManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * 获取内容列表
     */
    public function getContents($filters = []) {
        try {
            $sql = "SELECT c.*, cat.name as category_name, cat.slug as category_slug 
                    FROM contents c 
                    LEFT JOIN categories cat ON c.category_id = cat.id 
                    WHERE 1=1";
            $params = [];
            
            // 栏目筛选
            if (isset($filters['category_id']) && $filters['category_id']) {
                $sql .= " AND c.category_id = ?";
                $params[] = $filters['category_id'];
            }
            
            // 发布状态筛选
            if (isset($filters['is_published'])) {
                $sql .= " AND c.is_published = ?";
                $params[] = $filters['is_published'];
            }
            
            // 推荐状态筛选
            if (isset($filters['is_featured'])) {
                $sql .= " AND c.is_featured = ?";
                $params[] = $filters['is_featured'];
            }
            
            // 关键词搜索
            if (isset($filters['keyword']) && $filters['keyword']) {
                $sql .= " AND (c.title LIKE ? OR c.summary LIKE ? OR c.content LIKE ?)";
                $keyword = '%' . $filters['keyword'] . '%';
                $params[] = $keyword;
                $params[] = $keyword;
                $params[] = $keyword;
            }
            
            // 排序
            $sql .= " ORDER BY c.sort_order DESC, c.created_at DESC";
            
            // 分页
            if (isset($filters['limit'])) {
                $sql .= " LIMIT ?";
                $params[] = $filters['limit'];
                
                if (isset($filters['offset'])) {
                    $sql .= " OFFSET ?";
                    $params[] = $filters['offset'];
                }
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            return [];
        }
    }
    
    /**
     * 获取内容总数
     */
    public function getContentCount($filters = []) {
        try {
            $sql = "SELECT COUNT(*) as total FROM contents c WHERE 1=1";
            $params = [];
            
            if (isset($filters['category_id']) && $filters['category_id']) {
                $sql .= " AND c.category_id = ?";
                $params[] = $filters['category_id'];
            }
            
            if (isset($filters['is_published'])) {
                $sql .= " AND c.is_published = ?";
                $params[] = $filters['is_published'];
            }
            
            if (isset($filters['keyword']) && $filters['keyword']) {
                $sql .= " AND (c.title LIKE ? OR c.summary LIKE ? OR c.content LIKE ?)";
                $keyword = '%' . $filters['keyword'] . '%';
                $params[] = $keyword;
                $params[] = $keyword;
                $params[] = $keyword;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            return $result['total'];
        } catch(PDOException $e) {
            return 0;
        }
    }
    
    /**
     * 添加内容
     */
    public function addContent($data) {
        try {
            $sql = "INSERT INTO contents (category_id, title, slug, summary, content, thumbnail, images, videos, tags, sort_order, is_featured, is_published, seo_title, seo_keywords, seo_description, published_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $published_at = ($data['is_published'] ?? 1) ? date('Y-m-d H:i:s') : null;
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['category_id'],
                $data['title'],
                $data['slug'] ?: generate_slug($data['title']),
                $data['summary'] ?? '',
                $data['content'] ?? '',
                $data['thumbnail'] ?? '',
                $data['images'] ? json_encode($data['images']) : null,
                $data['videos'] ? json_encode($data['videos']) : null,
                $data['tags'] ?? '',
                $data['sort_order'] ?? 0,
                $data['is_featured'] ?? 0,
                $data['is_published'] ?? 1,
                $data['seo_title'] ?? '',
                $data['seo_keywords'] ?? '',
                $data['seo_description'] ?? '',
                $published_at
            ]);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    /**
     * 更新内容
     */
    public function updateContent($id, $data) {
        try {
            $sql = "UPDATE contents SET 
                    category_id = ?, title = ?, slug = ?, summary = ?, content = ?, 
                    thumbnail = ?, images = ?, videos = ?, tags = ?, sort_order = ?, 
                    is_featured = ?, is_published = ?, seo_title = ?, seo_keywords = ?, 
                    seo_description = ?, updated_at = CURRENT_TIMESTAMP";
            
            $params = [
                $data['category_id'],
                $data['title'],
                $data['slug'] ?: generate_slug($data['title']),
                $data['summary'] ?? '',
                $data['content'] ?? '',
                $data['thumbnail'] ?? '',
                $data['images'] ? json_encode($data['images']) : null,
                $data['videos'] ? json_encode($data['videos']) : null,
                $data['tags'] ?? '',
                $data['sort_order'] ?? 0,
                $data['is_featured'] ?? 0,
                $data['is_published'] ?? 1,
                $data['seo_title'] ?? '',
                $data['seo_keywords'] ?? '',
                $data['seo_description'] ?? ''
            ];
            
            // 如果发布状态改变，更新发布时间
            if (isset($data['is_published']) && $data['is_published']) {
                $sql .= ", published_at = CURRENT_TIMESTAMP";
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    /**
     * 删除内容
     */
    public function deleteContent($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM contents WHERE id = ?");
            return $stmt->execute([$id]);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    /**
     * 获取内容详情
     */
    public function getContentById($id) {
        try {
            $stmt = $this->db->prepare("SELECT c.*, cat.name as category_name, cat.slug as category_slug 
                                        FROM contents c 
                                        LEFT JOIN categories cat ON c.category_id = cat.id 
                                        WHERE c.id = ?");
            $stmt->execute([$id]);
            $content = $stmt->fetch();
            
            if ($content) {
                // 解析JSON字段
                $content['images'] = $content['images'] ? json_decode($content['images'], true) : [];
                $content['videos'] = $content['videos'] ? json_decode($content['videos'], true) : [];
                
                // 处理内容中的图片URL，替换不存在的图片
                if (function_exists('process_content_images')) {
                    // 使用fast_process_content_images以提高性能
                    $content['content'] = fast_process_content_images($content['content']);
                }
            }
            
            return $content;
        } catch(PDOException $e) {
            return false;
        }
    }
    
    /**
     * 批量操作
     */
    public function batchOperation($ids, $operation, $data = []) {
        try {
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            
            switch ($operation) {
                case 'delete':
                    $sql = "DELETE FROM contents WHERE id IN ($placeholders)";
                    $stmt = $this->db->prepare($sql);
                    return $stmt->execute($ids);
                    
                case 'publish':
                    $sql = "UPDATE contents SET is_published = 1, published_at = CURRENT_TIMESTAMP WHERE id IN ($placeholders)";
                    $stmt = $this->db->prepare($sql);
                    return $stmt->execute($ids);
                    
                case 'unpublish':
                    $sql = "UPDATE contents SET is_published = 0 WHERE id IN ($placeholders)";
                    $stmt = $this->db->prepare($sql);
                    return $stmt->execute($ids);
                    
                case 'feature':
                    $sql = "UPDATE contents SET is_featured = 1 WHERE id IN ($placeholders)";
                    $stmt = $this->db->prepare($sql);
                    return $stmt->execute($ids);
                    
                case 'unfeature':
                    $sql = "UPDATE contents SET is_featured = 0 WHERE id IN ($placeholders)";
                    $stmt = $this->db->prepare($sql);
                    return $stmt->execute($ids);
                    
                case 'move':
                    if (isset($data['category_id'])) {
                        $sql = "UPDATE contents SET category_id = ? WHERE id IN ($placeholders)";
                        $params = array_merge([$data['category_id']], $ids);
                        $stmt = $this->db->prepare($sql);
                        return $stmt->execute($params);
                    }
                    break;
            }
            
            return false;
        } catch(PDOException $e) {
            return false;
        }
    }
}
?>