<?php
/**
 * CSS性能优化脚本
 * 用于合并、压缩和优化CSS文件
 */

class CSSOptimizer {
    private $config;
    private $outputDir = './optimized';
    
    public function __construct($configFile) {
        $this->config = json_decode(file_get_contents($configFile), true);
        $this->createOutputDir();
    }
    
    private function createOutputDir() {
        if (!file_exists($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }
    
    public function optimize() {
        echo "开始CSS性能优化...\n";
        
        // 1. 合并CSS文件
        $mergedCSS = $this->mergeCSSFiles();
        
        // 2. 移除注释
        $cleanCSS = $this->removeComments($mergedCSS);
        
        // 3. 压缩CSS
        $minifiedCSS = $this->minifyCSS($cleanCSS);
        
        // 4. 保存优化后的CSS
        $this->saveOptimizedCSS($minifiedCSS);
        
        // 5. 生成关键CSS
        $this->generateCriticalCSS($mergedCSS);
        
        echo "CSS性能优化完成!\n";
        echo "优化后的文件保存在: " . $this->outputDir . "/main-optimized.css\n";
    }
    
    private function mergeCSSFiles() {
        $merged = "/* 合并优化后的CSS文件 */\n";
        
        foreach ($this->config['files']['input'] as $file) {
            $filePath = $file;
            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                $merged .= "\n/* === " . basename($file) . " === */\n";
                $merged .= $content . "\n";
            } else {
                echo "警告: 文件不存在 - " . $filePath . "\n";
            }
        }
        
        return $merged;
    }
    
    private function removeComments($css) {
        // 保留版权注释
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        return $css;
    }
    
    private function minifyCSS($css) {
        // 移除多余的空白
        $css = preg_replace('/\s+/', ' ', $css);
        
        // 移除分号前的空格
        $css = preg_replace('/\s*;\s*/', ';', $css);
        
        // 移除大括号前后的空格
        $css = preg_replace('/\s*{\s*/', '{', $css);
        $css = preg_replace('/\s*}\s*/', '}', $css);
        
        // 移除逗号前的空格
        $css = preg_replace('/\s*,\s*/', ',', $css);
        
        // 移除冒号前的空格
        $css = preg_replace('/\s*:\s*/', ':', $css);
        
        // 移除开头和结尾的空白
        $css = trim($css);
        
        return $css;
    }
    
    private function saveOptimizedCSS($css) {
        $outputFile = $this->outputDir . '/main-optimized.css';
        file_put_contents($outputFile, $css);
        
        // 输出文件大小信息
        $originalSize = 0;
        foreach ($this->config['files']['input'] as $file) {
            if (file_exists($file)) {
                $originalSize += filesize($file);
            }
        }
        
        $optimizedSize = filesize($outputFile);
        $reduction = round((($originalSize - $optimizedSize) / $originalSize) * 100, 2);
        
        echo "原始文件大小: " . $this->formatBytes($originalSize) . "\n";
        echo "优化后文件大小: " . $this->formatBytes($optimizedSize) . "\n";
        echo "减少大小: " . $this->formatBytes($originalSize - $optimizedSize) . " (" . $reduction . "%)\n";
    }
    
    private function generateCriticalCSS($css) {
        // 提取关键CSS选择器
        $criticalCSS = "/* 关键CSS */\n";
        
        foreach ($this->config['critical']['selectors'] as $selector) {
            // 简单的正则匹配（实际项目中可能需要更复杂的解析）
            $pattern = '/' . preg_quote($selector, '/') . '[^{]*\{[^}]*\}/';
            if (preg_match($pattern, $css, $matches)) {
                $criticalCSS .= $matches[0] . "\n";
            }
        }
        
        file_put_contents($this->outputDir . '/critical.css', $criticalCSS);
        echo "关键CSS已生成: " . $this->outputDir . "/critical.css\n";
    }
    
    private function formatBytes($size, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
}

// 运行优化
if (php_sapi_name() === 'cli') {
    $optimizer = new CSSOptimizer('./css-optimization-config.json');
    $optimizer->optimize();
}
?>