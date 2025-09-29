<?php
/**
 * 栏目管理类
 */

class CategoryManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * 获取所有栏目（树形结构）
     */
    public function getAllCategories() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM categories ORDER BY parent_id ASC, sort_order ASC");
            $stmt->execute();
            $categories = $stmt->fetchAll();
            
            return $this->buildCategoryTree($categories);
        } catch(PDOException $e) {
            return [];
        }
    }
    
    /**
     * 构建栏目树
     */
    private function buildCategoryTree($categories, $parent_id = 0) {
        $tree = [];
        foreach ($categories as $category) {
            if ($category['parent_id'] == $parent_id) {
                $category['children'] = $this->buildCategoryTree($categories, $category['id']);
                $tree[] = $category;
            }
        }
        return $tree;
    }
    
    /**
     * 添加栏目
     */
    public function addCategory($data) {
        try {
            $sql = "INSERT INTO categories (parent_id, name, slug, description, template_type, template_file, sort_order, is_enabled, seo_title, seo_keywords, seo_description) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['parent_id'] ?? 0,
                $data['name'],
                $data['slug'],
                $data['description'] ?? '',
                $data['template_type'] ?? 'list',
                $data['template_file'] ?? '',
                $data['sort_order'] ?? 0,
                $data['is_enabled'] ?? 1,
                $data['seo_title'] ?? '',
                $data['seo_keywords'] ?? '',
                $data['seo_description'] ?? ''
            ]);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    /**
     * 更新栏目
     */
    public function updateCategory($id, $data) {
        try {
            $sql = "UPDATE categories SET 
                    parent_id = ?, name = ?, slug = ?, description = ?, 
                    template_type = ?, template_file = ?, sort_order = ?, is_enabled = ?, 
                    seo_title = ?, seo_keywords = ?, seo_description = ?, 
                    updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['parent_id'] ?? 0,
                $data['name'],
                $data['slug'],
                $data['description'] ?? '',
                $data['template_type'] ?? 'list',
                $data['template_file'] ?? '',
                $data['sort_order'] ?? 0,
                $data['is_enabled'] ?? 1,
                $data['seo_title'] ?? '',
                $data['seo_keywords'] ?? '',
                $data['seo_description'] ?? '',
                $id
            ]);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    /**
     * 删除栏目
     */
    public function deleteCategory($id) {
        try {
            // 检查是否有子栏目
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM categories WHERE parent_id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                return false; // 有子栏目不能删除
            }
            
            // 检查是否有内容
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM contents WHERE category_id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                return false; // 有内容不能删除
            }
            
            // 删除栏目
            $stmt = $this->db->prepare("DELETE FROM categories WHERE id = ?");
            return $stmt->execute([$id]);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    /**
     * 获取栏目详情
     */
    public function getCategoryById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch(PDOException $e) {
            return false;
        }
    }
}
?>