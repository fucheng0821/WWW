<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSS样式测试页面</title>
    <link rel="stylesheet" href="./main.css">
    <style>
        body {
            font-family: 'PingFang SC', 'Microsoft YaHei', sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .test-section {
            margin-bottom: 40px;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 8px;
        }
        .test-section h2 {
            margin-top: 0;
            color: #2c3e50;
        }
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <h1>CSS样式测试页面</h1>
    
    <!-- 颜色和背景测试 -->
    <div class="test-section">
        <h2>颜色和背景测试</h2>
        <div class="test-grid">
            <div class="bg-primary" style="padding: 20px; border-radius: 8px;">
                <p class="text-dark">主背景色 + 深色文字</p>
            </div>
            <div class="bg-secondary" style="padding: 20px; border-radius: 8px;">
                <p class="text-light">次背景色 + 浅色文字</p>
            </div>
            <div class="bg-gradient-primary" style="padding: 20px; border-radius: 8px; color: white;">
                <p>主渐变背景</p>
            </div>
        </div>
    </div>
    
    <!-- 按钮组件测试 -->
    <div class="test-section">
        <h2>按钮组件测试</h2>
        <div class="test-grid">
            <div>
                <button class="btn btn-primary">主要按钮</button>
            </div>
            <div>
                <button class="btn btn-secondary">次要按钮</button>
            </div>
            <div>
                <button class="btn btn-outline">轮廓按钮</button>
            </div>
            <div>
                <button class="btn btn-danger">危险按钮</button>
            </div>
            <div>
                <button class="btn btn-success">成功按钮</button>
            </div>
            <div>
                <button class="btn btn-primary btn-sm">小按钮</button>
            </div>
            <div>
                <button class="btn btn-primary btn-lg">大按钮</button>
            </div>
            <div>
                <button class="btn btn-primary btn-block">块级按钮</button>
            </div>
        </div>
    </div>
    
    <!-- 卡片组件测试 -->
    <div class="test-section">
        <h2>卡片组件测试</h2>
        <div class="test-grid">
            <div class="card">
                <div class="card-header">
                    <h3>卡片标题</h3>
                </div>
                <div class="card-body">
                    <p>这是一个标准的卡片组件，包含头部、主体和底部。</p>
                </div>
                <div class="card-footer">
                    <button class="btn btn-primary">操作按钮</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 表单组件测试 -->
    <div class="test-section">
        <h2>表单组件测试</h2>
        <div class="test-grid">
            <div class="card">
                <form>
                    <div class="form-group">
                        <label class="form-label" for="name">姓名</label>
                        <input class="form-control" type="text" id="name" placeholder="请输入姓名">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="email">邮箱</label>
                        <input class="form-control" type="email" id="email" placeholder="请输入邮箱">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="message">留言</label>
                        <textarea class="form-control form-control-textarea" id="message" placeholder="请输入留言"></textarea>
                    </div>
                    <div class="form-group">
                        <button class="btn btn-primary" type="submit">提交</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- 导航组件测试 -->
    <div class="test-section">
        <h2>导航组件测试</h2>
        <nav class="nav">
            <a class="nav-link active" href="#">首页</a>
            <a class="nav-link" href="#">产品</a>
            <a class="nav-link" href="#">服务</a>
            <a class="nav-link" href="#">关于我们</a>
            <a class="nav-link" href="#">联系我们</a>
        </nav>
    </div>
    
    <!-- 排版测试 -->
    <div class="test-section">
        <h2>排版测试</h2>
        <div class="test-grid">
            <div>
                <h1>标题1</h1>
                <h2>标题2</h2>
                <h3>标题3</h3>
                <h4>标题4</h4>
                <h5>标题5</h5>
                <h6>标题6</h6>
            </div>
            <div>
                <p>这是一个段落文本，用于测试排版样式。文本应该具有良好的可读性和适当的行高。</p>
                <p><strong>粗体文本</strong> 和 <em>斜体文本</em> 的样式也应该正确显示。</p>
                <p>链接样式: <a href="#">这是一个链接</a></p>
            </div>
        </div>
    </div>
    
    <!-- 响应式测试 -->
    <div class="test-section">
        <h2>响应式测试</h2>
        <div class="test-grid">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 8px; color: white;">
                <p>调整浏览器窗口大小测试响应式效果</p>
            </div>
        </div>
    </div>
</body>
</html>