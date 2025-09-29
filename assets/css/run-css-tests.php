<?php
/**
 * CSS样式自动化测试脚本
 * 用于验证所有优化后的样式是否正常工作
 */

class CSSTestRunner {
    private $testResults = [];
    private $baseUrl = 'http://localhost:8000';
    
    public function runAllTests() {
        echo "开始CSS样式自动化测试...\n\n";
        
        // 1. 测试CSS文件是否存在
        $this->testCSSFilesExist();
        
        // 2. 测试关键CSS变量
        $this->testCSSVariables();
        
        // 3. 测试组件样式
        $this->testComponentStyles();
        
        // 4. 测试响应式样式
        $this->testResponsiveStyles();
        
        // 5. 输出测试结果
        $this->outputTestResults();
        
        return $this->allTestsPassed();
    }
    
    private function testCSSFilesExist() {
        $testName = "CSS文件存在性测试";
        echo "执行: " . $testName . "\n";
        
        $cssFiles = [
            '/assets/css/main.css',
            '/assets/css/base/reset.css',
            '/assets/css/base/variables.css',
            '/assets/css/base/typography.css',
            '/assets/css/components/components.css',
            '/assets/css/layout/grid.css',
            '/assets/css/layout/header.css',
            '/assets/css/layout/footer.css'
        ];
        
        $passed = true;
        foreach ($cssFiles as $file) {
            $filePath = $_SERVER['DOCUMENT_ROOT'] . $file;
            if (!file_exists($filePath)) {
                $this->addTestResult($testName, false, "文件不存在: " . $file);
                $passed = false;
            }
        }
        
        if ($passed) {
            $this->addTestResult($testName, true, "所有CSS文件都存在");
        }
    }
    
    private function testCSSVariables() {
        $testName = "CSS变量测试";
        echo "执行: " . $testName . "\n";
        
        // 检查关键变量是否定义
        $keyVariables = [
            '--color-accent-blue',
            '--color-accent-pink',
            '--color-text-dark',
            '--color-bg-primary',
            '--font-size-md',
            '--spacing-md'
        ];
        
        $variablesFile = $_SERVER['DOCUMENT_ROOT'] . '/assets/css/base/variables.css';
        if (!file_exists($variablesFile)) {
            $this->addTestResult($testName, false, "variables.css文件不存在");
            return;
        }
        
        $content = file_get_contents($variablesFile);
        $missingVariables = [];
        
        foreach ($keyVariables as $variable) {
            if (strpos($content, $variable) === false) {
                $missingVariables[] = $variable;
            }
        }
        
        if (empty($missingVariables)) {
            $this->addTestResult($testName, true, "所有关键CSS变量都已定义");
        } else {
            $this->addTestResult($testName, false, "缺失变量: " . implode(', ', $missingVariables));
        }
    }
    
    private function testComponentStyles() {
        $testName = "组件样式测试";
        echo "执行: " . $testName . "\n";
        
        // 检查关键组件类是否存在
        $componentClasses = [
            '.btn',
            '.btn-primary',
            '.card',
            '.form-control',
            '.nav'
        ];
        
        $componentsFile = $_SERVER['DOCUMENT_ROOT'] . '/assets/css/components/components.css';
        if (!file_exists($componentsFile)) {
            $this->addTestResult($testName, false, "components.css文件不存在");
            return;
        }
        
        $content = file_get_contents($componentsFile);
        $missingClasses = [];
        
        foreach ($componentClasses as $class) {
            // 移除点号进行搜索
            $className = ltrim($class, '.');
            if (strpos($content, $className) === false) {
                $missingClasses[] = $class;
            }
        }
        
        if (empty($missingClasses)) {
            $this->addTestResult($testName, true, "所有关键组件样式都已定义");
        } else {
            $this->addTestResult($testName, false, "缺失组件类: " . implode(', ', $missingClasses));
        }
    }
    
    private function testResponsiveStyles() {
        $testName = "响应式样式测试";
        echo "执行: " . $testName . "\n";
        
        // 检查响应式文件是否存在
        $responsiveFiles = [
            '/assets/css/layout/responsive.css',
            '/assets/css/utils/responsive-helpers.css'
        ];
        
        $passed = true;
        foreach ($responsiveFiles as $file) {
            $filePath = $_SERVER['DOCUMENT_ROOT'] . $file;
            if (!file_exists($filePath)) {
                $this->addTestResult($testName, false, "响应式文件不存在: " . $file);
                $passed = false;
            }
        }
        
        if ($passed) {
            $this->addTestResult($testName, true, "所有响应式文件都存在");
        }
    }
    
    private function addTestResult($testName, $passed, $message) {
        $this->testResults[] = [
            'test' => $testName,
            'passed' => $passed,
            'message' => $message
        ];
        
        echo ($passed ? "✓ 通过" : "✗ 失败") . ": " . $message . "\n\n";
    }
    
    private function outputTestResults() {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "CSS样式测试结果汇总\n";
        echo str_repeat("=", 50) . "\n";
        
        $passedTests = 0;
        $totalTests = count($this->testResults);
        
        foreach ($this->testResults as $result) {
            echo ($result['passed'] ? "✓" : "✗") . " " . $result['test'] . "\n";
            echo "  " . $result['message'] . "\n\n";
            if ($result['passed']) {
                $passedTests++;
            }
        }
        
        echo str_repeat("-", 50) . "\n";
        echo "总计: " . $passedTests . "/" . $totalTests . " 个测试通过\n";
        
        if ($passedTests === $totalTests) {
            echo "🎉 所有CSS样式测试通过!\n";
        } else {
            echo "⚠️  有 " . ($totalTests - $passedTests) . " 个测试失败，请检查上述问题。\n";
        }
    }
    
    private function allTestsPassed() {
        foreach ($this->testResults as $result) {
            if (!$result['passed']) {
                return false;
            }
        }
        return true;
    }
}

// 如果通过命令行运行
if (php_sapi_name() === 'cli') {
    $testRunner = new CSSTestRunner();
    $success = $testRunner->runAllTests();
    
    exit($success ? 0 : 1);
}

// 如果通过Web访问
if (isset($_GET['run'])) {
    $testRunner = new CSSTestRunner();
    $success = $testRunner->runAllTests();
    
    if ($success) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 20px 0;'>";
        echo "<strong>✅ 所有CSS样式测试通过!</strong>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 20px 0;'>";
        echo "<strong>❌ 有CSS样式测试失败，请检查控制台输出。</strong>";
        echo "</div>";
    }
} else {
    echo "<h1>CSS样式测试</h1>";
    echo "<p>点击下面的按钮运行CSS样式自动化测试：</p>";
    echo "<a href='?run=1' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>运行测试</a>";
}
?>